import React, { useState, useEffect } from 'react';
import { __ } from "@wordpress/i18n";
import axios from 'axios';
import Swal from 'sweetalert2';
import PageHeader from './includes/PageHeader';
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
  faEye,
  faEyeSlash,
  faCopy
} from "@fortawesome/free-solid-svg-icons";

import {
  faYoutube
} from "@fortawesome/free-brands-svg-icons";
const Sync = () => {

  const [loading, setLoading] = useState(true);


  if (loading) {
    // return (
    //     <>
    //         <div className="text-center">{__('Loading', 'ultimate-spin-wheel')}...</div>
    //         <div className="flex justify-center items-center h-40 mt-12"><div className="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-blue-500"></div></div>
    //     </>
    // )
  }

  return (
    <div className="mt-6 pt-6">
      <div className="mb-12 relative flex flex-col bg-clip-border rounded-xl bg-white dark:bg-gray-900 text-gray-700 shadow-sm">
        <PageHeader
          title="A Addons List"
          desc="It is important to be aware of your system settings and make sure that they are correctly configured for optimal performance." />
        <div className="p-3 md:p-6">
          {/* // Render your sync content here */}
        </div>
      </div>
    </div>
  );
};

export default Sync;
