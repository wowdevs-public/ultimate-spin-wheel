import React, { useState, useEffect } from "react";
import { __ } from "@wordpress/i18n";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
  faWifi,
  faList,
  faGear,
  faKey,
  faQuestion,
  faHeadset,
} from "@fortawesome/free-solid-svg-icons";

import Nav from "./components/includes/Nav";
import Footer from "./components/includes/Footer";
import DashboardLayout from "./components/DashboardLayout";

const Welcome = React.lazy(() => import("./components/Welcome"));
const Reports = React.lazy(() => import("./components/Reports"));
const Campaigns = React.lazy(() => import("./Campaigns"));
const Entries = React.lazy(() => import("./Entries"));
const License = React.lazy(() => import("./components/License"));
const GetPro = React.lazy(() => import("./components/includes/GetPro"));
const FAQs = React.lazy(() => import("./components/FAQs"));

const HelpCenter = React.lazy(() => import("./components/includes/HelpCenter"));

const ConfigSpinWheel = React.lazy(() => import("./ConfigSpinWheel"));

const DashboardData = [
  {
    label: __('Dashboard', 'ultimate-spin-wheel'),
    value: "dashboard",
    icon: <FontAwesomeIcon icon={faWifi} className="h-5 w-5" />,
    desc: <Reports />,
  },
  {
    label: __('Campaigns', 'ultimate-spin-wheel'),
    value: "campaigns",
    icon: <FontAwesomeIcon icon={faList} className="h-5 w-5" />,
    desc: <Campaigns />,
  },
  {
    label: __('Entries', 'ultimate-spin-wheel'),
    value: "entries",
    icon: <FontAwesomeIcon icon={faList} className="h-5 w-5" />,
    desc: <Entries />,
  },
  {
    label: __('License', 'ultimate-spin-wheel'),
    value: "license",
    icon: <FontAwesomeIcon icon={faKey} className="h-5 w-5" />,
    desc: <License />,
  },
  // {
  //   label: USPIN_CONFIG_ADMIN.pro_init ? __('License', 'ultimate-spin-wheel') : __('Get Pro', 'ultimate-spin-wheel'),
  //   value: "license",
  //   icon: <FontAwesomeIcon icon={faKey} className="h-5 w-5" />,
  //   desc: USPIN_CONFIG_ADMIN.pro_init ? <License /> : <GetPro />,
  // },
  {
    label: __('FAQs', 'ultimate-spin-wheel'),
    value: "faqs",
    icon: <FontAwesomeIcon icon={faQuestion} className="h-5 w-5" />,
    desc: <FAQs />,
  },
  {
    label: __('Support', 'ultimate-spin-wheel'),
    value: "support",
    icon: <FontAwesomeIcon icon={faHeadset} className="h-5 w-5" />,
    desc: <HelpCenter />,
  },
];

/**
 * Check if URL params contain post_id, then push Campaigns tab dynamically
 */
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('post_id')) {
  DashboardData.splice(2, 0, {
    label: __('Configuration', 'ultimate-spin-wheel'),
    value: "config",
    icon: <FontAwesomeIcon icon={faGear} className="h-5 w-5" />,
    desc: <ConfigSpinWheel />,
  });
}


const Dashboard = () => {
  const pluginNameWithoutSpace = USPIN_CONFIG_ADMIN.plugin_name.replace(/\s+/g, '') + "SpinWheel";

  const [activeTab, setActiveTab] = useState(() => {
    const hash = window.location.hash.replace('#', '');
    if (hash && DashboardData.some(item => item.value === hash)) {
      return hash;
    }
    return localStorage.getItem(pluginNameWithoutSpace + "ActiveTab") || "dashboard";
  });

  const [isSidebarOpen, setIsSidebarOpen] = useState(() => {
    const savedState = localStorage.getItem(pluginNameWithoutSpace + "SidebarOpen");
    return savedState !== null ? savedState === "true" : true;
  });

  const [isLargeScreen, setIsLargeScreen] = useState(window.innerWidth >= 1280);

  // Helper function to check if a tab value is valid
  const isValidTab = (tab) => {
    return DashboardData.some(item => item.value === tab);
  };

  // Listen for hash changes in URL
  useEffect(() => {
    const handleHashChange = () => {
      const hash = window.location.hash.replace('#', '');
      // Only update if hash corresponds to a valid tab
      if (hash && isValidTab(hash)) {
        setActiveTab(hash);
      }
    };

    window.addEventListener('hashchange', handleHashChange);
    // Check hash on initial load
    handleHashChange();

    return () => {
      window.removeEventListener('hashchange', handleHashChange);
    };
  }, []);

  useEffect(() => {
    const handleResize = () => {
      setIsLargeScreen(window.innerWidth >= 1280);
    };

    handleResize();
    window.addEventListener("resize", handleResize);

    return () => {
      window.removeEventListener("resize", handleResize);
    };
  }, []);

  useEffect(() => {
    localStorage.setItem(pluginNameWithoutSpace + "ActiveTab", activeTab);
    window.location.hash = activeTab;
  }, [activeTab]);

  useEffect(() => {
    localStorage.setItem(pluginNameWithoutSpace + "SidebarOpen", isSidebarOpen);
  }, [isSidebarOpen]);

  // Listen for hash changes in the URL
  useEffect(() => {
    const handleHashChange = () => {
      const hash = window.location.hash.replace('#', '');
      if (isValidTab(hash)) {
        setActiveTab(hash);
      }
    };

    window.addEventListener('hashchange', handleHashChange);

    // Call the handler once on mount to set the initial tab based on the URL
    handleHashChange();

    return () => {
      window.removeEventListener('hashchange', handleHashChange);
    };
  }, []);

  const handleTabClick = (value) => {
    setActiveTab(value);
  };

  const toggleSidebar = () => {
    setIsSidebarOpen(!isSidebarOpen);
  };

  return (
    <>
      <Nav />
      <DashboardLayout
        data={DashboardData}
        activeTab={activeTab}
        onTabClick={handleTabClick}
        isSidebarOpen={isSidebarOpen}
        toggleSidebar={toggleSidebar}
        isLargeScreen={isLargeScreen}
      />
      <Footer />
    </>
  );
};

export default Dashboard;
