import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { __ } from "@wordpress/i18n";

const Entries = () => {
  const [entries, setEntries] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalEntries, setTotalEntries] = useState(0);
  const [perPage] = useState(20);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedEntries, setSelectedEntries] = useState([]);
  const [refreshing, setRefreshing] = useState(false);

  // Simplified WordPress AJAX helper function using axios
  const wpAjax = async (action, data = {}) => {
    const params = new URLSearchParams({
      action: action,
      nonce: USPIN_CONFIG_ADMIN.nonce,
      ...data
    });

    // Handle arrays in data
    Object.keys(data).forEach(key => {
      if (Array.isArray(data[key])) {
        params.delete(key); // Remove the non-array version
        data[key].forEach(item => params.append(`${key}[]`, item));
      }
    });

    const response = await axios.post(USPIN_CONFIG_ADMIN.ajax_url, params);

    if (!response.data.success) {
      throw new Error(response.data?.data?.message || 'Ajax request failed');
    }

    return response.data.data;
  };

  // Fetch entries
  const fetchEntries = async (page = 1, search = '', clearCache = false) => {
    setLoading(true);
    setError(null);

    try {
      const data = await wpAjax('ultimate_spin_wheel_get_entries', {
        page: page,
        per_page: perPage,
        search: search,
        clear_cache: clearCache ? 'true' : 'false',
        nonce: USPIN_CONFIG_ADMIN.nonce
      });

      setEntries(data.entries || []);
      setTotalPages(data.total_pages || 1);
      setTotalEntries(data.total || 0);
    } catch (err) {
      setError(err.message || 'Failed to fetch entries');
      console.error('Error fetching entries:', err);
    } finally {
      setLoading(false);
    }
  };

  // Initial load and when filters change
  useEffect(() => {
    fetchEntries(currentPage, searchTerm);
  }, [currentPage, searchTerm]);

  // Handle search
  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
    setCurrentPage(1);
  };

  // Handle pagination
  const handlePageChange = (page) => {
    setCurrentPage(page);
  };

  // Handle cache refresh
  const handleRefreshCache = async () => {
    setRefreshing(true);
    try {
      await fetchEntries(currentPage, searchTerm, true);
      // Show success message briefly
      const originalTitle = document.title;
      document.title = __('Cache Refreshed!', 'ultimate-spin-wheel');
      setTimeout(() => {
        document.title = originalTitle;
      }, 2000);
    } catch (error) {
      console.error('Error refreshing cache:', error);
      alert(__('Failed to refresh cache. Please try again.', 'ultimate-spin-wheel'));
    } finally {
      setRefreshing(false);
    }
  };

  // Handle entry selection
  const handleSelectEntry = (entryId) => {
    setSelectedEntries(prev =>
      prev.includes(entryId)
        ? prev.filter(id => id !== entryId)
        : [...prev, entryId]
    );
  };

  const handleSelectAll = () => {
    if (selectedEntries.length === entries.length) {
      setSelectedEntries([]);
    } else {
      setSelectedEntries(entries.map(entry => entry.id));
    }
  };

  // Delete single entry
  const deleteEntry = async (entryId) => {
    if (!window.confirm(__('Are you sure you want to delete this entry?', 'ultimate-spin-wheel'))) {
      return;
    }

    try {
      await wpAjax('ultimate_spin_wheel_delete_entry', { id: entryId, nonce: USPIN_CONFIG_ADMIN.nonce });
      fetchEntries(currentPage, searchTerm);
    } catch (error) {
      console.error('Error deleting entry:', error);
      alert(__('Failed to delete entry. Please try again.', 'ultimate-spin-wheel'));
    }
  };

  // Bulk delete entries
  const bulkDeleteEntries = async () => {
    if (selectedEntries.length === 0) {
      alert(__('Please select entries to delete.', 'ultimate-spin-wheel'));
      return;
    }

    if (!window.confirm(__(`Are you sure you want to delete ${selectedEntries.length} entries?`, 'ultimate-spin-wheel'))) {
      return;
    }

    try {
      await wpAjax('ultimate_spin_wheel_bulk_delete_entries', { ids: selectedEntries, nonce: USPIN_CONFIG_ADMIN.nonce });
      setSelectedEntries([]);
      fetchEntries(currentPage, searchTerm);
    } catch (error) {
      console.error('Error bulk deleting entries:', error);
      alert(__('Failed to delete entries. Please try again.', 'ultimate-spin-wheel'));
    }
  };

  // Export entries
  const exportEntries = async () => {
    try {
      const data = await wpAjax('ultimate_spin_wheel_export_entries', {
        search: searchTerm,
        nonce: USPIN_CONFIG_ADMIN.nonce
      });

      // The PHP now returns the CSV string as the 'data' property of the JSON response
      const csvContent = typeof data === 'string' ? data : '';

      if (!csvContent || !csvContent.trim().startsWith('ID,')) {
        throw new Error('No valid CSV content received from server');
      }

      // Add BOM for Excel compatibility
      const BOM = '\uFEFF';
      const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `qr-entries-${new Date().toISOString().split('T')[0]}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);

      alert(__('Entries exported successfully!', 'ultimate-spin-wheel'));
    } catch (error) {
      console.error('Error exporting entries:', error);
      alert(__('Failed to export entries. Please try again.', 'ultimate-spin-wheel'));
    }
  };

  // Format date
  const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
  };

  // Parse JSON data safely
  const parseJsonData = (jsonString) => {
    try {
      return JSON.parse(jsonString || '{}');
    } catch (e) {
      return {};
    }
  };

  // Generate pagination
  const generatePagination = () => {
    const pages = [];
    const maxVisiblePages = 5;

    if (totalPages <= maxVisiblePages) {
      for (let i = 1; i <= totalPages; i++) {
        pages.push(i);
      }
    } else {
      const startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
      const endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

      for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
      }
    }

    return pages;
  };

  return (
    <div className="space-y-6">
      {/* Header with filters and actions */}
      <div className="bg-white rounded-xl shadow-lg p-6">
        <div className="flex flex-col md:flex-row gap-4 justify-between items-start md:items-center">
          <div className="flex flex-col md:flex-row gap-4 flex-1">
            {/* Search */}
            <div className="flex-1 max-w-md">
              <input
                type="text"
                placeholder={__('Search entries...', 'ultimate-spin-wheel')}
                value={searchTerm}
                onChange={handleSearch}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            {/* Removed Status and Optin Filters */}
          </div>

          {/* Actions */}
          <div className="flex gap-2">
            <button
              onClick={handleRefreshCache}
              disabled={refreshing}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <svg 
                className={`w-4 h-4 ${refreshing ? 'animate-spin' : ''}`} 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              {refreshing ? __('Refreshing...', 'ultimate-spin-wheel') : __('Refresh Cache', 'ultimate-spin-wheel')}
            </button>

            <button
              onClick={exportEntries}
              className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"
            >
              {__('Export CSV', 'ultimate-spin-wheel')}
            </button>

            {selectedEntries.length > 0 && (
              <button
                onClick={bulkDeleteEntries}
                className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
              >
                {__('Delete Selected', 'ultimate-spin-wheel')} ({selectedEntries.length})
              </button>
            )}
          </div>
        </div>

        {/* Summary */}
        <div className="mt-4 flex justify-between items-center">
          <div className="text-sm text-gray-600">
            {__('Total entries:', 'ultimate-spin-wheel')} {totalEntries}
          </div>
          <div className="text-xs text-gray-500 flex items-center gap-1">
            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {__('Data cached for 60 minutes', 'ultimate-spin-wheel')}
          </div>
        </div>
      </div>

      {/* Entries Table */}
      <div className="bg-white rounded-xl shadow-lg overflow-hidden">
        {loading ? (
          <div className="p-12 text-center">
            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p className="mt-4 text-gray-600">{__('Loading entries...', 'ultimate-spin-wheel')}</p>
          </div>
        ) : error ? (
          <div className="p-8 text-center">
            <div className="bg-red-50 border border-red-200 rounded-lg p-6">
              <svg className="w-12 h-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p className="text-red-600 font-medium">{error}</p>
              <button
                onClick={() => fetchEntries(currentPage, searchTerm)}
                className="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
              >
                {__('Retry', 'ultimate-spin-wheel')}
              </button>
            </div>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                <tr>
                  <th scope="col" className="px-6 py-4 text-left">
                    <input
                      type="checkbox"
                      checked={selectedEntries.length === entries.length && entries.length > 0}
                      onChange={handleSelectAll}
                      className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                  </th>
                  <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    {__('Name', 'ultimate-spin-wheel')}
                  </th>
                  <th scope="col" className="px-3 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    {__('Email', 'ultimate-spin-wheel')}
                  </th>
                  <th scope="col" className="px-3 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    {__('Campaign', 'ultimate-spin-wheel')}
                  </th>
                  <th scope="col" className="px-3 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    {__('Type', 'ultimate-spin-wheel')}
                  </th>
                  <th scope="col" className="px-3 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    {__('Created', 'ultimate-spin-wheel')}
                  </th>
                  <th scope="col" className="px-3 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    {__('Actions', 'ultimate-spin-wheel')}
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {entries && entries.length > 0 ? (
                  entries.map((entry) => {
                    const othersData = parseJsonData(entry.others_data);
                    const userData = parseJsonData(entry.user_data);

                    return (
                      <tr
                        key={entry.id}
                        className="hover:bg-gray-50"
                      >
                        <td className="px-6 py-4">
                          <input
                            type="checkbox"
                            checked={selectedEntries.includes(entry.id)}
                            onChange={() => handleSelectEntry(entry.id)}
                            className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                          />
                        </td>
                        <td className="px-6 py-4">
                          <div className="font-medium text-gray-900 text-sm">
                            {entry.name || '-'}
                          </div>
                          <div className="text-xs text-gray-500">
                            ID: #{entry.id}
                          </div>
                        </td>
                        <td className="px-3 py-4 text-sm text-gray-600">
                          {entry.email || '-'}
                        </td>
                        <td className="px-3 py-4 text-sm text-gray-600">
                          <div className="font-medium">
                            {entry.campaign_title || '-'}
                          </div>
                          {entry.campaign_id && (
                            <div className="text-xs text-gray-500">
                              ID: #{entry.campaign_id}
                            </div>
                          )}
                        </td>
                        <td className="px-3 py-4 text-sm text-gray-600">
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {entry.campaign_type || 'QR Code'}
                          </span>
                        </td>
                        <td className="px-3 py-4 text-sm text-gray-600">
                          {formatDate(entry.created_at)}
                        </td>
                        <td className="px-3 py-4 text-sm">
                          <div className="flex items-center gap-2">
                            <button
                              onClick={() => deleteEntry(entry.id)}
                              className="text-red-600 hover:text-red-700 p-1 rounded-full hover:bg-red-50 transition-colors"
                              title={__('Delete Entry', 'ultimate-spin-wheel')}
                            >
                              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                              </svg>
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                ) : (
                  <tr>
                    <td colSpan="9" className="text-center py-8">
                      <div className="text-gray-500">
                        <svg className="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p className="text-lg font-medium">{__('No entries found', 'ultimate-spin-wheel')}</p>
                        <p className="text-sm mt-1">{__('No entries match your current filters', 'ultimate-spin-wheel')}</p>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="bg-white rounded-xl shadow-lg px-6 py-4">
          <div className="flex items-center justify-between">
            <div className="text-sm text-gray-700">
              {__('Showing', 'ultimate-spin-wheel')} {((currentPage - 1) * perPage) + 1} {__('to', 'ultimate-spin-wheel')} {Math.min(currentPage * perPage, totalEntries)} {__('of', 'ultimate-spin-wheel')} {totalEntries} {__('entries', 'ultimate-spin-wheel')}
            </div>

            <div className="flex items-center gap-2">
              <button
                onClick={() => handlePageChange(currentPage - 1)}
                disabled={currentPage === 1}
                className="px-3 py-1 rounded-lg border border-gray-300 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
              >
                {__('Previous', 'ultimate-spin-wheel')}
              </button>

              {generatePagination().map(page => (
                <button
                  key={page}
                  onClick={() => handlePageChange(page)}
                  className={`px-3 py-1 rounded-lg text-sm transition-colors ${currentPage === page
                    ? 'bg-blue-600 text-white'
                    : 'border border-gray-300 hover:bg-gray-50'
                    }`}
                >
                  {page}
                </button>
              ))}

              <button
                onClick={() => handlePageChange(currentPage + 1)}
                disabled={currentPage === totalPages}
                className="px-3 py-1 rounded-lg border border-gray-300 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
              >
                {__('Next', 'ultimate-spin-wheel')}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Entries;
