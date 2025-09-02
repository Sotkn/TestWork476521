/**
 * Cities Status Update JavaScript
 * Handles collecting and sending cities without temperatures to the server
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
     * Collect cities without temperatures and send to server
     */
    function updateCityStatuses() {
        const citiesWithoutTemp = collectCitiesWithoutTemperature();
        
        if (citiesWithoutTemp.length > 0) {
            sendCitiesForUpdate(citiesWithoutTemp);
        }
    }

    /**
     * Collect all cities that don't have temperatures
     * 
     * @return {Array} Array of city IDs without temperatures
     */
    function collectCitiesWithoutTemperature() {
        const citiesWithoutTemp = [];
        
        $('.city-item').each(function() {
            const $cityItem = $(this);
            const cityId = $cityItem.data('city-id');
            const $temperatureDisplay = $cityItem.find('.city-temperature');
            const cacheStatus = $cityItem.find('.cache-status-field').val();
            
            // Check if city has no temperature display or has 'expected' status
            if ($temperatureDisplay.length === 0 || cacheStatus === 'expected') {
                citiesWithoutTemp.push(cityId);
            }
        });
        
        return citiesWithoutTemp;
    }

    /**
     * Send cities without temperatures to server for update
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
                    console.log('Cities status update initiated for', cityIds.length, 'cities');
                } else {
                    console.error('Failed to update cities status:', response.data?.message || 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to update cities status:', error);
            }
        });
    }

    // Initialize when DOM is ready
    $(document).ready(init);

})(jQuery);
