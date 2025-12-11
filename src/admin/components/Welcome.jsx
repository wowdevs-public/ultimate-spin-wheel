import React, { useState, useContext } from "react";
import { __ } from "@wordpress/i18n";

import { AppContext } from "./includes/AppContext";

import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
  faPlay,
  faChevronRight,
} from "@fortawesome/free-solid-svg-icons";

const Welcome = () => {
  // Enhanced testimonials with more detailed info
  const testimonials = [
    {
      name: 'Llia Borzoo',
      role: 'CEO & Founder',
      avatar: `${USPIN_CONFIG_ADMIN.assets_url}images/22323.png`,
      content: 'I\'m Ilia Borzoo, and I\'m doing website design. I think I had a chance to find, the widget and futures are lovely, but the essential thing in TechFyd is support; they are 7/24 online support, and any time I have any issues, they solve them fast for me...',
      stars: 5,
      project: 'E-commerce Fashion Site'
    },
    {
      name: 'Carl J. Massey',
      role: 'Software Engineer',
      avatar: `${USPIN_CONFIG_ADMIN.assets_url}images/644f48667.png`,
      content: 'is a plugin that allows you to add beautiful widgets to your WordPress site. The widgets are fully customizable and come with a variety of options. The customer service is top notch and the plugin is very easy to use. I highly recommend to anyone looking for an easy way to design their WordPress site.',
      stars: 5,
      project: 'Corporate Websites'
    },
    {
      name: 'Michael Oluwayimika',
      role: 'Founder of Movi.T S.',
      avatar: `${USPIN_CONFIG_ADMIN.assets_url}images/83043014.jpg`,
      content: 'gives me all the elements or addons I require to design an elegant, responsive website. The customization of each addons is simplified.',
      stars: 5,
      project: 'Portfolio & Blog Sites'
    },
  ];

  return (
    <div className="bg-white dark:bg-gray-900">
      {/* Hero Section - Without animations */}
      <section className="ultimate-spin-wheel-welcome-hero-bg rounded-md overflow-hidden relative">
        <div
          className="ultimate-spin-wheel-welcome-hero-overlay text-center lg:text-left shadow-sm sm:p-8"
          style={{
            backgroundImage: `url("${USPIN_CONFIG_ADMIN.assets_url}images/rocket-bg.jpg")`,
            backgroundSize: "cover",
            backgroundPosition: "center right"
          }}
        >
        </div>
        <div className="absolute w-100 top-0 left-0 right-0 bottom-0 flex flex-col justify-center items-left p-8">
          <h4 className="font-bold text-white dark:text-white mb-2">
            {__('Trusted by 2,000+ Designers', 'ultimate-spin-wheel')}
          </h4>
          <h3 className="text-5xl font-bold text-white dark:text-white">
            Powerful Addons for Elementor
          </h3>
          <p className="mt-4 text-base text-white dark:text-gray-300">
            Power to create stunning websites with one-click. Create beautiful, mobile-ready sites in minutes.
          </p>
          <div className="mt-6">
            <a
              href="https://smartcart.com/pricing/"
              target="_blank"
              className="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-[#e0528d] hover:bg-opacity-80 rounded-lg shadow-lg transition duration-300"
            >
              {__('Get Started', 'ultimate-spin-wheel')}
              <FontAwesomeIcon icon={faChevronRight} className="ml-2" />
            </a>
          </div>
        </div>
      </section>

      <section className="bg-white dark:bg-gray-900 my-16">
        <div className="px-4 mx-auto text-center lg:py-8 lg:px-6">
          <dl className="grid max-w-screen-md gap-8 mx-auto text-gray-900 grid-cols-2 md:grid-cols-4 dark:text-white">
            <div className="flex flex-col items-center justify-center">
              <dt className="mb-2 text-3xl md:text-4xl font-extrabold">105+</dt>
              <dd className="font-medium text-gray-500 dark:text-gray-400">
                Widgets & Features
              </dd>
            </div>
            <div className="flex flex-col items-center justify-center">
              <dt className="mb-2 text-3xl md:text-4xl font-extrabold">210+</dt>
              <dd className="font-medium text-gray-500 dark:text-gray-400">
                Ready templates
              </dd>
            </div>
            <div className="flex flex-col items-center justify-center">
              <dt className="mb-2 text-3xl md:text-4xl font-extrabold">2,000+</dt>
              <dd className="font-medium text-gray-500 dark:text-gray-400">
                Sections & Blocks
              </dd>
            </div>
            <div className="flex flex-col items-center justify-center">
              <dt className="mb-2 text-3xl md:text-4xl font-extrabold">24/7</dt>
              <dd className="font-medium text-gray-500 dark:text-gray-400">
                Customer support
              </dd>
            </div>
          </dl>
        </div>
      </section>
      <section className="bg-white dark:bg-gray-900 px-6 mt-4">
        <div className="grid max-w-screen-xl px-4 py-8 mx-auto lg:gap-8 xl:gap-8 lg:py-8 lg:grid-cols-12">
          <div className="mr-auto place-self-center lg:col-span-6">
            <h2 className="mb-4 text-4xl tracking-tight font-extrabold text-gray-900 dark:text-white">
              {__('Supercharge Your Design Workflow in Minutes', 'ultimate-spin-wheel')}
            </h2>
            <p className="mb-6 font-light text-gray-500 md:text-lg dark:text-gray-400">
              {__('A transforms Elementor with 105+ premium widgets and features that let you build any design you can imagine. Craft stunning, high-converting websites in half the time with our intuitive, professionally-designed components.', 'ultimate-spin-wheel')}
            </p>
          </div>
          <div className="lg:mt-0 lg:col-span-6 lg:flex rounded-lg overflow-hidden">
            <iframe width="100%" height="380" src="https://www.youtube.com/embed/wSIDsoSKK1c?si=_aGVrMG0NCnMLhOA" title="YouTube video player" frameBorder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerPolicy="strict-origin-when-cross-origin" allowFullScreen></iframe>
          </div>
        </div>
      </section>
      <section className="bg-white dark:bg-gray-900 px-6 mt-4">
        <div className="grid max-w-screen-xl px-4 py-8 mx-auto lg:gap-8 xl:gap-8 lg:py-8 lg:grid-cols-12">
          <div className="lg:mt-0 lg:col-span-6 lg:flex rounded-lg overflow-hidden">
            <iframe width="100%" height="380" src="https://www.youtube.com/embed/uy4UfxryuZ0?si=_FjNFwq1rQeP1bfI" title="YouTube video player" frameBorder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerPolicy="strict-origin-when-cross-origin" allowFullScreen></iframe>
          </div>
          <div className="mr-auto place-self-center lg:col-span-6">
            <h2 className="mb-4 text-4xl tracking-tight font-extrabold text-gray-900 dark:text-white">
              Clone Entire Sites with One-Click Magic
            </h2>
            <p className="mb-6 font-light text-gray-500 md:text-lg dark:text-gray-400">
              Our revolutionary Cross-Domain Copy feature lets you instantly transfer complete designs between websites with perfect accuracy. Reduce development time by 70% while ensuring pixel-perfect consistency across all your client projects.
            </p>
          </div>
        </div>
      </section>
      <section className="bg-white dark:bg-gray-900 px-6 mt-4">
        <div className="grid max-w-screen-xl px-4 py-8 mx-auto lg:gap-8 xl:gap-8 lg:py-8 lg:grid-cols-12">
          <div className="mr-auto place-self-center lg:col-span-6">
            <h2 className="mb-4 text-4xl tracking-tight font-extrabold text-gray-900 dark:text-white">
              {__('Design Every Part of Your Site with Our Theme Builder', 'ultimate-spin-wheel')}
            </h2>
            <p className="mb-6 font-light text-gray-500 md:text-lg dark:text-gray-400">
              {__('Our Theme Builder lets you design every part of your website with custom headers, footers, archive pages, single post templates, and dynamic hooks. Create a cohesive design system that loads up to 40% faster than standard themes, improving both user experience and search rankings.', 'ultimate-spin-wheel')}
            </p>
          </div>
          <div className="lg:mt-0 lg:col-span-6 lg:flex rounded-lg overflow-hidden">
            <iframe width="100%" height="380" src="https://www.youtube.com/embed/O-cjoOjLSPg?si=jCwCHuLnkTVWg2eI" title={__('YouTube video player', 'ultimate-spin-wheel')} frameBorder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerPolicy="strict-origin-when-cross-origin" allowFullScreen></iframe>
          </div>
        </div>
      </section>
      <section className="bg-white dark:bg-gray-900 py-8">
        <div className="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <span className="bg-purple-100 text-purple-800 text-sm font-medium py-1 px-3 rounded-full dark:bg-purple-900 dark:text-purple-300">
              {__('WHY US', 'ultimate-spin-wheel')}
            </span>
            <h4 className="text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl mt-4">
              {__('Transform Your Website Building Experience', 'ultimate-spin-wheel')}
            </h4>
            <p className="mt-6 text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
              {__('Unleash your creativity with powerful tools designed for professionals who want to create without limits.', 'ultimate-spin-wheel')}
            </p>
          </div>

          <div className="flex flex-wrap my-12">


            <div className="w-full border-b md:w-1/2 md:border-r lg:w-1/3 p-8">
              <div className="flex items-center mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20"
                  fill="currentColor" className="h-6 w-6 text-indigo-500">
                  <path
                    d="M16 3C8.8 3 3 8.8 3 16s5.8 13 13 13 13-5.8 13-13c0-1.398-.188-2.793-.688-4.094L26.688 13.5c.2.8.313 1.602.313 2.5 0 6.102-4.898 11-11 11S5 22.102 5 16 9.898 5 16 5c3 0 5.695 1.195 7.594 3.094L25 6.688C22.7 4.386 19.5 3 16 3zm11.281 4.281L16 18.563l-4.281-4.282-1.438 1.438 5 5 .719.687.719-.687 12-12z">
                  </path>
                </svg>
                <div className="ml-4 text-xl">{__('Boost Revenue', 'ultimate-spin-wheel')}</div>
              </div>
              <p className="leading-loose text-gray-500">
                {__('Create websites that drive more leads, engage visitors longer, and increase client satisfaction, ultimately boosting your bottom line by up to 35%.', 'ultimate-spin-wheel')}
              </p>
            </div>

            <div className="w-full border-b md:w-1/2 lg:w-1/3 lg:border-r p-8">
              <div className="flex items-center mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20"
                  fill="currentColor" className="h-6 w-6 text-indigo-500">
                  <path
                    d="M16 3C8.8 3 3 8.8 3 16s5.8 13 13 13 13-5.8 13-13c0-1.398-.188-2.793-.688-4.094L26.688 13.5c.2.8.313 1.602.313 2.5 0 6.102-4.898 11-11 11S5 22.102 5 16 9.898 5 16 5c3 0 5.695 1.195 7.594 3.094L25 6.688C22.7 4.386 19.5 3 16 3zm11.281 4.281L16 18.563l-4.281-4.282-1.438 1.438 5 5 .719.687.719-.687 12-12z">
                  </path>
                </svg>
                <div className="ml-4 text-xl">{__('Boost Conversions', 'ultimate-spin-wheel')}</div>
              </div>
              <p className="leading-loose text-gray-500">
                {__('Create irresistible website elements that capture attention and drive user action, increasing your conversion rates by up to 30%.', 'ultimate-spin-wheel')}
              </p>
            </div>

            <div className="w-full border-b md:w-1/2 md:border-r lg:w-1/3 lg:border-r-0 p-8">
              <div className="flex items-center mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20"
                  fill="currentColor" className="h-6 w-6 text-indigo-500">
                  <path
                    d="M16 3C8.8 3 3 8.8 3 16s5.8 13 13 13 13-5.8 13-13c0-1.398-.188-2.793-.688-4.094L26.688 13.5c.2.8.313 1.602.313 2.5 0 6.102-4.898 11-11 11S5 22.102 5 16 9.898 5 16 5c3 0 5.695 1.195 7.594 3.094L25 6.688C22.7 4.386 19.5 3 16 3zm11.281 4.281L16 18.563l-4.281-4.282-1.438 1.438 5 5 .719.687.719-.687 12-12z">
                  </path>
                </svg>
                <div className="ml-4 text-xl">{__('Agency-Scale Tools', 'ultimate-spin-wheel')}</div>
              </div>
              <p className="leading-loose text-gray-500">
                {__('Manage unlimited client websites with enterprise features designed for agencies - batch updates, white labeling, and client reporting built right in.', 'ultimate-spin-wheel')}
              </p>
            </div>

            <div className="w-full border-b md:w-1/2 lg:w-1/3 lg:border-r lg:border-b-0 p-8">
              <div className="flex items-center mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20"
                  fill="currentColor" className="h-6 w-6 text-indigo-500">
                  <path
                    d="M16 3C8.8 3 3 8.8 3 16s5.8 13 13 13 13-5.8 13-13c0-1.398-.188-2.793-.688-4.094L26.688 13.5c.2.8.313 1.602.313 2.5 0 6.102-4.898 11-11 11S5 22.102 5 16 9.898 5 16 5c3 0 5.695 1.195 7.594 3.094L25 6.688C22.7 4.386 19.5 3 16 3zm11.281 4.281L16 18.563l-4.281-4.282-1.438 1.438 5 5 .719.687.719-.687 12-12z">
                  </path>
                </svg>
                <div className="ml-4 text-xl">{__('Future-Proof Design', 'ultimate-spin-wheel')}</div>
              </div>
              <p className="leading-loose text-gray-500">
                {__('Stay ahead with regular updates that introduce cutting-edge web design trends and performance optimizations before your competitors can adapt.', 'ultimate-spin-wheel')}
              </p>
            </div>

            <div className="w-full border-b md:w-1/2 md:border-r md:border-b-0 lg:w-1/3 lg:border-b-0 p-8">
              <div className="flex items-center mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20"
                  fill="currentColor" className="h-6 w-6 text-indigo-500">
                  <path
                    d="M16 3C8.8 3 3 8.8 3 16s5.8 13 13 13 13-5.8 13-13c0-1.398-.188-2.793-.688-4.094L26.688 13.5c.2.8.313 1.602.313 2.5 0 6.102-4.898 11-11 11S5 22.102 5 16 9.898 5 16 5c3 0 5.695 1.195 7.594 3.094L25 6.688C22.7 4.386 19.5 3 16 3zm11.281 4.281L16 18.563l-4.281-4.282-1.438 1.438 5 5 .719.687.719-.687 12-12z">
                  </path>
                </svg>
                <div className="ml-4 text-xl">{__('Performance-Focused', 'ultimate-spin-wheel')}</div>
              </div>
              <p className="leading-loose text-gray-500">
                {__('Create blazing-fast websites with our optimized code that scores 90+ on PageSpeed Insights, even with complex, feature-rich designs.', 'ultimate-spin-wheel')}
              </p>
            </div>

            <div className="w-full md:w-1/2 lg:w-1/3 p-8">
              <div className="flex items-center mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20"
                  fill="currentColor" className="h-6 w-6 text-indigo-500">
                  <path
                    d="M16 3C8.8 3 3 8.8 3 16s5.8 13 13 13 13-5.8 13-13c0-1.398-.188-2.793-.688-4.094L26.688 13.5c.2.8.313 1.602.313 2.5 0 6.102-4.898 11-11 11S5 22.102 5 16 9.898 5 16 5c3 0 5.695 1.195 7.594 3.094L25 6.688C22.7 4.386 19.5 3 16 3zm11.281 4.281L16 18.563l-4.281-4.282-1.438 1.438 5 5 .719.687.719-.687 12-12z">
                  </path>
                </svg>
                <div className="ml-4 text-xl">{__('Seamless Ecosystem', 'ultimate-spin-wheel')}</div>
              </div>
              <p className="leading-loose text-gray-500">
                {__('Enjoy perfect compatibility with popular WordPress plugins and themes. Our elements integrate flawlessly with WooCommerce, WPML, ACF and more.', 'ultimate-spin-wheel')}
              </p>
            </div>

          </div>
        </div>
      </section>


      {/* Features Section - Without animations */}
      <section className="bg-white dark:bg-gray-900 py-8">
        <div className="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <span className="bg-purple-100 text-purple-800 text-sm font-medium py-1 px-3 rounded-full dark:bg-purple-900 dark:text-purple-300">
              {__('RESOURCES', 'ultimate-spin-wheel')}
            </span>
            <h4 className="text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl mt-4">
              {__('Resources & Support', 'ultimate-spin-wheel')}
            </h4>
            <p className="mt-6 text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
              {__('Everything you need to succeed with Sky Elementor Addons', 'ultimate-spin-wheel')}
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="bg-blue-50 p-6 rounded-lg shadow-sm">
              <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 className="text-lg font-bold mb-2">Knowledge Hub</h3>
              <p className="text-gray-700 mb-4">Access detailed tutorials, documentation and best practices to make the most of Sky Elementor Addons.</p>
              <a
                href="https://wowdevs.com/docs/"
                target="_blank"
                className="text-blue-700 hover:text-blue-800 font-medium inline-flex items-center">
                Browse Tutorials
                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                </svg>
              </a>
            </div>
            <div className="bg-green-50 p-6 rounded-lg shadow-sm">
              <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <h3 className="text-lg font-bold mb-2">Premium Support</h3>
              <p className="text-gray-700 mb-4">Get priority assistance from our expert team whenever you have questions or need help with your projects.</p>
              <a
                href="https://wowdevs.com/support/"
                target="_blank"
                className="text-green-700 hover:text-green-800 font-medium inline-flex items-center">
                Contact Support
                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                </svg>
              </a>
            </div>
            <div className="bg-purple-50 p-6 rounded-lg shadow-sm">
              <div className="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-purple-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
              </div>
              <h3 className="text-lg font-bold mb-2">Ready-Made Templates</h3>
              <p className="text-gray-700 mb-4">Jump-start your projects with our extensive library of professionally designed templates for any industry or niche.</p>
              <a
                href="https://smartcart.com/elementor-templates/"
                target="_blank"
                className="text-purple-700 hover:text-purple-800 font-medium inline-flex items-center">
                Explore Templates
                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </section>

      {/* Testimonials Section - Without animations */}
      <section className="py-8">
        <div className="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <span className="bg-purple-100 text-purple-800 text-sm font-medium py-1 px-3 rounded-full dark:bg-purple-900 dark:text-purple-300">
              {__('TESTIMONIALS', 'ultimate-spin-wheel-elementor_addons')}
            </span>
            <h4 className="text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl mt-4">
              {__('What Our Users Say', 'ultimate-spin-wheel')}
            </h4>
            <p className="mt-6 text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
              {__('Join thousands of satisfied web designers and developers already using Sky Elementor Addons', 'ultimate-spin-wheel')}
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {testimonials.map((testimonial, index) => (
              <div key={index}
                className="max-w-md w-full bg-gradient-to-br from-purple-600 to-blue-500 rounded-2xl shadow-2xl overflow-hidden transform hover:scale-105 transition-transform duration-300">
                <div className="p-6 sm:p-8">
                  <div className="flex justify-between items-center mb-4">
                    <div className="flex items-center space-x-2">
                      {/* Generate stars dynamically based on testimonial rating */}
                      {Array.from({ length: testimonial.stars }).map((_, i) => (
                        <svg key={i} className="w-5 h-5 text-yellow-400 fill-current" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                          viewBox="0 0 20 20">
                          <path d="M10 15.27L16.18 20l-1.64-7.03L20 8.24l-7.19-.61L10 1 7.19 7.63 0 8.24l5.46 4.73L3.82 20z" />
                        </svg>
                      ))}
                    </div>
                    <span className="text-white text-sm font-semibold">{__('Verified Customer', 'ultimate-spin-wheel')}</span>
                  </div>
                  <blockquote className="text-white text-xl font-medium mb-6">
                    "{testimonial.content}"
                  </blockquote>
                  <div className="flex items-center space-x-4">
                    {/* <img src={testimonial.avatar} alt={`${testimonial.name} avatar`} className="w-12 h-12 rounded-full border-2 border-white" /> */}
                    <div>
                      <p className="text-white font-semibold">{testimonial.name}</p>
                      <p className="text-blue-200 text-sm">{testimonial.role}</p>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Call to Action - Without animations */}
      <section className="bg-gradient-to-br from-purple-600 to-indigo-800 py-8 rounded-3xl shadow-2xl relative overflow-hidden mt-8">
        {/* Static decorative elements instead of animated ones */}
        <div className="absolute top-10 left-10 w-32 h-32 rounded-full bg-white opacity-10"></div>
        <div className="absolute bottom-10 right-10 w-24 h-24 rounded-full bg-white opacity-10"></div>
        <div className="absolute top-1/2 right-1/4 w-16 h-16 rounded-full bg-white opacity-10"></div>

        <div className="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
          <div className="inline-block p-1 bg-purple-700 bg-opacity-50 rounded-full mb-6">
            <span className="bg-white text-purple-800 text-sm font-medium py-1 px-4 rounded-full">
              {__('GET STARTED TODAY', 'ultimate-spin-wheel')}
            </span>
          </div>
          <h4 className="text-3xl font-extrabold text-white sm:text-4xl mb-6">
            {__('Ready to Transform Your Website?', 'ultimate-spin-wheel-elementor_addons')}
          </h4>
          <p className="mt-4 text-xl text-purple-100 max-w-2xl mx-auto mb-10">
            {__('Join thousands of web professionals already using Sky Elementor Addons to create stunning websites that convert.', 'ultimate-spin-wheel-elementor_addons')}
          </p>
          <div className="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6">
            <a
              href="https://smartcart.com/pricing/"
              target="_blank"
              className="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-purple-700 bg-white border border-transparent rounded-xl shadow-md hover:bg-purple-50 transition-all duration-300 hover:bg-opacity-80"
            >
              {__('Get Started Now', 'ultimate-spin-wheel')}
            </a>
            <a
              href="https://smartcart.com/elementor-widgets/"
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-white bg-[#e0528d] border border-[#e0528d] border-opacity-25 rounded-xl shadow-md hover:bg-opacity-80 transition-all duration-300"
            >
              {__('View Demo', 'ultimate-spin-wheel')}
              <FontAwesomeIcon icon={faPlay} className="ml-2 w-4 h-4" />
            </a>
          </div>
        </div>
      </section>
    </div>
  );
};

export default Welcome;
