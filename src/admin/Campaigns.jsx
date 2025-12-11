import React, { useState } from 'react';
import { __ } from "@wordpress/i18n";
import { useSelect, dispatch } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';

const Campaigns = () => {
  const [newCampaignTitle, setNewCampaignTitle] = useState('');
  const [optimisticUpdates, setOptimisticUpdates] = useState({});

  // Get site URL using useSelect
  const siteUrl = useSelect((select) => {
    const { getSite } = select(coreDataStore);
    const site = getSite();
    return site?.url || '';
  }, []);

  // Use useSelect to fetch campaigns reactively
  const { campaigns, loading, error } = useSelect((select) => {
    const { getEntityRecords, isResolving, getLastEntitySaveError } = select(coreDataStore);
    const campaigns = getEntityRecords('postType', 'wowdevs_engage', { per_page: -1 });
    const loading = isResolving('getEntityRecords', ['postType', 'wowdevs_engage', { per_page: -1 }]);
    const error = getLastEntitySaveError && getLastEntitySaveError('postType', 'wowdevs_engage');
    return {
      campaigns,
      loading,
      error,
    };
  }, []);

  // Handle campaign creation
  const createCampaign = async () => {
    if (!newCampaignTitle.trim()) {
      alert(__('Please enter a campaign name.', 'ultimate-spin-wheel'));
      return;
    }

    try {
      const { saveEntityRecord } = dispatch(coreDataStore);
      const newCampaign = await saveEntityRecord('postType', 'wowdevs_engage', {
        title: newCampaignTitle,
        status: 'publish',
        meta: {
          uspw_type: 'spin_wheel', // Default meta value
          uspw_status: 'disabled', // Default status
          uspw_start_date: new Date().toISOString(),
          uspw_end_date: new Date(Date.now() + 60 * 60 * 24 * 60 * 1000).toISOString(),
        },
      });

      console.log('Campaign created successfully:', newCampaign);
      setNewCampaignTitle('');
    } catch (error) {
      console.error('Error creating campaign:', error);
      console.error('Error details:', {
        message: error.message,
        code: error.code,
        data: error.data
      });
      alert(__('Failed to create campaign. Please check console for details.', 'ultimate-spin-wheel'));
    }
  };

  // Handle campaign deletion
  const deleteCampaign = async (campaignId) => {
    if (!window.confirm(__('Are you sure you want to delete this campaign?', 'ultimate-spin-wheel'))) {
      return;
    }
    try {
      const { deleteEntityRecord } = dispatch(coreDataStore);
      await deleteEntityRecord('postType', 'wowdevs_engage', campaignId);
    } catch (error) {
      console.error('Error deleting campaign:', error);
      alert(__('Failed to delete campaign. Please try again.', 'ultimate-spin-wheel'));
    }
  };

  // Toggle campaign status (enabled/disabled) in a simple, clear way
  const toggleCampaignStatus = async (campaign) => {
    const currentStatus = campaign.meta?.uspw_status === 'enabled' ? 'enabled' : 'disabled';
    const newStatus = currentStatus === 'enabled' ? 'disabled' : 'enabled';

    console.log('Toggling campaign status:', {
      campaignId: campaign.id,
      currentStatus,
      newStatus,
      currentMeta: campaign.meta
    });

    // Optimistically update UI
    setOptimisticUpdates(prev => ({
      ...prev,
      [campaign.id]: {
        ...campaign,
        meta: {
          ...campaign.meta,
          uspw_status: newStatus
        }
      }
    }));

    try {
      const { editEntityRecord, saveEditedEntityRecord } = dispatch(coreDataStore);
      // Update only the status meta and save
      await editEntityRecord('postType', 'wowdevs_engage', campaign.id, {
        meta: {
          ...campaign.meta,
          uspw_status: newStatus,
        },
      });
      const result = await saveEditedEntityRecord('postType', 'wowdevs_engage', campaign.id);

      console.log('Campaign status updated successfully:', result);

      // Remove optimistic update after success
      setOptimisticUpdates(prev => {
        const updated = { ...prev };
        delete updated[campaign.id];
        return updated;
      });
    } catch (error) {
      console.error('Error updating campaign status:', error);
      console.error('Error details:', {
        message: error.message,
        code: error.code,
        data: error.data
      });
      // Revert optimistic update on error
      setOptimisticUpdates(prev => {
        const updated = { ...prev };
        delete updated[campaign.id];
        return updated;
      });
      alert(__('Failed to update campaign status. Please check console for details.', 'ultimate-spin-wheel'));
    }
  };

  // Get campaigns with optimistic updates applied
  const getCampaignsWithOptimisticUpdates = () => {
    if (!campaigns) return campaigns;

    return campaigns.map(campaign =>
      optimisticUpdates[campaign.id] || campaign
    );
  };

  const displayCampaigns = getCampaignsWithOptimisticUpdates();
  const getEditUrl = (campaignId) => {
    let url = new URL(window.location.href);
    url.searchParams.set('post_id', campaignId);
    url.hash = 'config';
    return url.toString();
  };

  return (
    <>
      <div className="bg-gradient-to-r from-blue-50 to-indigo-100 p-6 rounded-xl shadow-sm mb-8">
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-3xl font-bold text-gray-800 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            {__('Campaigns', 'ultimate-spin-wheel')}
          </h2>
        </div>
        <form className="mb-4 flex gap-4" onSubmit={(e) => {
          e.preventDefault();
          createCampaign();
        }}>
          <div className="flex-1 max-w-md">
            <input
              type="text"
              value={newCampaignTitle}
              onChange={(e) => setNewCampaignTitle(e.target.value)}
              placeholder={__('Enter campaign name', 'ultimate-spin-wheel')}
              className="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-all duration-200 bg-white shadow-sm"
              required
            />
          </div>
          <button
            type="submit"
            className="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-8 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-2"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            {__('Create Campaign', 'ultimate-spin-wheel')}
          </button>
        </form>
      </div>

      <div className="bg-white rounded-xl shadow-lg overflow-hidden">
        {loading ? (
          <div className="p-12 text-center">
            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p className="mt-4 text-gray-600">Loading campaigns...</p>
          </div>
        ) : error ? (
          <div className="p-8 text-center">
            <div className="bg-red-50 border border-red-200 rounded-lg p-6">
              <svg className="w-12 h-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p className="text-red-600 font-medium">{error.message || 'Error loading campaigns.'}</p>
            </div>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full dark:bg-gray-800 dark:text-gray-200">
              <thead className="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 border-b border-gray-200 dark:border-gray-700">
                <tr>
                  <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Campaign Name
                  </th>
                  <th scope="col" className="px-3 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Status
                  </th>
                  <th scope="col" className="px-3 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Schedule At
                  </th>
                  <th scope="col" className="px-3 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Reports
                  </th>
                  <th scope="col" className="px-3 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    -
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                {displayCampaigns && displayCampaigns.length > 0 ? (
                  displayCampaigns.map((campaign, index) => (
                    <tr
                      key={campaign.id}
                      className="hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                      <td className="pl-6 py-4">
                        <div className="font-semibold text-gray-900 text-sm">
                          {campaign.title?.rendered || campaign.title}
                          <small className="text-gray-500 ml-1 text-xs" title="Campaign ID">
                            (ID#{campaign?.id})
                          </small>
                          <small className="text-gray-500 mt-1 block">
                            Date: {campaign?.date_gmt ? new Date(campaign.date_gmt).toLocaleString() : '-'}
                          </small>
                        </div>
                      </td>
                      <td className="px-2 py-4">
                        <div className="flex items-center gap-3">
                          <button
                            onClick={() => toggleCampaignStatus(campaign)}
                            disabled={optimisticUpdates[campaign.id]} // Prevent multiple clicks during update
                            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-75 ${campaign.meta?.uspw_status === 'enabled'
                              ? 'bg-green-500 hover:bg-green-600 shadow-green-200'
                              : 'bg-gray-300 hover:bg-gray-400 shadow-gray-200'
                              } shadow-lg`}
                          >
                            <span className="sr-only">Toggle status</span>
                            <span
                              className={`inline-block h-4 w-4 transform rounded-full bg-white transition-all duration-300 ease-in-out shadow-sm ${campaign.meta?.uspw_status === 'enabled'
                                ? 'translate-x-6 shadow-md'
                                : 'translate-x-1 shadow-sm'
                                }`}
                            />
                            {optimisticUpdates[campaign.id] && (
                              <div className="absolute inset-0 flex items-center justify-center">
                                <div className="w-3 h-3 border border-white border-t-transparent rounded-full animate-spin"></div>
                              </div>
                            )}
                          </button>
                          <span className={`text-sm font-medium transition-colors duration-200 ${campaign.meta?.uspw_status === 'enabled'
                            ? 'text-green-600'
                            : 'text-gray-500'
                            }`}>
                            {campaign.meta?.uspw_status === 'enabled' ? 'Enabled' : 'Disabled'}
                          </span>
                        </div>
                      </td>
                      <td className="px-2 py-4 text-gray-600">
                        {campaign.meta?.uspw_start_date && (
                          <small className="text-gray-500 mt-1 block text-sm">
                            <strong className='mr-2 font-bold text-xs'>Start At:</strong>
                            {campaign.meta?.uspw_start_date ? new Date(campaign.meta.uspw_start_date).toLocaleString() : '-'}
                          </small>
                        )}
                        {campaign.meta?.uspw_end_date && (
                          <small className="text-gray-500 mt-1 block text-sm">
                            <strong className='mr-2 font-bold text-xs'>End At:</strong>
                            {campaign.meta?.uspw_end_date ? new Date(campaign.meta.uspw_end_date).toLocaleString() : '-'}
                          </small>
                        )}
                      </td>
                      <td className="px-2 py-4 text-sm text-gray-600">
                        <small className="text-gray-500 mt-1 block">
                          Coming soon
                        </small>
                      </td>
                      <td className="px-2 py-4 flex items-center gap-2">
                        {siteUrl && (
                          <a
                            href={siteUrl + `?spin_wheel=preview&campaign_id=${campaign.id}&_wpnonce=${USPIN_CONFIG_ADMIN.nonce}`}
                            target='_blank'
                            title='Live Preview & Debug'
                            className="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
                          >
                            {/* Eye/Preview icon */}
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                          </a>
                        )}
                        <a
                          href={getEditUrl(campaign.id)}
                          className="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                        >
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </a>
                        {campaign.meta?.uspw_type === 'spin_wheel' && (
                          <button
                            type="button"
                            className="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800"
                            onClick={() => deleteCampaign(campaign.id)}
                          >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                          </button>
                        )}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan="7" className="text-center py-4">
                      <div className="text-gray-500 dark:text-gray-400">
                        <p className="text-lg font-medium">No campaigns found</p>
                        <p className="text-sm mt-1">Create your first campaign to get started</p>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  );
};

export default Campaigns;
