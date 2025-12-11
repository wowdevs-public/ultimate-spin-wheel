import React, { useState, useEffect, useContext, useRef } from 'react';
import { __ } from "@wordpress/i18n";
import axios from 'axios';
import Swal from 'sweetalert2';
import Switch from "react-switch";
import { AppContext } from "./AppContext";

import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
  faCheck,
  faTrash,
  faCheckDouble,
} from "@fortawesome/free-solid-svg-icons";


const RenderFeatures = ({ featuresType }) => {
  const { triggerRefresh } = useContext(AppContext);

  const [loading, setLoading] = useState(true);
  const [features, setFeatures] = useState([]);
  const [searchValue, setSearchValue] = useState(""); // State for search
  const [isSearchEmpty, setIsSearchEmpty] = useState(false); // State to track if search results are empty
  const formRef = useRef(featuresType);

  useEffect(() => {
    const fetchFeatures = async () => {
      try {
        setLoading(true);

        const response = await axios.post(ajaxurl, new URLSearchParams({
          action: 'ultimate_spin_wheel_get_settings',
          action_type: featuresType,
          _wpnonce: USPIN_CONFIG_ADMIN.nonce
        }));

        if (response?.data?.success) {
          const features = Array.isArray(response?.data?.data) ? response.data.data : [];
          setFeatures(features);
        } else {
          Swal.fire({
            icon: 'error',
            title: __('Error', 'ultimate-spin-wheel'),
            text: response?.data?.data?.message || __('Unknown error', 'ultimate-spin-wheel')
          });
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: __('Error', 'ultimate-spin-wheel'),
          text: error.message
        });
      } finally {
        setLoading(false);
      }
    };

    fetchFeatures();
  }, [featuresType]); // Add featuresType to dependency array

  const handleSearch = (event) => {
    const searchValue = event.target.value.toLowerCase();
    setSearchValue(searchValue);

    const hasResults = features.some((feature) =>
      feature.label.toLowerCase().includes(searchValue)
    );
    setIsSearchEmpty(!hasResults);
  };

  const toggleAllFeatures = (value) => {
    setFeatures((prevFeatures) =>
      prevFeatures.map((feature) => ({ ...feature, value: value ? "on" : "off" }))
    );
    formRef.current.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
  };

  if (loading) {
    return (
      <>
        <div className="text-center">{__('Loading', 'ultimate-spin-wheel')}...</div>
        <div className="flex justify-center items-center h-40 mt-12">
          <div className="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-blue-500"></div>
        </div>
      </>
    );
  }

  const submitForm = (event) => {
    event.preventDefault();

    const updatedFeatures = {};
    const formData = new FormData(event.target);
    for (const [key, value] of formData.entries()) {
      updatedFeatures[key] = value;
    }

    Swal.fire({
      title: 'Loading...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    const addonsParams = new URLSearchParams();
    Object.entries(updatedFeatures).forEach(([key, value]) => {
      addonsParams.append(key, value);
    });

    fetch(window.ajaxurl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: new URLSearchParams({ // Use URLSearchParams for WordPress AJAX requests
        action: 'ultimate_spin_wheel_set_settings', // Ensure this matches the registered action in WordPress
        action_type: featuresType,
        _wpnonce: USPIN_CONFIG_ADMIN.nonce,
        ...Object.fromEntries(addonsParams) // Spread the flattened addons key-value pairs
      }).toString() // Convert to string for proper formatting
    })
      .then(async (response) => {
        if (!response.ok) {
          const errorData = await response.json();
          console.error('Server responded with error:', errorData);
          Swal.fire({
            icon: 'error',
            title: errorData?.title || 'Error',
            text: errorData?.msg || 'An error occurred while updating feature settings.'
          });
          return;
        }
        const data = await response.json();
        if (data?.success) {
          triggerRefresh();
          const Toast = Swal.mixin({
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            didOpen: (toast) => {
              toast.addEventListener('mouseenter', Swal.stopTimer);
              toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
          });
          Toast.fire({
            icon: 'success',
            title: data?.data?.title,
            text: data?.data?.msg
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: data?.data?.title || 'Error',
            text: data?.data?.msg || 'An error occurred while updating feature settings.'
          });
        }
      })
      .catch((error) => {
        console.error('Error updating feature settings:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred while updating feature settings.'
        });
      });

  };

  const ItemCard = ({ data }) => {
    const [isChecked, setIsChecked] = useState(data.value === "on");

    const handleSwitchChange = () => {
      if (data.feature_type === 'pro' && !USPIN_CONFIG_ADMIN?.pro_init) {
        Swal.fire({
          icon: 'warning',
          title: __('Pro Feature', 'ultimate-spin-wheel'),
          text: __('This is a Pro feature. Please activate Pro to use this feature.', 'ultimate-spin-wheel')
        });
        return;
      }

      setIsChecked(!isChecked);
      setTimeout(() => {
        //update the data value
        const updatedFeatures = {};
        updatedFeatures[data.name] = !isChecked ? "on" : "off";
        // console.log('Updated feature:', updatedFeatures);

        setFeatures((prevFeatures) => {
          const updatedFeatures = prevFeatures.map((feature) => {
            if (feature.name === data.name) {
              return { ...feature, value: !isChecked ? "on" : "off" };
            }
            return feature;
          });
          return updatedFeatures;
        });
      }, 1000);

      // Delay request to avoid multiple requests at once
      clearTimeout(window.featureUpdateTimeout);
      window.featureUpdateTimeout = setTimeout(() => {
        // console.log('Submitting form...');
        formRef.current.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
      }, 2000); // Delay request by 1000ms to batch updates
    };

    let badgeValue = data?.content_type?.includes('new') ? 'New' : false;
    const badge = ('pro' !== data?.feature_type ? badgeValue : (USPIN_CONFIG_ADMIN?.pro_init ? badgeValue : 'Pro'));

    return (
      <div className="ultimate-spin-wheel-features-items w-100 px-5 py-4 flex items-center gap-3 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 relative overflow-hidden"
        data-feature-type={data?.feature_type}
      >
        {badge && (
          <div className="absolute left-0 top-0 h-16 w-16">
            <div
              className="absolute transform -rotate-45 text-center text-white text-xs font-semibold py-1 left-[-64px] top-[8px] w-[170px] bg-gradient-to-r from-[#e052bd] via-[#8445a2] to-[#8441a4]">
              {badge}
            </div>
          </div>
        )}
        <div className="sa-icon-wrap text-5xl text-[#8441A4] bg-[#ebcff926] p-2.5 rounded-md dark:text-white">
          <i className={`wd-icon-${data.name}`}></i>
        </div>
        <div className="flex flex-col">
          <h6 className="text-l font-medium text-gray-800 dark:text-white leading-[1.3] mb-1">
            <a href={data?.demo_url} target="_blank" rel="noreferrer" className="hover:text-[#8441A4]" title="Click to view demo">
              {data.label}
            </a>
          </h6>
        </div>
        <div className="flex items-center ml-auto mr-0">
          <label htmlFor={`switch-${data.name}`}>
            <input type="hidden" value="off" name={`${data.name}`}></input>
            <Switch
              checked={'pro' !== data?.feature_type ? isChecked : (USPIN_CONFIG_ADMIN?.pro_init && isChecked ? isChecked : false)}
              onChange={handleSwitchChange}
              onColor="#b47fcc"
              onHandleColor="#8441A4"
              handleDiameter={30}
              uncheckedIcon={false}
              checkedIcon={false}
              boxShadow="0px 1px 5px rgba(0, 0, 0, 0.6)"
              height={18}
              width={48}
              className="react-switch"
              id={`switch-${data.name}`}
              name={`${data.name}`}
            />
          </label>
        </div>
      </div>
    );
  }

  return (
    <form method="post" name={`ultimate-spin-wheel-${featuresType}`} onSubmit={submitForm} ref={formRef}>
      <div className='flex flex-col md:flex-row w-100 mb-5 gap-3 md:justify-between'>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            className="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-1.5 rounded-md dark:bg-blue-900 dark:text-blue-300 hover:bg-blue-200 whitespace-nowrap"
            onClick={() => setSearchValue("")}
          >
            <FontAwesomeIcon icon={faCheck} className="me-1" />
            {__('All', 'ultimate-spin-wheel')}
          </button>
        </div>
        <div className="flex flex-wrap gap-2 items-center">
          <input
            type="search"
            className="w-full sm:w-[130px] border border-gray-200 text-sm font-medium px-2.5 py-1.5 rounded-md dark:border-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-300 dark:bg-gray-800"
            placeholder={__('Search ...', 'ultimate-spin-wheel')}
            value={searchValue}
            onChange={handleSearch}
          />
          <button
            className="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-1.5 rounded-md dark:bg-blue-900 dark:text-blue-300 hover:bg-blue-200 whitespace-nowrap"
            onClick={() => toggleAllFeatures(true)}
          >
            <FontAwesomeIcon icon={faCheckDouble} className="me-1" />
            {__('Enable All', 'ultimate-spin-wheel')}
          </button>
          <button
            className="bg-red-100 text-red-800 text-sm font-medium px-2.5 py-1.5 rounded-md dark:bg-red-900 dark:text-red-300 hover:bg-red-200 whitespace-nowrap"
            onClick={() => toggleAllFeatures(false)}
          >
            <FontAwesomeIcon icon={faTrash} className="me-1" />
            {__('Disable All', 'ultimate-spin-wheel')}
          </button>
        </div>
      </div>

      {isSearchEmpty && (
        <div className="text-center text-gray-500 dark:text-gray-300 mt-5">
          {__('Sorry, not found. Please contact support for more information.', 'ultimate-spin-wheel')}
        </div>
      )}

      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {features
          .filter((feature) => feature.label.toLowerCase().includes(searchValue))
          .map((feature, index) => (
            <ItemCard key={index} data={feature} />
          ))}
      </div>
      <button type="submit" className="hidden">Submit</button>
    </form>
  );
};

export default RenderFeatures;
