import React, { useState, useEffect } from 'react';
import { __ } from "@wordpress/i18n";
import { PanelBody, SelectControl, RangeControl, TextControl, ToggleControl } from '@wordpress/components';
import Sticky from "react-sticky-el";
import '../style/spin-wheel.scss';

const ColorPicker = ({ value, onChange, title }) => (
  <input
    type="color"
    value={value}
    onChange={onChange}
    className="w-12 h-12 cursor-pointer"
    title={title}
  />
);

const Preview = ({ segments, customDesign, setCustomDesign }) => {
  const [previewMode, setPreviewMode] = useState('wheel'); // 'wheel', 'prize', 'lost'

  console.log('customDesign:', customDesign);

  // Parse JSON string if needed, otherwise use object directly
  const currentDesign = (() => {
    if (typeof customDesign === 'string') {
      try {
        return JSON.parse(customDesign);
      } catch (error) {
        console.error('Error parsing customDesign JSON:', error);
        return {};
      }
    }
    return customDesign || {};
  })();

  // Generate and inject custom CSS
  const generateCustomCSS = (custom_design) => {
    const id = 'ultimate-spin-wheel-preview';
    const css = `
      /* Spin Wheel Custom Styles */
      #${id} {
        --panel-bg-color: ${custom_design.viewPanel?.backgroundColor || '#16a085'};
        --spin-btn-color: ${custom_design.spinButton?.color || '#fff'};
        --spin-btn-bg: ${custom_design.spinButton?.backgroundColor || '#16a085'};
        --spin-btn-hover: ${custom_design.spinButton?.backgroundColorColorHover || '#139cb2'};
        --spin-btn-font-size: ${custom_design.spinButton?.fontSize || '16px'};
        --form-title-color: ${custom_design.formTitle?.color || '#fff'};
        --form-title-size: ${custom_design.formTitle?.fontSize || '24px'};
        --form-title-weight: ${custom_design.formTitle?.fontWeight || 'normal'};
        --form-submit-color: ${custom_design.formSubmitButton?.color || '#0a0a0a'};
        --form-submit-bg: ${custom_design.formSubmitButton?.backgroundColor || '#fff'};
        --form-submit-hover: ${custom_design.formSubmitButton?.hoverColor || '#f0f0f0'};
        --form-submit-size: ${custom_design.formSubmitButton?.fontSize || '16px'};
        --form-submit-radius: ${custom_design.formSubmitButton?.borderRadius || 5}px;
        --input-border: ${custom_design.formInputs?.borderColor || '#ccc'};
        --input-focus: ${custom_design.formInputs?.focusColor || '#16a085'};
        --privacy-color: ${custom_design.privacyText?.color || '#fff'};
        --privacy-size: ${custom_design.privacyText?.fontSize || '12px'};
        --prize-title-color: ${custom_design.prizeWonTitle?.color || '#fff'};
        --prize-title-size: ${custom_design.prizeWonTitle?.fontSize || '24px'};
        --prize-msg-color: ${custom_design.prizeWonMsg?.color || '#fff'};
        --prize-msg-size: ${custom_design.prizeWonMsg?.fontSize || '28px'};
        --lost-title-color: ${custom_design.prizeLostTitle?.color || '#fff'};
        --lost-title-size: ${custom_design.prizeLostTitle?.fontSize || '24px'};
        --coupon-btn-color: ${custom_design.couponButton?.color || '#fff'};
        --coupon-btn-bg: ${custom_design.couponButton?.backgroundColor || '#16a085'};
        --coupon-btn-hover: ${custom_design.couponButton?.hoverColor || '#139cb2'};
        --coupon-btn-radius: ${custom_design.couponButton?.borderRadius || 5}px;
        --wheel-lost-color: ${custom_design.wheel?.lostColor || '#ccc'};
        --wheel-border-color: ${custom_design.wheel?.borderColor || '#fff'};
        --wheel-border-width: ${custom_design.wheel?.borderWidth || 12}px;
      }
    `;
    return css;
  };

  // Inject CSS into head
  const injectCustomStyles = () => {
    // Remove existing style tag if it exists
    const existingStyle = document.getElementById('ultimate-spin-wheel-preview-styles');
    if (existingStyle) {
      existingStyle.remove();
    }

    // Create new style tag
    const styleTag = document.createElement('style');
    styleTag.id = 'ultimate-spin-wheel-preview-styles';
    styleTag.type = 'text/css';

    // Generate CSS based on design configuration
    const css = generateCustomCSS(currentDesign);

    if (styleTag.styleSheet) {
      // IE support
      styleTag.styleSheet.cssText = css;
    } else {
      styleTag.appendChild(document.createTextNode(css));
    }

    // Append to head
    document.head.appendChild(styleTag);
  };

  // Inject styles when design changes
  useEffect(() => {
    injectCustomStyles();

    // Cleanup function to remove styles when component unmounts
    return () => {
      const existingStyle = document.getElementById('ultimate-spin-wheel-preview-styles');
      if (existingStyle) {
        existingStyle.remove();
      }
    };
  }, [currentDesign]);

  // Update design function for nested object structure
  const updateDesign = (section, key, value) => {
    const newDesign = {
      ...currentDesign,
      [section]: {
        ...currentDesign[section],
        [key]: value
      }
    };

    // Convert back to JSON string if parent expects string format
    if (typeof customDesign === 'string') {
      setCustomDesign(JSON.stringify(newDesign));
    } else {
      setCustomDesign(newDesign);
    }
  };

  const generateAreas = () => {
    const areas = [];
    const totalAreas = segments.length * 2; // Each segment has winner and lost area
    const skewVal = 90 - (360 / totalAreas);
    const rotateVal = (360 / totalAreas) / 2;

    segments.forEach((coupon, index) => {
      const winnerIndex = index * 2;
      const loserIndex = index * 2 + 1;

      // Winner area
      areas.push(
        <div
          key={`win-${index}`}
          className="area"
          data-wheel-bg={coupon.color}
          data-wheel-prize="wins"
          data-coupon-code={coupon.code || ''}
          data-wheel-message={coupon.label}
          style={{
            transform: `rotate(${(360 / totalAreas) * winnerIndex}deg) skewY(-${skewVal}deg)`
          }}
        >
          <span style={{
            background: coupon.color,
            transform: `skewY(${skewVal}deg) rotate(${rotateVal}deg)`
          }}>
            {coupon.label}
          </span>
        </div>
      );

      // Lost area
      areas.push(
        <div
          key={`lost-${index}`}
          className="area"
          data-wheel-bg={coupon.lost?.color || currentDesign.wheel?.lostColor || '#ccc'}
          data-wheel-prize="lost"
          data-wheel-message={coupon.lost?.label || currentDesign.prizeLostTitle?.text || 'Better luck next time!'}
          style={{
            transform: `rotate(${(360 / totalAreas) * loserIndex}deg) skewY(-${skewVal}deg)`
          }}
        >
          <span style={{
            background: coupon.lost?.color || currentDesign.wheel?.lostColor || '#ccc',
            transform: `skewY(${skewVal}deg) rotate(${rotateVal}deg)`
          }}>
          </span>
        </div>
      );
    });
    return areas;
  };

  // Preview Mode Tabs
  const PreviewTabs = () => (
    <div className="preview-tabs text-center mb-10">
      <button
        className={`px-4 py-2 mr-2 rounded ${previewMode === 'wheel' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
        onClick={() => setPreviewMode('wheel')}
      >
        {__('Wheel View', 'ultimate-spin-wheel')}
      </button>
      <button
        className={`px-4 py-2 mr-2 rounded ${previewMode === 'submit' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
        onClick={() => setPreviewMode('submit')}
      >
        {__('Form Panel', 'ultimate-spin-wheel')}
      </button>
      <button
        className={`px-4 py-2 mr-2 rounded ${previewMode === 'prize' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
        onClick={() => setPreviewMode('prize')}
      >
        {__('Prize Panel', 'ultimate-spin-wheel')}
      </button>
      <button
        className={`px-4 py-2 rounded ${previewMode === 'lost' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
        onClick={() => setPreviewMode('lost')}
      >
        {__('Lost Panel', 'ultimate-spin-wheel')}
      </button>
    </div>
  );

  // Single Unified Preview Component
  const UnifiedPreview = () => (
    <div id="ultimate-spin-wheel-preview" className={`spinWheel text-center ${previewMode === 'wheel' ? 'active' : ''} ${previewMode === 'prize' ? 'active' : ''} ${previewMode === 'lost' ? 'active' : ''}`}>
      <div className="wheelWrap">
        <div className="wheel" data-spin-circles="6">
          {generateAreas()}
        </div>

        {/* Form Wrapper - Active only for wheel view */}
        <div
          className={`sc-form-wrap ${previewMode === 'submit' ? 'active' : ''}`}
          style={{ opacity: previewMode === 'submit' ? 1 : 0 }}
        >
          <div className="title">
            {currentDesign.formTitle?.text || "Let's try luck!"}
          </div>
          <form className="sc-spin-form">
            <input
              type="text"
              name="name"
              placeholder={currentDesign.formInputs?.namePlaceholder || 'Enter your name'}
            />
            <input
              type="email"
              name="email"
              placeholder={currentDesign.formInputs?.emailPlaceholder || 'Enter your email'}
              required
            />
            <button type="submit" className="spin">
              {currentDesign.formSubmitButton?.text || 'Spin the Wheel'}
            </button>
          </form>
          <a
            className="sc-small"
            href={currentDesign.privacyText?.url || 'javascript:void(0);'}
          >
            {currentDesign.privacyText?.text || 'Privacy & Policy'}
          </a>
        </div>

        {/* Message Wrapper - Active for prize and lost views */}
        <div
          className={`msg ${previewMode === 'prize' ? 'active' : ''} ${previewMode === 'lost' ? 'active' : ''}`}
          style={{ opacity: (previewMode === 'prize' || previewMode === 'lost') ? 1 : 0 }}
        >
          <div className="title">
            <span>
              {previewMode === 'prize' ? (currentDesign.prizeWonTitle?.text || 'Congratulations!') : (currentDesign.prizeLostTitle?.text || 'Oops!')}
            </span>
          </div>
          <div className="prizeMsg">
            {previewMode === 'prize'
              ? (currentDesign.prizeWonMsg?.text || 'You won a {{discount_label}} discount!').replace('{{discount_label}}', '10%')
              : currentDesign.prizeLostTitle?.text || 'Better luck next time!'
            }
          </div>
          <div className={`sc-btn sc-coupon ${previewMode === 'prize' ? 'active' : ''} ${previewMode === 'lost' ? 'active' : ''}`}>
            {previewMode === 'prize'
              ? (currentDesign.couponButton?.winText || 'Start shopping!')
              : (currentDesign.couponButton?.lostText || 'Try Again')
            }
          </div>
          <a
            className="sc-small"
            href={currentDesign.privacyText?.url || 'javascript:void(0);'}
          >
            {currentDesign.privacyText?.text || 'Privacy & Policy'}
          </a>
        </div>

        {/* Start Button - Active classes based on preview mode */}
        <div
          className={`start ${previewMode === 'wheel' ? 'active' : ''} ${previewMode === 'prize' ? 'active' : ''} ${previewMode === 'lost' ? 'active' : ''}`}
          data-wheel-lost-text="Go again?"
        >
          {previewMode === 'wheel'
            ? currentDesign.spinButton?.text || 'GO!'
            : previewMode === 'lost'
              ? 'Go again?'
              : currentDesign.spinButton?.text || 'GO!'
          }
        </div>
        <div className="marker"></div>
      </div>
    </div>
  );

  return (
    <div className="ultimate-spin-wheel">
      <div className='flex gap-8 p-4'>
        <div className='min-w-[400px]'>

          {/* ===== GENERAL SETTINGS ===== */}
          <div className="mb-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">{__('General Settings', 'ultimate-spin-wheel')}</h3>

            {/* Panel Background */}
            <PanelBody title={__('Panel Background')} initialOpen={false} className="flex gap-4 flex-col">
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Background Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose panel background color"
                  value={currentDesign.viewPanel?.backgroundColor || '#16a085'}
                  onChange={(e) => updateDesign('viewPanel', 'backgroundColor', e.target.value)}
                />
              </div>
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Border Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose wheel border color"
                  value={currentDesign.wheel?.borderColor || '#fff'}
                  onChange={(e) => updateDesign('wheel', 'borderColor', e.target.value)}
                />
              </div>
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Border Width', 'ultimate-spin-wheel')}</label>
                <RangeControl
                  value={currentDesign.wheel?.borderWidth || 12}
                  onChange={(value) => updateDesign('wheel', 'borderWidth', value)}
                  min={0}
                  max={40}
                  help={__('Border width in pixels', 'ultimate-spin-wheel')}
                />
              </div>
            </PanelBody>

            {/* Privacy Text */}
            <PanelBody title={__('Privacy Text')} initialOpen={false} className="flex gap-4 flex-col">
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Privacy Text', 'ultimate-spin-wheel')}
                value={currentDesign.privacyText?.text || 'Privacy & Policy'}
                onChange={(value) => updateDesign('privacyText', 'text', value)}
                help={__('Privacy policy text.', 'ultimate-spin-wheel')}
              />
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Text Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose privacy text color"
                  value={currentDesign.privacyText?.color || '#fff'}
                  onChange={(e) => updateDesign('privacyText', 'color', e.target.value)}
                />
              </div>
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Font Size', 'ultimate-spin-wheel')}
                value={currentDesign.privacyText?.fontSize || '12px'}
                onChange={(value) => updateDesign('privacyText', 'fontSize', value)}
                help={__('Font size for privacy text (e.g., 12px, 0.8em).', 'ultimate-spin-wheel')}
              />
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Privacy Link URL', 'ultimate-spin-wheel')}
                value={currentDesign.privacyText?.url || '#'}
                onChange={(value) => updateDesign('privacyText', 'url', value)}
                help={__('URL for privacy policy link.', 'ultimate-spin-wheel')}
              />
            </PanelBody>
          </div>

          {/* ===== WHEEL SETTINGS ===== */}
          <div className="mb-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">{__('Wheel Settings', 'ultimate-spin-wheel')}</h3>

            {/* Spin Button */}
            <PanelBody title={__('Spin Button (Center)')} initialOpen={false} className="flex gap-4 flex-col">
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Button Text', 'ultimate-spin-wheel')}
                value={currentDesign.spinButton?.text || 'GO!'}
                onChange={(value) => updateDesign('spinButton', 'text', value)}
                help={__('Text displayed on the center spin button.', 'ultimate-spin-wheel')}
              />
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Text Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose text color"
                  value={currentDesign.spinButton?.color || '#fff'}
                  onChange={(e) => updateDesign('spinButton', 'color', e.target.value)}
                />
              </div>
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Background Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose background color"
                  value={currentDesign.spinButton?.backgroundColor || '#16a085'}
                  onChange={(e) => updateDesign('spinButton', 'backgroundColor', e.target.value)}
                />
              </div>
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Hover Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose hover color"
                  value={currentDesign.spinButton?.backgroundColorColorHover || '#139cb2'}
                  onChange={(e) => updateDesign('spinButton', 'backgroundColorColorHover', e.target.value)}
                />
              </div>
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Font Size', 'ultimate-spin-wheel')}
                value={currentDesign.spinButton?.fontSize || '16px'}
                onChange={(value) => updateDesign('spinButton', 'fontSize', value)}
                help={__('Font size for button text (e.g., 16px, 1em).', 'ultimate-spin-wheel')}
              />
            </PanelBody>
          </div>

          {/* ===== FORM SETTINGS ===== */}
          <div className="mb-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">{__('Form Settings', 'ultimate-spin-wheel')}</h3>

            {/* Form Title */}
            <PanelBody title={__('Form Title')} initialOpen={false} className="flex gap-4 flex-col">
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Title Text', 'ultimate-spin-wheel')}
                value={currentDesign.formTitle?.text || "Let's try luck!"}
                onChange={(value) => updateDesign('formTitle', 'text', value)}
                help={__('Main title text displayed on the form.', 'ultimate-spin-wheel')}
              />
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Text Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose title color"
                  value={currentDesign.formTitle?.color || '#fff'}
                  onChange={(e) => updateDesign('formTitle', 'color', e.target.value)}
                />
              </div>
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Font Size', 'ultimate-spin-wheel')}
                value={currentDesign.formTitle?.fontSize || '24px'}
                onChange={(value) => updateDesign('formTitle', 'fontSize', value)}
                help={__('Font size for the title (e.g., 24px, 1.5em).', 'ultimate-spin-wheel')}
              />
              <SelectControl
                label={__('Font Weight', 'ultimate-spin-wheel')}
                value={currentDesign.formTitle?.fontWeight || 'normal'}
                options={[
                  { label: 'Normal', value: 'normal' },
                  { label: 'Bold', value: 'bold' },
                  { label: 'Light', value: '300' },
                  { label: 'Semi Bold', value: '600' },
                  { label: 'Extra Bold', value: '800' }
                ]}
                onChange={(value) => updateDesign('formTitle', 'fontWeight', value)}
              />
            </PanelBody>

            {/* Form Submit Button */}
            <PanelBody title={__('Submit Button')} initialOpen={false} className="flex gap-4 flex-col">
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Button Text', 'ultimate-spin-wheel')}
                value={currentDesign.formSubmitButton?.text || 'Spin The Wheel'}
                onChange={(value) => updateDesign('formSubmitButton', 'text', value)}
                help={__('Text for the submit button.', 'ultimate-spin-wheel')}
              />
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Text Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose submit button text color"
                  value={currentDesign.formSubmitButton?.color || '#0a0a0a'}
                  onChange={(e) => updateDesign('formSubmitButton', 'color', e.target.value)}
                />
              </div>
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Background Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose submit button background color"
                  value={currentDesign.formSubmitButton?.backgroundColor || '#fff'}
                  onChange={(e) => updateDesign('formSubmitButton', 'backgroundColor', e.target.value)}
                />
              </div>
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Hover Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose submit button hover color"
                  value={currentDesign.formSubmitButton?.hoverColor || '#f0f0f0'}
                  onChange={(e) => updateDesign('formSubmitButton', 'hoverColor', e.target.value)}
                />
              </div>
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Font Size', 'ultimate-spin-wheel')}
                value={currentDesign.formSubmitButton?.fontSize || '16px'}
                onChange={(value) => updateDesign('formSubmitButton', 'fontSize', value)}
                help={__('Font size for button text (e.g., 16px, 1em).', 'ultimate-spin-wheel')}
              />
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Border Radius', 'ultimate-spin-wheel')}</label>
                <RangeControl
                  value={currentDesign.formSubmitButton?.borderRadius || 5}
                  onChange={(value) => updateDesign('formSubmitButton', 'borderRadius', value)}
                  min={0}
                  max={50}
                  help={__('Button border radius in pixels', 'ultimate-spin-wheel')}
                />
              </div>
            </PanelBody>

            {/* Form Input Fields */}
            <PanelBody title={__('Input Fields')} initialOpen={false} className="flex gap-4 flex-col">
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Name Placeholder', 'ultimate-spin-wheel')}
                value={currentDesign.formInputs?.namePlaceholder || 'Enter your name'}
                onChange={(value) => updateDesign('formInputs', 'namePlaceholder', value)}
              />
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Email Placeholder', 'ultimate-spin-wheel')}
                value={currentDesign.formInputs?.emailPlaceholder || 'Enter your email'}
                onChange={(value) => updateDesign('formInputs', 'emailPlaceholder', value)}
              />
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Border Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose input border color"
                  value={currentDesign.formInputs?.borderColor || '#ccc'}
                  onChange={(e) => updateDesign('formInputs', 'borderColor', e.target.value)}
                />
              </div>
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Focus Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose input focus color"
                  value={currentDesign.formInputs?.focusColor || '#16a085'}
                  onChange={(e) => updateDesign('formInputs', 'focusColor', e.target.value)}
                />
              </div>
            </PanelBody>
          </div>

          {/* ===== RESULT PANELS ===== */}
          <div className="mb-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">{__('Result Panels', 'ultimate-spin-wheel')}</h3>

            {/* Prize Won Panel */}
            <PanelBody title={__('Prize Won Panel')} initialOpen={false} className="flex gap-4 flex-col">
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Title Text', 'ultimate-spin-wheel')}
                value={currentDesign.prizeWonTitle?.text || "Congratulations!"}
                onChange={(value) => updateDesign('prizeWonTitle', 'text', value)}
                help={__('Title shown when user wins.', 'ultimate-spin-wheel')}
              />
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Title Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose title color"
                  value={currentDesign.prizeWonTitle?.color || '#fff'}
                  onChange={(e) => updateDesign('prizeWonTitle', 'color', e.target.value)}
                />
              </div>
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Title Font Size', 'ultimate-spin-wheel')}
                value={currentDesign.prizeWonTitle?.fontSize || '24px'}
                onChange={(value) => updateDesign('prizeWonTitle', 'fontSize', value)}
              />
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Prize Message', 'ultimate-spin-wheel')}
                value={currentDesign.prizeWonMsg?.text || 'You won a {{discount_label}} discount!'}
                onChange={(value) => updateDesign('prizeWonMsg', 'text', value)}
                help={__('Message shown when user wins. Use {{discount_label}} for dynamic content.', 'ultimate-spin-wheel')}
              />
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Message Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose prize message color"
                  value={currentDesign.prizeWonMsg?.color || '#fff'}
                  onChange={(e) => updateDesign('prizeWonMsg', 'color', e.target.value)}
                />
              </div>
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Message Font Size', 'ultimate-spin-wheel')}
                value={currentDesign.prizeWonMsg?.fontSize || '28px'}
                onChange={(value) => updateDesign('prizeWonMsg', 'fontSize', value)}
              />
            </PanelBody>

            {/* Lost Panel */}
            <PanelBody title={__('Lost Panel')} initialOpen={false} className="flex gap-4 flex-col">
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Title Text', 'ultimate-spin-wheel')}
                value={currentDesign.prizeLostTitle?.text || 'Better luck next time!'}
                onChange={(value) => updateDesign('prizeLostTitle', 'text', value)}
                help={__('Message shown when user loses.', 'ultimate-spin-wheel')}
              />
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Text Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose lost message color"
                  value={currentDesign.prizeLostTitle?.color || '#fff'}
                  onChange={(e) => updateDesign('prizeLostTitle', 'color', e.target.value)}
                />
              </div>
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Font Size', 'ultimate-spin-wheel')}
                value={currentDesign.prizeLostTitle?.fontSize || '24px'}
                onChange={(value) => updateDesign('prizeLostTitle', 'fontSize', value)}
              />
            </PanelBody>

            {/* Coupon Button */}
            <PanelBody title={__('Action Button')} initialOpen={false} className="flex gap-4 flex-col">
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Win Button Text', 'ultimate-spin-wheel')}
                value={currentDesign.couponButton?.winText || 'Start Shopping!'}
                onChange={(value) => updateDesign('couponButton', 'winText', value)}
                help={__('Button text when user wins.', 'ultimate-spin-wheel')}
              />
              <TextControl
                className='bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500'
                label={__('Lost Button Text', 'ultimate-spin-wheel')}
                value={currentDesign.couponButton?.lostText || 'Try Again'}
                onChange={(value) => updateDesign('couponButton', 'lostText', value)}
                help={__('Button text when user loses.', 'ultimate-spin-wheel')}
              />
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Text Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose coupon button text color"
                  value={currentDesign.couponButton?.color || '#fff'}
                  onChange={(e) => updateDesign('couponButton', 'color', e.target.value)}
                />
              </div>
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Background Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose coupon button background color"
                  value={currentDesign.couponButton?.backgroundColor || '#16a085'}
                  onChange={(e) => updateDesign('couponButton', 'backgroundColor', e.target.value)}
                />
              </div>
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Hover Color', 'ultimate-spin-wheel')}</label>
                <ColorPicker
                  title="Choose coupon button hover color"
                  value={currentDesign.couponButton?.hoverColor || '#139cb2'}
                  onChange={(e) => updateDesign('couponButton', 'hoverColor', e.target.value)}
                />
              </div>
              <div className="flex items-center gap-8 justify-between">
                <label className="block text-sm font-medium text-gray-900 dark:text-white w-32">{__('Border Radius', 'ultimate-spin-wheel')}</label>
                <RangeControl
                  value={currentDesign.couponButton?.borderRadius || 5}
                  onChange={(value) => updateDesign('couponButton', 'borderRadius', value)}
                  min={0}
                  max={50}
                  help={__('Button border radius in pixels', 'ultimate-spin-wheel')}
                />
              </div>
            </PanelBody>
          </div>

        </div>

        <div className="max-w-full flex-1">
          <Sticky>
            <PreviewTabs />
            <UnifiedPreview />
          </Sticky>
        </div>
      </div>
    </div>
  );
};

export default Preview;
