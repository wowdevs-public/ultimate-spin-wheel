import React, { useEffect, useState } from 'react';
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
  faTrophy,
  faUsers,
  faChartLine,
  faPercentage,
  faCalendarDay,
  faCrown,
  faFaceFrown,
  faEnvelope,
  faGift,
  faChartBar,
  faClock,
  faCalendarDays,
  faTicket,
  faFire,
  faHistory,
  faSpinner
} from "@fortawesome/free-solid-svg-icons";
import { __ } from "@wordpress/i18n";
// Using fetch API instead of axios for compatibility

import {
  Chart as ChartJS,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Tooltip,
  Legend,
  ArcElement,
  BarElement,
  Title,
  Filler
} from 'chart.js/auto';
import { Line, Bar, Doughnut, Pie } from 'react-chartjs-2';

// Register Chart.js components
ChartJS.register(
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Tooltip,
  Legend,
  ArcElement,
  BarElement,
  Title,
  Filler
);

const Reports = () => {
  const [reportData, setReportData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setIsLoading(true);
        const formData = new FormData();
        formData.append('action', 'ultimate_spin_wheel_reports');
        formData.append('_wpnonce', USPIN_CONFIG_ADMIN.nonce);

        const response = await fetch(ajaxurl, {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.success) {
          setReportData(data.data);
        } else {
          setError('Failed to fetch reports data');
        }
      } catch (error) {
        console.error('Error fetching data:', error);
        setError('Error fetching reports data');
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, []);

  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-96">
        <div className="text-center">
          <FontAwesomeIcon icon={faSpinner} className="animate-spin text-4xl text-blue-500 mb-4" />
          <div className="text-lg text-gray-600 dark:text-gray-400">
            {__('Loading Reports', 'ultimate-spin-wheel')}...
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex justify-center items-center h-96">
        <div className="text-center text-red-500">
          <div className="text-lg">{error}</div>
        </div>
      </div>
    );
  }

  if (!reportData) {
    return (
      <div className="flex justify-center items-center h-96">
        <div className="text-center text-gray-500">
          <div className="text-lg">No data available</div>
        </div>
      </div>
    );
  }

  // Chart Options
  const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: 'index',
      intersect: false,
    },
    plugins: {
      legend: {
        position: 'top',
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleColor: 'white',
        bodyColor: 'white',
        cornerRadius: 8,
      },
    },
    scales: {
      x: {
        display: true,
        grid: {
          display: false,
        },
      },
      y: {
        display: true,
        beginAtZero: true,
        grid: {
          color: 'rgba(0, 0, 0, 0.1)',
        },
      },
    },
  };

  const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleColor: 'white',
        bodyColor: 'white',
        cornerRadius: 8,
      },
    },
    scales: {
      x: {
        display: true,
        grid: {
          display: false,
        },
      },
      y: {
        display: true,
        beginAtZero: true,
        grid: {
          color: 'rgba(0, 0, 0, 0.1)',
        },
      },
    },
  };

  const pieOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'right',
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleColor: 'white',
        bodyColor: 'white',
        cornerRadius: 8,
      },
    },
  };

  const StatCard = ({ icon, title, value, color, bgColor, shadowColor }) => (
    <div className="relative flex flex-col bg-clip-border rounded-xl bg-white text-gray-700 shadow-md dark:bg-gray-900">
      <div className={`bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr ${bgColor} text-white ${shadowColor} shadow-lg absolute -mt-4 grid h-16 w-16 place-items-center`}>
        <FontAwesomeIcon icon={icon} className="w-6 h-6 text-white" />
      </div>
      <div className="p-6 text-right">
        <p className="block antialiased font-sans text-sm leading-normal font-normal text-blue-gray-600 dark:text-gray-400">
          {title}
        </p>
        <h4 className="block antialiased tracking-normal font-sans text-2xl font-semibold leading-snug text-blue-gray-900 dark:text-white">
          {typeof value === 'number' ? value.toLocaleString() : value}
        </h4>
      </div>
    </div>
  );

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  return (
    <div className="mt-8 space-y-8">
      {/* Overview Stats */}
      <div className="grid gap-6 grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        <StatCard
          icon={faUsers}
          title="Total Engagements"
          value={reportData.total_engagements}
          bgColor="from-blue-600 to-blue-400"
          shadowColor="shadow-blue-500/40"
        />
        <StatCard
          icon={faTrophy}
          title="Total Winners"
          value={reportData.total_winners}
          bgColor="from-green-600 to-green-400"
          shadowColor="shadow-green-500/40"
        />
        <StatCard
          icon={faEnvelope}
          title="Total Leads"
          value={reportData.total_leads}
          bgColor="from-purple-600 to-purple-400"
          shadowColor="shadow-purple-500/40"
        />
        <StatCard
          icon={faPercentage}
          title="Win Rate"
          value={`${reportData.win_rate}%`}
          bgColor="from-orange-600 to-orange-400"
          shadowColor="shadow-orange-500/40"
        />
      </div>

      {/* Today's Stats */}
      <div className="grid gap-6 grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        <StatCard
          icon={faCalendarDay}
          title="Today's Engagements"
          value={reportData.today_engagements}
          bgColor="from-indigo-600 to-indigo-400"
          shadowColor="shadow-indigo-500/40"
        />
        <StatCard
          icon={faCrown}
          title="Today's Winners"
          value={reportData.today_winners}
          bgColor="from-yellow-600 to-yellow-400"
          shadowColor="shadow-pink-500/40"
        />
        <StatCard
          icon={faFaceFrown}
          title="Today's Lost"
          value={reportData.today_losers}
          bgColor="from-red-600 to-red-400"
          shadowColor="shadow-pink-500/40"
        />
        <StatCard
          icon={faEnvelope}
          title="Today's Leads"
          value={reportData.today_leads}
          bgColor="from-teal-600 to-teal-400"
          shadowColor="shadow-teal-500/40"
        />
      </div>

      {/* Charts Row 1 - Trends */}
      <div className="grid gap-6 grid-cols-1 lg:grid-cols-2">
        {/* Monthly Trends */}
        <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
          <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <FontAwesomeIcon icon={faChartLine} className="mr-2 text-blue-500" />
            Monthly Trends (Last 12 Months)
          </h6>
          <div style={{ height: 300, width: "100%" }}>
            <Line data={reportData.monthly_trends} options={lineOptions} />
          </div>
        </div>

        {/* Hourly Engagement Pattern */}
        <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
          <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <FontAwesomeIcon icon={faClock} className="mr-2 text-orange-500" />
            Hourly Engagement Pattern
          </h6>
          <div style={{ height: 300, width: "100%" }}>
            <Bar data={reportData.hourly_engagement_pattern} options={barOptions} />
          </div>
        </div>
        {/* Day Engagement Pattern */}
        <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
          <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <FontAwesomeIcon icon={faCalendarDays} className="mr-2 text-orange-500" />
            Day's Engagement Pattern
          </h6>
          <div style={{ height: 300, width: "100%" }}>
            <Bar data={reportData.days_engagement_pattern} options={barOptions} />
          </div>
        </div>
        {/* Weekly  Engagement Pattern */}
        <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
          <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <FontAwesomeIcon icon={faCalendarDays} className="mr-2 text-orange-500" />
            Weekly Engagement Pattern
          </h6>
          <div style={{ height: 300, width: "100%" }}>
            <Bar data={reportData.weekly_engagement_pattern} options={barOptions} />
          </div>
        </div>
      </div>

      {/* Charts Row 2 - Last 30 Days */}
      <div className="grid gap-6 grid-cols-1 lg:grid-cols-3">
        {/* Last 30 Days Engagements */}
        <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
          <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <FontAwesomeIcon icon={faChartBar} className="mr-2 text-blue-500" />
            Last 30 Days Engagements
          </h6>
          <div style={{ height: 250, width: "100%" }}>
            <Line data={reportData.last_30_days_engagements} options={lineOptions} />
          </div>
        </div>

        {/* Last 30 Days Winners */}
        <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
          <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <FontAwesomeIcon icon={faTrophy} className="mr-2 text-green-500" />
            Last 30 Days Winners
          </h6>
          <div style={{ height: 250, width: "100%" }}>
            <Line data={reportData.last_30_days_winners} options={lineOptions} />
          </div>
        </div>

        {/* Last 30 Days Leads */}
        <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
          <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <FontAwesomeIcon icon={faEnvelope} className="mr-2 text-purple-500" />
            Last 30 Days Leads
          </h6>
          <div style={{ height: 250, width: "100%" }}>
            <Line data={reportData.last_30_days_leads} options={lineOptions} />
          </div>
        </div>
      </div>

      {/* Charts Row 3 - Campaign Performance */}
      <div className="grid gap-6 grid-cols-1 lg:grid-cols-2">
        {/* Engagement by Campaigns */}
        <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
          <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <FontAwesomeIcon icon={faChartBar} className="mr-2 text-indigo-500" />
            Top Campaigns by Engagement
          </h6>
          <div style={{ height: 300, width: "100%" }}>
            <Doughnut data={reportData.engagement_by_campaigns} options={pieOptions} />
          </div>
        </div>

        {/* Winners by Campaigns */}
        <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
          <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <FontAwesomeIcon icon={faCrown} className="mr-2 text-yellow-500" />
            Top Campaigns by Winners
          </h6>
          <div style={{ height: 300, width: "100%" }}>
            <Pie data={reportData.winners_by_campaigns} options={pieOptions} />
          </div>
        </div>
      </div>

      {/* Prize Distribution */}
      <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
        <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
          <FontAwesomeIcon icon={faGift} className="mr-2 text-pink-500" />
          Prize Distribution
        </h6>
        <div style={{ height: 350, width: "100%" }}>
          <Bar data={reportData.prize_distribution} options={barOptions} />
        </div>
      </div>

      {/* Tables Row */}
      <div className="grid gap-6 grid-cols-1 lg:grid-cols-2">
        {/* Top Coupons */}
        <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
          <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <FontAwesomeIcon icon={faTicket} className="mr-2 text-red-500" />
            Top Performing Coupons
          </h6>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
              <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400 rounded-lg">
                <tr>
                  <th scope="col" className="px-4 py-3">Coupon</th>
                  <th scope="col" className="px-4 py-3">Code</th>
                  <th scope="col" className="px-4 py-3">Count</th>
                </tr>
              </thead>
              <tbody>
                {reportData.top_coupons?.map((coupon, index) => (
                  <tr key={index} className="bg-white border-b dark:bg-gray-900 dark:border-gray-700">
                    <td className="px-4 py-3 font-medium text-gray-900 dark:text-white">
                      {coupon.title}
                    </td>
                    <td className="px-4 py-3 font-mono text-sm">
                      {coupon.code}
                    </td>
                    <td className="px-4 py-3">
                      <span className="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-1.5 rounded dark:bg-blue-900 dark:text-blue-300">
                        {coupon.count}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Recent Activities */}
        <div className="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-md">
          <h6 className="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <FontAwesomeIcon icon={faHistory} className="mr-2 text-gray-500" />
            Recent Activities
          </h6>
          <div className="space-y-3 max-h-96 overflow-y-auto">
            {reportData.recent_activities?.map((activity, index) => (
              <div key={index} className="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div className={`w-3 h-3 rounded-full mr-3 ${activity.status === 'Won' ? 'bg-green-500' : 'bg-red-500'}`}></div>
                <div className="flex-1">
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium text-gray-900 dark:text-white">
                      {activity.name}
                    </span>
                    <span className={`text-xs px-2 py-1 rounded-full ${activity.status === 'Won'
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                      }`}>
                      {activity.status}
                    </span>
                  </div>
                  <div className="text-xs text-gray-500 dark:text-gray-200 mt-1">
                    {activity.campaign} • {activity.prize}
                  </div>
                  <div className="text-xs text-gray-400 dark:text-gray-300 mt-1">
                    {formatDate(activity.date)}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        <div className="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-6 rounded-xl">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-blue-600 dark:text-blue-400 text-sm font-medium">Engagement Rate</p>
              <p className="text-2xl font-bold text-blue-800 dark:text-blue-200">
                {reportData.total_engagements > 0 ? '100%' : '0%'}
              </p>
            </div>
            <FontAwesomeIcon icon={faFire} className="text-3xl text-blue-500" />
          </div>
          <p className="text-blue-600 dark:text-blue-400 text-xs mt-2">
            Based on total user interactions
          </p>
        </div>

        <div className="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-6 rounded-xl">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-green-600 dark:text-green-400 text-sm font-medium">Lead Conversion</p>
              <p className="text-2xl font-bold text-green-800 dark:text-green-200">
                {reportData.total_engagements > 0 ?
                  `${Math.round((reportData.total_leads / reportData.total_engagements) * 100)}%` :
                  '0%'
                }
              </p>
            </div>
            <FontAwesomeIcon icon={faChartLine} className="text-3xl text-green-500" />
          </div>
          <p className="text-green-600 dark:text-green-400 text-xs mt-2">
            Engagements to leads ratio
          </p>
        </div>

        <div className="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-6 rounded-xl">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-purple-600 dark:text-purple-400 text-sm font-medium">Average Daily</p>
              <p className="text-2xl font-bold text-purple-800 dark:text-purple-200">
                {Math.round(reportData.total_engagements / 30)}
              </p>
            </div>
            <FontAwesomeIcon icon={faCalendarDay} className="text-3xl text-purple-500" />
          </div>
          <p className="text-purple-600 dark:text-purple-400 text-xs mt-2">
            Engagements per day (30-day avg)
          </p>
        </div>
      </div>
    </div>
  );
};

export default Reports;
