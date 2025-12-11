import React from 'react';
import { __ } from "@wordpress/i18n";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
  faHeadset,
  faLayerGroup,
  faBriefcase,
  faChartLine,
  faUserFriends,
  faUserShield,
  faArrowRight,
  faWandMagicSparkles,
  faCode,
  faMoon,
  faTable,
  faWindowMaximize,
  faTag,
  faVideo,
  faQrcode,
  faBars,
  faScroll,
  faCompass,
  faParachuteBox,
  faInfoCircle,
  faCheck,
  faTimes,
  faCrown,
  faBuilding,
  faUser
} from "@fortawesome/free-solid-svg-icons";


const additionalFeatures = [
  {
    icon: faInfoCircle,
    title: __('Tooltip', 'ultimate-spin-wheel'),
    description: __('Add informative tooltips to any element with customizable styling.', 'ultimate-spin-wheel'),
  },
  {
    icon: faWandMagicSparkles,
    title: __('Theme Builder', 'ultimate-spin-wheel'),
    description: __('Create custom headers, footers, and page templates with full control.', 'ultimate-spin-wheel'),
  },
  {
    icon: faScroll,
    title: __('Smooth Scroll', 'ultimate-spin-wheel'),
    description: __('Enhance user experience with buttery-smooth page scrolling.', 'ultimate-spin-wheel'),
  },
  {
    icon: faLayerGroup,
    title: __('Premium Templates', 'ultimate-spin-wheel'),
    description: __('Access our exclusive library of professionally designed templates.', 'ultimate-spin-wheel'),
  },
  {
    icon: faCompass,
    title: __('Breadcrumbs', 'ultimate-spin-wheel'),
    description: __('Improve site navigation and SEO with customizable breadcrumbs.', 'ultimate-spin-wheel'),
  },
  {
    icon: faMoon,
    title: __('Dark Mode', 'ultimate-spin-wheel'),
    description: __('Add togglable dark mode to your site with just a few clicks.', 'ultimate-spin-wheel'),
  },
  {
    icon: faWindowMaximize,
    title: __('Iframe', 'ultimate-spin-wheel'),
    description: __('Embed external content seamlessly with advanced iframe options.', 'ultimate-spin-wheel'),
  },
  {
    icon: faTable,
    title: __('Data Table', 'ultimate-spin-wheel'),
    description: __('Create responsive, sortable, and filterable tables effortlessly.', 'ultimate-spin-wheel'),
  },
  {
    icon: faBars,
    title: __('Nav Menu', 'ultimate-spin-wheel'),
    description: __('Design stunning navigation menus with countless style options.', 'ultimate-spin-wheel'),
  },
  {
    icon: faWindowMaximize,
    title: __('Off-canvas', 'ultimate-spin-wheel'),
    description: __('Create slidable content panels for menus, forms, and more.', 'ultimate-spin-wheel'),
  },
  {
    icon: faTable,
    title: __('Pricing Table', 'ultimate-spin-wheel'),
    description: __('Showcase your pricing plans with beautiful, conversion-optimized tables.', 'ultimate-spin-wheel'),
  },
  {
    icon: faQrcode,
    title: __('QR Code', 'ultimate-spin-wheel'),
    description: __('Generate custom QR codes to connect offline and online experiences.', 'ultimate-spin-wheel'),
  },
  {
    icon: faTag,
    title: __('Tags Cloud', 'ultimate-spin-wheel'),
    description: __('Display your content tags in an interactive, visually appealing cloud.', 'ultimate-spin-wheel'),
  },
  {
    icon: faVideo,
    title: __('Hover Video', 'ultimate-spin-wheel'),
    description: __('Create interactive videos that play on hover with customizable controls.', 'ultimate-spin-wheel'),
  },
  {
    icon: faVideo,
    title: __('Video Gallery', 'ultimate-spin-wheel'),
    description: __('Build beautiful video galleries with filters and custom layouts.', 'ultimate-spin-wheel'),
  },
  {
    icon: faParachuteBox,
    title: __('And Much More!', 'ultimate-spin-wheel'),
    description: __('Particles, Display Controls, Custom Scripts, and many more premium features.', 'ultimate-spin-wheel'),
  },
];

const pricingFeatures = [
  __('All Widgets Access', 'ultimate-spin-wheel'),
  __('All Features Access', 'ultimate-spin-wheel'),
  __('Premium Templates', 'ultimate-spin-wheel'),
  __('Theme Builder', 'ultimate-spin-wheel'),
  __('WooCommerce', 'ultimate-spin-wheel'),
  __('White Label Option', 'ultimate-spin-wheel'),
  __('Priority Support', 'ultimate-spin-wheel'),
  __('Lifetime Updates', 'ultimate-spin-wheel'),
  __('Multiple Site License', 'ultimate-spin-wheel'),
  __('Agency Features', 'ultimate-spin-wheel'),
];

