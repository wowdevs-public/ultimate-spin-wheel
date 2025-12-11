import React, { useMemo, useCallback } from 'react';

// Constants
const DEFAULT_LOST_COLORS = [
  '#277162', '#277162', '#277162', '#277162', '#277162',
];

const FIELD_CONFIGS = {
  win: {
    bgColor: 'from-green-50 to-emerald-50 hover:from-green-100 hover:to-emerald-100',
    badgeColor: 'from-green-500 to-emerald-500',
    focusColor: 'focus:ring-green-500',
    dotColor: 'bg-green-500 animate-pulse'
  },
  lost: {
    bgColor: 'from-red-50 to-pink-50 hover:from-red-100 hover:to-pink-100',
    badgeColor: 'from-red-500 to-pink-500',
    focusColor: 'focus:ring-red-500',
    dotColor: 'bg-red-500'
  }
};

// Utility Components
const StatusIndicator = ({ type, label }) => {
  const config = FIELD_CONFIGS[type];
  return (
    <div className="flex items-center space-x-2">
      <div className={`w-2 h-2 ${config.dotColor} rounded-full`}></div>
      <span className={`inline-block px-2 py-1 bg-gradient-to-r ${config.badgeColor} text-white text-xs font-${type === 'win' ? 'bold' : 'medium'} rounded-full shadow-sm`}>
        {label}
      </span>
    </div>
  );
};

const InputField = ({
  type = 'text',
  value = '',
  onChange,
  placeholder,
  className = '',
  focusColor = 'focus:ring-green-500',
  ...props
}) => (
  <input
    type={type}
    value={value}
    onChange={onChange}
    className={`w-full p-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:ring-2 ${focusColor} focus:border-transparent transition-all duration-200 bg-white shadow-sm ${className}`}
    placeholder={placeholder}
    {...props}
  />
);

const ColorPicker = ({ value, onChange, title }) => (
  <input
    type="color"
    value={value}
    onChange={onChange}
    className="w-12 h-12 cursor-pointer"
    title={title}
  />
);

const DeleteButton = ({ onClick }) => (
  <button
    onClick={onClick}
    className="text-white bg-gradient-to-r from-red-400 via-red-500 to-red-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-2.5 py-2.5 text-center group"
    title="Remove this coupon"
  >
    <svg
      className="w-5 h-5 transition-transform duration-200 group-hover:scale-110"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth={2}
        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
      />
    </svg>
  </button>
);

const EmptyCell = ({ text }) => (
  <div className="flex items-center justify-center h-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
    <span className="text-gray-400 italic text-sm">{text}</span>
  </div>
);

const SummaryCard = ({ icon, title, value, isWarning = false, warningText = '' }) => (
  <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
    <div className="flex items-center space-x-2 mb-2">
      <span className={`w-2 h-2 ${icon} rounded-full`}></span>
      <span className="font-semibold text-gray-700">{title}</span>
    </div>
    <div className="flex items-center space-x-2">
      <span className={`text-2xl font-bold ${isWarning ? 'text-red-600' : 'text-blue-600'}`}>
        {value}
      </span>
      {isWarning && warningText && (
        <span className="text-red-500 text-xs bg-red-100 px-2 py-1 rounded-full">
          {warningText}
        </span>
      )}
    </div>
  </div>
);

