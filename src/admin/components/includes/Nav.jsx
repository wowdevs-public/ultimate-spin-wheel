import React, { useState, useEffect } from "react";
import { __ } from "@wordpress/i18n";

import Notice from "../Notice";

import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faSun, faMoon } from "@fortawesome/free-solid-svg-icons";

export default function Nav() {
  const pluginNameWithoutSpace = USPIN_CONFIG_ADMIN.plugin_name.replace(/\s+/g, '');

  const [isDarkMode, setIsDarkMode] = useState(() => {
    return localStorage.getItem(pluginNameWithoutSpace + "DarkMode") === "true";
  });

  useEffect(() => {
    document.documentElement.classList.toggle("dark", isDarkMode);
    localStorage.setItem(pluginNameWithoutSpace + "DarkMode", isDarkMode);
  }, [isDarkMode]);

  return (
    <>
      {/* <Notice /> */}
      <nav className="bg-white border-gray-200 rounded-xl px-2 lg:px-4 py-3 lg:py-4 dark:bg-gray-900 shadow-sm">
        <div className="flex flex-wrap justify-between items-center gap-3">
          <div className="flex justify-start items-center">
            <a
              href={USPIN_CONFIG_ADMIN.admin_url + 'admin.php?page=' + USPIN_CONFIG_ADMIN.plugin_slug}
              className="flex items-center outline-none focus:outline-none shadow-none"
            >
              <img
                src={USPIN_CONFIG_ADMIN.logo}
                className="mr-2 h-10 rounded"
                alt={USPIN_CONFIG_ADMIN.plugin_name}
              />
              <span className="text-xl lg:text-3xl uppercase font-bold bg-gradient-to-r from-indigo-800 via-sky-400 to-purple-600 bg-clip-text text-transparent relative">
                <span className="absolute -top-2 right-0 text-xs uppercase font-bold bg-gradient-to-r from-indigo-800 via-sky-400 to-purple-600 bg-clip-text text-transparent tracking-widest">ULTIMATE</span>
                {USPIN_CONFIG_ADMIN.plugin_name}
              </span>
            </a>
          </div>
          <div className="flex items-center lg:order-2 gap-3">
            <button
              onClick={() => setIsDarkMode(!isDarkMode)}
              className={`rounded-lg px-2.5 py-2 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700 ring-2 ring-gray-300 dark:ring-gray-600`}
            >
              <FontAwesomeIcon icon={isDarkMode ? faSun : faMoon} className="h-4 w-4" />
            </button>
            {/* Notifications */}
            <button
              type="button"
              data-dropdown-toggle="notification-dropdown"
              className="hidden p-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
            >
              <span className="sr-only">View notifications</span>
              {/* Bell icon */}
              <svg
                aria-hidden="true"
                className="w-6 h-6"
                fill="currentColor"
                viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
              </svg>
            </button>
            {/* Dropdown menu */}
            <div
              className="hidden overflow-hidden z-50 my-4 max-w-sm text-base list-none bg-white rounded divide-y divide-gray-100 shadow-lg dark:divide-gray-600 dark:bg-gray-700"
              id="notification-dropdown"
            >
              <div className="block py-2 px-4 text-base font-medium text-center text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                {/*?php esc_html_e( 'Notifications (Empty)', 'ultimate-spin-wheel' ); ?*/}
              </div>
              <div></div>
              <a
                href="#"
                className="block py-2 text-base font-normal text-center text-gray-900 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:hover:underline"
              >
                <div className="inline-flex items-center ">
                  <svg
                    aria-hidden="true"
                    className="mr-2 w-5 h-5"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                    <path
                      fillRule="evenodd"
                      d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                      clipRule="evenodd"
                    />
                  </svg>
                  {/*?php esc_html_e( 'View all', 'ultimate-spin-wheel' ); ?*/}
                </div>
              </a>
            </div>
            <button type="button" className='relative w-10 h-10 bg-sky-100 border-2 border-solid border-indigo-600 flex justify-center items-center rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600'>
              <img className="rounded-full" src={USPIN_CONFIG_ADMIN.current_user.avatar} alt="avatar"/>
              <span className="top-0 left-7 absolute w-3.5 h-3.5 bg-green-400 border-2 border-white dark:border-gray-800 rounded-full"
              ></span>
            </button>
          </div>
        </div>
      </nav>
    </>
  );
}