const pricingPlans = [
  {
    id: 'free',
    name: __('FREE', 'ultimate-spin-wheel'),
    price: __('$0', 'ultimate-spin-wheel'),
    interval: '',
    description: __('Limited Features', 'ultimate-spin-wheel'),
    icon: faUser,
    iconColor: 'text-gray-400',
    popular: false,
    buttonText: __('Current Plan', 'ultimate-spin-wheel'),
    buttonLink: '#',
    buttonColor: 'bg-gray-400',
    buttonGradient: false,
    smallText: __('Basic features for personal projects', 'ultimate-spin-wheel'),
    trial: false,
    features: [
      { text: __('Limited Widgets', 'ultimate-spin-wheel'), included: true },
      { text: __('Limited', 'ultimate-spin-wheel'), included: true },
      { text: '', included: false },
      { text: __('Limited', 'ultimate-spin-wheel'), included: true },
      { text: '', included: false },
      { text: '', included: false },
      { text: '', included: false },
      { text: '', included: false },
      { text: '', included: false },
      { text: '', included: false },
    ]
  },
  {
    id: 'pro',
    name: __('PRO', 'ultimate-spin-wheel'),
    price: __('$29', 'ultimate-spin-wheel'),
    interval: __('/year', 'ultimate-spin-wheel'),
    description: __('Single Site License', 'ultimate-spin-wheel'),
    icon: faCrown,
    iconColor: 'text-purple-500',
    popular: true,
    buttonText: __('Get Started', 'ultimate-spin-wheel'),
    buttonLink: 'https://smartcart.com/pricing/',
    buttonColor: '',
    buttonGradient: 'bg-gradient-to-br from-pink-500 to-purple-700 hover:bg-gradient-to-bl',
    smallText: __('14-day free trial.', 'ultimate-spin-wheel'),
    trial: true,
    features: [
      { text: __('All Widgets', 'ultimate-spin-wheel'), included: true },
      { text: '', included: true },
      { text: '', included: true },
      { text: '', included: true },
      { text: '', included: true },
      { text: '', included: false },
      { text: '', included: true },
      { text: '', included: true },
      { text: '', included: false },
      { text: '', included: false },
    ]
  },
  {
    id: 'agency',
    name: __('AGENCY', 'ultimate-spin-wheel'),
    price: __('$149', 'ultimate-spin-wheel'),
    interval: __('/year', 'ultimate-spin-wheel'),
    description: __('Unlimited Sites', 'ultimate-spin-wheel'),
    icon: faBuilding,
    iconColor: 'text-gray-600 dark:text-gray-300',
    popular: false,
    buttonText: __('Get Started', 'ultimate-spin-wheel'),
    buttonLink: 'https://smartcart.com/pricing/',
    buttonColor: '',
    buttonGradient: 'bg-gradient-to-br from-purple-600 to-blue-500 hover:bg-gradient-to-bl',
    smallText: __('14-day free trial. Perfect for agencies and professionals.', 'ultimate-spin-wheel'),
    trial: true,
    features: [
      { text: __('All Widgets', 'ultimate-spin-wheel'), included: true },
      { text: '', included: true },
      { text: '', included: true },
      { text: '', included: true },
      { text: '', included: true },
      { text: '', included: true },
      { text: '', included: true },
      { text: '', included: true },
      { text: '', included: true },
      { text: '', included: true },
    ]
  }
];

