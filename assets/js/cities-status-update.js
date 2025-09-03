/**
 * Cities Status Update JavaScript
 * Simplified version that only collects cities with status "expired" and sends them to the server
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
        // Send requests until no more cities have "expected" status
        let requestCount = 0;
        const maxRequests = 20;
        
        function sendNextRequest() {
            const citiesWithExpectedStatus = collectCitiesWithExpiredStatus();
            
            if (citiesWithExpectedStatus.length > 0 && requestCount < maxRequests) {
                requestCount++;
                sendCitiesForUpdate(citiesWithExpectedStatus);
                
                // Schedule next request only if there are still cities with expected status
                setTimeout(sendNextRequest, 1000);
            }
        }
        
        // Start the first request
        sendNextRequest();
    }

    /**
     * Collect all cities with status "expected"
     * 
     * @return {Array} Array of city IDs with expected status
     */
    function collectCitiesWithExpiredStatus() {
        const citiesWithExpiredStatus = [];
        
        $('.city-item').each(function() {
            const $cityItem = $(this);
            const cityId = $cityItem.data('city-id');
            const cacheStatus = $cityItem.find('.cache-status-field').val();
            
            // Only collect cities with status "expected" - exclude "no_coordinates" and other statuses
            if (cacheStatus === 'expected') {
                citiesWithExpiredStatus.push(cityId);
            }
        });
        
        return citiesWithExpiredStatus;
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
                // Print the city status list in console
                 if (response.success && response.data && response.data.city_status_list) {
                     Object.entries(response.data.city_status_list).forEach(([cityId, cityData]) => {
                         // Update DOM with the received data
                         updateCityInDOM(cityId, cityData);
                     });
                 }
            },
            error: function(xhr, status, error) {
                console.error('Failed to update cities status:', error);
            }
        });
    }

    /**
     * Update city information in the DOM with received data
     * 
     * @param {string} cityId City ID
     * @param {Object} cityData City data with status and temperature
     */
    function updateCityInDOM(cityId, cityData) {
        if (!cityData || typeof cityData !== 'object' || !cityData.status) return;
    
        const $cityItems = $(`[data-city-id="${cityId}"]`);
        if ($cityItems.length === 0) return;
    
        $cityItems.each(function() {
            const $cityItem = $(this);
    
            // статус
            const $statusField = $cityItem.find('.cache-status-field');
            if ($statusField.length) $statusField.val(cityData.status);
    
            const $statusIndicator = $cityItem.find('.cache-status-indicator');
            if ($statusIndicator.length) {
                $statusIndicator
                    .removeClass()
                    .addClass(`cache-status-indicator cache-status-${cityData.status}`)
                    .attr('title', cityData.status.charAt(0).toUpperCase() + cityData.status.slice(1) + ' cache status')
                    .html(getCacheStatusIcon(cityData.status));
            }
    
            
            const $info = $cityItem.find('.city-info');
            let $temp = $cityItem.find('.city-temperature');
    
            // remove possible <em> "Temperature data not available"
            $info.find('em').remove();
    
            if (cityData.temperature !== null && cityData.temperature !== undefined) {
                if ($temp.length === 0) {
                    if ($info.length) {
                        $info.prepend(`<span class="city-temperature">${cityData.temperature}°C</span>`);
                    } else {
                        $cityItem.prepend(`<span class="city-temperature">${cityData.temperature}°C</span>`);
                    }
                } else {
                    $temp.text(`${cityData.temperature}°C`).show();
                }
            } else {
                // no temperature - hide span, you can show <em> if you want
                if ($temp.length) $temp.hide();
                
            }
    
            $cityItem.addClass('status-updated');
            setTimeout(() => $cityItem.removeClass('status-updated'), 2000);
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
            'no_coordinates': '<span class="dashicons dashicons-location"></span>',
            'unknown': '<span class="dashicons dashicons-help"></span>'
        };
        return icons[status] || icons.unknown;
    }

    // Initialize when DOM is ready
    $(document).ready(init);

})(jQuery);
