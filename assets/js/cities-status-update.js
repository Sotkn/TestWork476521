/**
 * Cities Status Update JavaScript
 * Simplified version that only collects cities with status "expected" and sends them to the server
 */
(function($) {
    'use strict';

    /**
     * Initialize the status update functionality
     */
    function init() {
        // Update statuses when page loads
        updateCityStatuses();
        
        // Listen for custom event when cities list is updated
        $(document).on('citiesListUpdated', updateCityStatuses);
    }

    /**
     * Collect cities with status "expected" and send to server
     */
    function updateCityStatuses() {
        const citiesWithExpectedStatus = collectCitiesWithExpectedStatus();
        
        if (citiesWithExpectedStatus.length > 0) {
            sendCitiesForUpdate(citiesWithExpectedStatus);
        }
    }

    /**
     * Collect all cities with status "expected"
     * 
     * @return {Array} Array of city IDs with expected status
     */
    function collectCitiesWithExpectedStatus() {
        const citiesWithExpectedStatus = [];
        
        $('.city-item').each(function() {
            const $cityItem = $(this);
            const cityId = $cityItem.data('city-id');
            const cacheStatus = $cityItem.find('.cache-status-field').val();
            
            // Only collect cities with status "expected"
            if (cacheStatus === 'expected') {
                citiesWithExpectedStatus.push(cityId);
            }
        });
        
        return citiesWithExpectedStatus;
    }

    /**
     * Send cities with expected status to server for update
     * 
     * @param {Array} cityIds Array of city IDs to update
     */
    function sendCitiesForUpdate(cityIds) {
        if (cityIds.length === 0) return;

        $.ajax({
            url: citiesStatusUpdateAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_cities_status',
                city_ids: cityIds,
                nonce: citiesStatusUpdateAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('Cities status update successful:', response.data.message);
                    
                    // Process city updates
                    if (response.data.city_updates) {
                        processCityUpdates(response.data.city_updates);
                    }
                    
                    // Process aborted cities
                    if (response.data.abort_cities) {
                        processAbortedCities(response.data.abort_cities);
                    }
                } else {
                    console.error('Failed to update cities status:', response.data?.message || 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to update cities status:', error);
            }
        });
    }

    /**
     * Process city updates from server response
     * 
     * @param {Object} cityUpdates Object with city_id => update_data
     */
    function processCityUpdates(cityUpdates) {
        Object.entries(cityUpdates).forEach(([cityId, updateData]) => {
            const $cityItem = $(`.city-item[data-city-id="${cityId}"]`);
            if ($cityItem.length === 0) return;

            const newStatus = updateData.status;
            const newTemperature = updateData.temperature;
            
            // Update the hidden cache status field
            const $statusField = $cityItem.find('.cache-status-field');
            $statusField.val(newStatus);

            // Update the status indicator
            const $statusIndicator = $cityItem.find('.cache-status-indicator');
            $statusIndicator
                .removeClass()
                .addClass(`cache-status-indicator cache-status-${newStatus}`)
                .attr('title', newStatus.charAt(0).toUpperCase() + newStatus.slice(1) + ' cache status')
                .html(getCacheStatusIcon(newStatus));

            // Update temperature display if available
            if (newTemperature !== null) {
                let $temperatureDisplay = $cityItem.find('.city-temperature');
                
                if ($temperatureDisplay.length === 0) {
                    // Create temperature display if it doesn't exist
                    $cityItem.find('.city-info').prepend(
                        `<span class="city-temperature">${newTemperature}°C</span>`
                    );
                } else {
                    $temperatureDisplay.text(`${newTemperature}°C`);
                }
            }

            // Add visual feedback for status change
            $cityItem.addClass('status-updated');
            setTimeout(() => {
                $cityItem.removeClass('status-updated');
            }, 2000);
        });
    }

    /**
     * Process aborted cities from server response
     * 
     * @param {Array} abortCityIds Array of city IDs to abort
     */
    function processAbortedCities(abortCityIds) {
        abortCityIds.forEach(cityId => {
            const $cityItem = $(`.city-item[data-city-id="${cityId}"]`);
            if ($cityItem.length === 0) return;

            // Update status to 'abort'
            const $statusField = $cityItem.find('.cache-status-field');
            $statusField.val('abort');

            // Update the status indicator
            const $statusIndicator = $cityItem.find('.cache-status-indicator');
            $statusIndicator
                .removeClass()
                .addClass('cache-status-indicator cache-status-abort')
                .attr('title', 'Aborted - no more requests')
                .html('<span class="dashicons dashicons-dismiss"></span>');

            // Add visual feedback
            $cityItem.addClass('city-aborted');
            
            console.log(`City ${cityId} has been aborted due to repeated failures`);
        });
    }

    /**
     * Get cache status icon HTML
     * 
     * @param {string} status Cache status
     * @return {string} HTML for status icon
     */
    function getCacheStatusIcon(status) {
        const icons = {
            'valid': '<span class="dashicons dashicons-yes-alt"></span>',
            'expired': '<span class="dashicons dashicons-clock"></span>',
            'expected': '<span class="dashicons dashicons-update"></span>',
            'unavailable': '<span class="dashicons dashicons-no-alt"></span>',
            'abort': '<span class="dashicons dashicons-dismiss"></span>',
            'unknown': '<span class="dashicons dashicons-help"></span>'
        };
        return icons[status] || icons.unknown;
    }

    // Initialize when DOM is ready
    $(document).ready(init);

})(jQuery);