// Main Component
const Coupons = ({ coupons = [], onAdd, onRemove, onUpdate }) => {
  // Memoized enhanced coupons data
  const enhancedCoupons = useMemo(() => {
    return coupons.map((coupon, index) => ({
      ...coupon,
      lost: coupon.lost || {
        label: '',
        color: coupons[index]?.lost?.color || DEFAULT_LOST_COLORS[index % DEFAULT_LOST_COLORS.length]
      }
    }));
  }, [coupons]);

  // Memoized calculations
  const calculations = useMemo(() => {
    const totalWinProbability = enhancedCoupons.reduce(
      (sum, coupon) => sum + (parseInt(coupon?.probability) || 0),
      0
    );
    const lostProbability = Math.max(0, 100 - totalWinProbability);
    const averageWinRate = enhancedCoupons.length > 0
      ? Math.round(totalWinProbability / enhancedCoupons.length)
      : 0;
    const isOverLimit = totalWinProbability > 100;

    return {
      totalWinProbability,
      lostProbability,
      averageWinRate,
      isOverLimit
    };
  }, [enhancedCoupons]);

  // Event handlers
  const handleFieldUpdate = useCallback((index, field, value) => {
    const updatedCoupon = { ...enhancedCoupons[index] };

    if (field.startsWith('lost.')) {
      const lostField = field.split('.')[1];
      updatedCoupon.lost = {
        ...updatedCoupon.lost,
        [lostField]: value || '' // Ensure default value is saved
      };
    } else {
      updatedCoupon[field] = value || ''; // Ensure default value is saved
    }

    onUpdate(index, updatedCoupon);
  }, [enhancedCoupons, onUpdate]);

  const handleLostUpdate = useCallback((index, lostField, value) => {
    const updatedCoupon = { ...enhancedCoupons[index] };
    updatedCoupon.lost = {
      ...updatedCoupon.lost,
      [lostField]: value || '' // Ensure default value is saved
    };
    onUpdate(index, updatedCoupon);
  }, [enhancedCoupons, onUpdate]);

  // Render table rows
  const renderCouponRows = () => {
    return enhancedCoupons.map((coupon, index) => (
      <React.Fragment key={`coupon-${index}`}>
        {/* Win Row */}
        <tr className={`bg-gradient-to-r ${FIELD_CONFIGS.win.bgColor} transition-all duration-200`}>
          <td className="px-6 py-4 border-r border-gray-100">
            <StatusIndicator type="win" label="WIN" />
          </td>
          <td className="px-6 py-4 border-r border-gray-100">
            <InputField
              value={coupon?.label || ''}
              onChange={(e) => handleFieldUpdate(index, 'label', e.target.value)}
              placeholder="🏷️ Win Label"
              focusColor={FIELD_CONFIGS.win.focusColor}
            />
          </td>
          <td className="px-6 py-4 border-r border-gray-100">
            <InputField
              value={coupon?.code || ''}
              onChange={(e) => handleFieldUpdate(index, 'code', e.target.value)}
              placeholder="💳 Coupon Code"
              className="font-mono"
              focusColor={FIELD_CONFIGS.win.focusColor}
            />
          </td>
          <td className="px-6 py-4 border-r border-gray-100">
            <div className="relative">
              <InputField
                type="number"
                value={coupon?.probability || ''}
                onChange={(e) => handleFieldUpdate(index, 'probability', e.target.value)}
                placeholder="Win %"
                className="pr-8"
                focusColor={FIELD_CONFIGS.win.focusColor}
                min="0"
                max="100"
              />
              <span className="absolute right-3 top-3 text-gray-400 text-sm">%</span>
            </div>
          </td>
          <td className="px-6 py-4 border-r border-gray-100">
            <ColorPicker
              value={coupon?.color || '#4ECDC4'}
              onChange={(e) => handleFieldUpdate(index, 'color', e.target.value)}
              title="Choose win color"
            />
          </td>
          <td className="px-6 py-4 text-center">
            <DeleteButton onClick={() => onRemove(index)} />
          </td>
        </tr>

        {/* Lost Row */}
        <tr className={`bg-gradient-to-r ${FIELD_CONFIGS.lost.bgColor} transition-all duration-200`}>
          <td className="px-6 py-4 border-r border-gray-100">
            <StatusIndicator type="lost" label="LOST" />
          </td>
          <td className="px-6 py-4 border-r border-gray-100">
            <InputField
              value={coupon?.lost?.label || ''}
              onChange={(e) => handleLostUpdate(index, 'label', e.target.value)}
              placeholder=""
              focusColor={FIELD_CONFIGS.lost.focusColor}
            />
          </td>
          <td className="px-6 py-4 border-r border-gray-100">
            <EmptyCell text="🚫 No coupon code for lost" />
          </td>
          <td className="px-6 py-4 border-r border-gray-100">
            <EmptyCell text="N/A" />
          </td>
          <td className="px-6 py-4 border-r border-gray-100">
            <ColorPicker
              value={coupon?.lost?.color || DEFAULT_LOST_COLORS[index % DEFAULT_LOST_COLORS.length]}
              onChange={(e) => handleLostUpdate(index, 'color', e.target.value)}
              title="Choose lost color"
            />
          </td>
          <td className="px-6 py-4 text-center"></td>
        </tr>

        {/* Separator Row */}
        {index < enhancedCoupons.length - 1 && (
          <tr>
            <td colSpan="6" className="h-4 bg-gradient-to-r from-gray-50 via-white to-gray-50 border-b border-gray-100">
              <div className="w-full h-full flex items-center justify-center">
                <div className="w-32 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
              </div>
            </td>
          </tr>
        )}
      </React.Fragment>
    ));
  };

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-8 pb-4 border-b border-gray-200">
        <div>
          <h3 className="text-2xl font-bold text-gray-800 mb-2">🎡 Coupons List</h3>
          <p className="text-gray-600 text-sm">Configure your coupon probabilities and appearance</p>
        </div>
      </div>

      {/* Table */}
      <div className="overflow-x-auto rounded-lg shadow-sm">
        <table className="table-auto w-full border-collapse bg-white">
          <thead>
            <tr className="bg-gradient-to-r from-indigo-800 to-purple-600 text-white shadow-lg">
              <th className="px-6 py-4 text-left font-semibold text-sm uppercase tracking-wider border-r border-blue-500">
                <div className="flex items-center space-x-2">
                  <span className="w-2 h-2 bg-white rounded-full"></span>
                  <span>Type</span>
                </div>
              </th>
              <th className="px-6 py-4 text-left font-semibold text-sm uppercase tracking-wider border-r border-blue-500">Label</th>
              <th className="px-6 py-4 text-left font-semibold text-sm uppercase tracking-wider border-r border-blue-500">Code/Description</th>
              <th className="px-6 py-4 text-left font-semibold text-sm uppercase tracking-wider border-r border-blue-500">Probability (%)</th>
              <th className="px-6 py-4 text-left font-semibold text-sm uppercase tracking-wider border-r border-blue-500">Color</th>
              <th className="px-6 py-4 text-center font-semibold text-sm uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody>
            {renderCouponRows()}
          </tbody>
        </table>
      </div>

      {/* Add Button */}
      <div className="mt-6">
        <button
          onClick={onAdd}
          className="px-6 py-3 bg-blue-500 text-white rounded shadow hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-colors"
        >
          Add New Coupon
        </button>
      </div>

      {/* Summary Section */}
      <div className="mt-8">
        <div className="mb-6">
          <h4 className="text-xl font-bold text-gray-800">Configuration Summary</h4>
          <p className="text-gray-600 text-sm">
            In a spin wheel, the total of all win probabilities should equal 100% or less.
            Any remaining percentage becomes the "lost" probability. Each coupon competes for its share of the 100% total.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
          <SummaryCard
            icon="bg-blue-500"
            title="Total Coupons"
            value={enhancedCoupons.length}
          />

          <SummaryCard
            icon="bg-green-500"
            title="Total Win Probability"
            value={`${calculations.totalWinProbability}%`}
            isWarning={calculations.isOverLimit}
            warningText={calculations.isOverLimit ? "⚠️ Over 100%" : ""}
          />

          <SummaryCard
            icon="bg-red-500"
            title="Lost Probability"
            value={`${calculations.lostProbability}%`}
              />

              <SummaryCard
                icon="bg-purple-500"
                title="Average Win Rate"
                value={`${calculations.averageWinRate}%`}
              />
        </div>

        {/* Warning Message */}
        {calculations.isOverLimit && (
          <div className="mt-6 p-4 bg-gradient-to-r from-red-50 to-orange-50 border-l-4 border-red-500 rounded-lg shadow-sm">
            <div className="flex items-start space-x-3">
              <div className="w-6 h-6 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <span className="text-white text-xs">⚠️</span>
              </div>
              <div>
                <h5 className="font-semibold text-red-800 mb-1">Probability Warning</h5>
                <p className="text-red-700 text-sm">
                  Total win probabilities exceed 100%. This configuration may cause unpredictable results.
                  Consider reducing individual probabilities so they sum to 100% or less.
                </p>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default Coupons;