const GetPro = () => (
  <section className="bg-white dark:bg-gray-900">
    <div className="py-8 px-4 mx-auto max-w-screen-xl sm:py-16 lg:px-6">
      {/* More Premium Features Section */}
      <div className="max-w-screen-md mb-8 lg:mb-12 mx-auto text-center">
        <h2 className="mb-4 text-3xl tracking-tight font-extrabold text-gray-900 dark:text-white">
          {__('Unlock These Premium Features Today', 'ultimate-spin-wheel')}
        </h2>
        <p className="text-gray-500 sm:text-lg dark:text-gray-400">
          {__('Take your website to the next level with our extensive collection of premium widgets and features.', 'ultimate-spin-wheel')}
        </p>
      </div>
      <div className="space-y-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 md:gap-8 md:space-y-0">
        {additionalFeatures.map((feature, index) => (
          <div key={`additional-${index}`} className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300">
            <div className="flex items-center mb-2">
              <FontAwesomeIcon icon={feature.icon} className="w-5 h-5 text-primary-600 mr-2 dark:text-primary-300" />
              <h3 className="text-lg font-bold dark:text-white">{feature.title}</h3>
            </div>
            <p className="text-sm text-gray-500 dark:text-gray-400">{feature.description}</p>
          </div>
        ))}
      </div>
    </div>

    {/* Pricing Table Section */}
    <section className="text-gray-700 body-font overflow-hidden  border-gray-200 dark:border-gray-700 dark:text-gray-300">
      <div className="container px-5 py-6 mx-auto flex flex-wrap">
        <div className="flex w-full mb-10 flex-wrap">
          <h2 className="text-3xl font-bold text-center w-full mb-2">{__('Choose Your Plan', 'ultimate-spin-wheel')}</h2>
          <p className="text-center w-full text-gray-500 dark:text-gray-400 mb-5">
            {__('Select the perfect plan for your needs with our flexible pricing options', 'ultimate-spin-wheel')}
          </p>
        </div>

        <div className="lg:w-1/4 mt-48 hidden lg:block">
          <div className="mt-px border-t border-gray-300 dark:border-gray-700 border-b border-l rounded-tl-lg rounded-bl-lg overflow-hidden">
            {pricingFeatures.map((feature, index) => (
              <p key={index} className={`${index % 2 === 0 ? 'bg-gray-100 dark:bg-gray-800' : ''} text-gray-900 dark:text-gray-200 h-12 text-center px-4 flex items-center justify-start -mt-px`}>
                {feature}
              </p>
            ))}
          </div>
        </div>

        <div className="flex lg:w-3/4 w-full flex-wrap lg:border border-gray-300 dark:border-gray-700 rounded-lg">
          {pricingPlans.map((plan, planIndex) => (
            <div
              key={plan.id}
              className={`lg:w-1/3 ${planIndex === 1 ? 'lg:-mt-px' : 'lg:mt-px'} w-full mb-10 lg:mb-0 ${plan.popular
                  ? 'border-2 rounded-lg border-purple-500 relative'
                  : 'border-2 border-gray-300 dark:border-gray-700 lg:border-none rounded-lg lg:rounded-none'
                }`}
            >
              {plan.popular && (
                <span className="bg-purple-500 text-white px-3 py-1 tracking-widest text-xs absolute right-0 top-0 rounded-bl">
                  {__('POPULAR', 'ultimate-spin-wheel')}
                </span>
              )}
              <div className="px-2 text-center h-48 flex flex-col items-center justify-center">
                <FontAwesomeIcon icon={plan.icon} className={`w-10 h-10 mb-2 ${plan.iconColor}`} />
                <h3 className={`tracking-widest ${plan.id === 'pro' ? 'text-purple-500' : 'text-gray-500 dark:text-gray-400'}`}>{plan.name}</h3>
                <h2 className="text-5xl text-gray-900 dark:text-white font-medium flex items-center justify-center leading-none mb-4 mt-2">
                  {plan.price}
                  {plan.interval && <span className="text-gray-600 dark:text-gray-400 text-base ml-1">{plan.interval}</span>}
                </h2>
                <span className="text-sm text-gray-600 dark:text-gray-400">{plan.description}</span>
              </div>

              {/* Feature rows */}
              {pricingFeatures.map((feature, featureIndex) => (
                <p key={`${plan.id}-feature-${featureIndex}`} className={`${featureIndex % 2 === 0 ? 'bg-gray-100 dark:bg-gray-800' : ''} text-gray-600 dark:text-gray-400 h-12 text-center px-2 flex items-center -mt-px justify-center ${featureIndex === 0 ? 'border-t border-gray-300 dark:border-gray-700' : ''}`}>
                  {plan.features[featureIndex].included ? (
                    <>
                      <FontAwesomeIcon icon={faCheck} className="text-green-500 mr-2" />
                      {plan.features[featureIndex].text}
                    </>
                  ) : (
                    <FontAwesomeIcon icon={faTimes} className="text-red-500 mr-2" />
                  )}
                </p>
              ))}

              <div className={`p-6 text-center ${plan.id !== 'pro' ? 'border-t border-gray-300 dark:border-gray-700' : ''}`}>
                {plan.id === 'free' ? (
                  <button className={`flex items-center mt-auto text-white ${plan.buttonColor} border-0 py-3 px-4 w-full focus:outline-none rounded justify-center`}>
                    {plan.buttonText}
                  </button>
                ) : (
                  <a href={plan.buttonLink} target="_blank" rel="noopener noreferrer"
                    className={`flex items-center mt-auto text-white ${plan.buttonGradient} border-0 py-3 px-4 w-full focus:outline-none hover:shadow-lg transition-all duration-300 rounded justify-center`}>
                    {plan.buttonText}
                    <FontAwesomeIcon icon={faArrowRight} className="w-4 h-4 ml-2" />
                  </a>
                )}
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-3">
                  {plan.smallText}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>

    <div className="py-8 text-center">
      <div className="mb-4 text-gray-700 dark:text-gray-300">
        {__('Join 10,000+ web professionals who already upgraded', 'ultimate-spin-wheel')}
      </div>
      <a target="_blank" href="https://smartcart.com/pricing/" rel="noopener noreferrer"
        className="text-white focus:ring-4 focus:outline-none focus:ring-[#3b5998]/50 font-medium rounded-lg text-lg px-10 py-5 text-center inline-flex items-center dark:focus:ring-[#3b5998]/55 bg-gradient-to-br from-pink-500 to-purple-700 hover:bg-gradient-to-bl hover:shadow-lg transition-all duration-300">
        <FontAwesomeIcon icon={faArrowRight} className="w-5 h-5 me-2" />
        {__('Start Your 14-Day Free Trial', 'ultimate-spin-wheel')}
      </a>
      <div className="mt-4 text-sm text-gray-500 dark:text-gray-400">
        {__('Full access to all premium features.', 'ultimate-spin-wheel')}
      </div>
    </div>
  </section>
);

export default GetPro;
