import React from 'react';
import { __ } from "@wordpress/i18n";

const PageHeader = ({ title, desc }) => {
  return (
    <div className="relative bg-gradient-to-r from-indigo-800 to-purple-600 dark:from-gray-900 dark:to-gray-700 bg-clip-border mx-4 rounded-xl overflow-hidden 
        text-white shadow-purple-500/40 dark:shadow-gray-900/40 
        shadow-lg -mt-12 mb-8 p-3 lg:p-6">
      
      <div className="flex w-full items-center justify-between">
        <div>
          <h6 className="block antialiased tracking-normal font-sans text-base font-semibold leading-relaxed 
            text-white mt-0 mb-1">
            {__(title, 'ultimate-spin-wheel')}
          </h6>
          <div className="block antialiased font-sans text-md font-normal 
            dark:text-gray-300">
            {__(desc, 'ultimate-spin-wheel')}
          </div>
        </div>
      </div>
    </div>
  );
};

export default PageHeader;
