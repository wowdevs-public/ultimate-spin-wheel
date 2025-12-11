import React from "react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faChevronLeft, faChevronRight } from "@fortawesome/free-solid-svg-icons";
import Sticky from "react-sticky-el";
import { __ } from "@wordpress/i18n";

const Sidebar = ({
  data,
  activeTab,
  onTabClick,
  isSidebarOpen,
  toggleSidebar,
  isLargeScreen,
}) => {
  const renderTabs = () => {
    return data.map(({ label, value, icon }) => (
      <button
        key={value}
        onClick={() => onTabClick(value)}
        className={`flex py-1.5 mb-1 items-center w-full leading-tight transition-all rounded-lg outline-none text-start justify-start font-sans text-base font-normal select-none cursor-pointer ${
          activeTab === value
            ? "tab-active text-gray-900 bg-white dark:bg-gray-800 dark:text-white"
            : "hover:bg-purple-700 text-white"
        }`}
      >
        <div className="flex gap-3 items-center w-full px-2 py-3 leading-tight transition-all rounded-lg outline-none text-start">
          <div className="grid place-items-center">{icon}</div>
          {isSidebarOpen && label}
        </div>
      </button>
    ));
  };

  const SidebarHeader = () => (
    <div className="flex items-center justify-between mb-4 pb-4 border-b border-white/20 dark:border-gray-800">
      {isSidebarOpen && (
        <h3 className="text-lg font-bold text-white uppercase">
          {USPIN_CONFIG_ADMIN.plugin_name}
        </h3>
      )}
      {isLargeScreen && (
        <button
          onClick={toggleSidebar}
          className="p-2 rounded-lg hover:bg-purple-700 dark:hover:bg-gray-800 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-opacity-50 ml-auto leading-none"
          aria-label={isSidebarOpen ? __('Hide Menu', 'ultimate-spin-wheel') : __('Show Menu', 'ultimate-spin-wheel')}
          title={isSidebarOpen ? __('Hide Menu', 'ultimate-spin-wheel') : __('Show Menu', 'ultimate-spin-wheel')}
        >
          <FontAwesomeIcon
            icon={isSidebarOpen ? faChevronLeft : faChevronRight}
            className="h-5 w-5"
          />
        </button>
      )}
    </div>
  );

  const sidebarContent = (
    <div className="bg-gradient-to-r from-indigo-800 to-purple-600 dark:from-gray-900 dark:to-gray-900 p-4 shadow-xl rounded-lg">
      <SidebarHeader />
      {renderTabs()}
    </div>
  );

  return (
    <div
      className={`transition-all duration-300 ease-in-out ${
        !isLargeScreen && !isSidebarOpen
          ? "hidden"
          : isLargeScreen
          ? isSidebarOpen
            ? "min-w-56"
            : "w-[70px]"
          : "min-w-56"
      }`}
    >
      <Sticky>{sidebarContent}</Sticky>
    </div>
  );
};

export default Sidebar;
