import React from "react";
import { __ } from "@wordpress/i18n";

import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";

import { faHeadset } from "@fortawesome/free-solid-svg-icons";

export default function HelpCenter() {

  return (
    <div className="p-4 min-h-[60vh] w-full text-center bg-white border border-gray-200 rounded-lg shadow-sm sm:p-8 dark:bg-gray-900 dark:border-gray-900 flex flex-col items-center justify-center">
      <FontAwesomeIcon icon={faHeadset} className="h-12 w-12 text-gray-400 mb-3" />
      <h5 className="mb-4 text-3xl font-bold text-gray-900 dark:text-white">Docs & Help Center</h5>
      <p className="mb-5 text-base text-gray-500 sm:text-lg dark:text-gray-100">
        If you need help, please contact live chat support for fastest response on{" "}
        <a href="https://wowdevs.com/support" target="_blank" rel="noopener noreferrer" className="text-blue-500">
          https://wowdevs.com/support
        </a>
        .
      </p>
      <p className="text-base text-gray-500 sm:text-lg dark:text-gray-100">
        You can also check our documentation at{" "}
        <a href="https://wowdevs.com/docs/spin-wheel/" target="_blank" rel="noopener noreferrer" className="text-blue-500">
          https://wowdevs.com/docs/spin-wheel/
        </a>
        .
      </p>
    </div>
  );

}
