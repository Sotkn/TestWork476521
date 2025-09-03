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
        console.log('Cities Status Update JS loaded and initialized');
        
        // Update statuses when page loads
        updateCityStatuses();
        
        // Listen for custom event when cities list is updated
        $(document).on('citiesListUpdated', updateCityStatuses);
    }

    /**
     * Collect cities with status "expected" and send to server
     */
    function updateCityStatuses() {
        console.log('updateCityStatuses called - looking for cities with expected status');
        
        // Send requests until no more cities have "expected" status
        let requestCount = 0;
        const maxRequests = 20;
        
        function sendNextRequest() {
            const citiesWithExpectedStatus = collectCitiesWithExpiredStatus();
            
            if (citiesWithExpectedStatus.length > 0 && requestCount < maxRequests) {
                requestCount++;
                console.log(`Sending request ${requestCount}/${maxRequests} for cities:`, citiesWithExpectedStatus);
                sendCitiesForUpdate(citiesWithExpectedStatus);
                
                // Schedule next request only if there are still cities with expected status
                setTimeout(sendNextRequest, 1000);
            } else {
                if (citiesWithExpectedStatus.length === 0) {
                    console.log('No more cities with expected status - stopping requests');
                } else {
                    console.log(`Reached maximum request limit (${maxRequests}) - stopping requests`);
                }
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
        
        console.log('Searching for cities with expected status...');
        console.log('Total city items found:', $('.city-item').length);
        
        $('.city-item').each(function() {
            const $cityItem = $(this);
            const cityId = $cityItem.data('city-id');
            const cacheStatus = $cityItem.find('.cache-status-field').val();
            
            console.log(`City ID: ${cityId}, Cache Status: ${cacheStatus}`);
            
            // Only collect cities with status "expected" - exclude "no_coordinates" and other statuses
            if (cacheStatus === 'expected') {
                citiesWithExpiredStatus.push(cityId);
                console.log(`Added city ${cityId} to expected list`);
            } else if (cacheStatus === 'no_coordinates') {
                console.log(`Skipping city ${cityId} - has no_coordinates status`);
            } else {
                console.log(`Skipping city ${cityId} - status is ${cacheStatus} (not expected)`);
            }
        });
        
        console.log('Cities with expected status collected:', citiesWithExpiredStatus);
        return citiesWithExpiredStatus;
    }

    /**
     * Send cities with expected status to server for update
     * 
     * @param {Array} cityIds Array of city IDs to update
     */
    function sendCitiesForUpdate(cityIds) {
        if (cityIds.length === 0) return;

        console.log('Sending AJAX request for city IDs:', cityIds);

        $.ajax({
            url: citiesStatusUpdateAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_cities_status',
                city_ids: cityIds,
                nonce: citiesStatusUpdateAjax.nonce
            },
            success: function(response) {
                console.log('Cities status update successful:', response);
                
                                 // Print the city status list in console
                 if (response.success && response.data && response.data.city_status_list) {
                     console.log('=== City Status List ===');
                     console.log('Total cities in list:', Object.keys(response.data.city_status_list).length);
                     
                     Object.entries(response.data.city_status_list).forEach(([cityId, cityData]) => {
                         console.log(`City ID: ${cityId}`);
                         console.log(`  Status: ${cityData.status}`);
                         if (cityData.temperature !== null) {
                             console.log(`  Temperature: ${cityData.temperature}°C`);
                         } else {
                             console.log(`  Temperature: Not available`);
                         }
                         console.log('---');
                         
                         // Update DOM with the received data
                         updateCityInDOM(cityId, cityData);
                     });
                 } else {
                     console.log('No city status list received in response');
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
        // Validate that we have valid city data from AJAX response
        if (!cityData || typeof cityData !== 'object' || !cityData.status) {
            console.log(`Skipping DOM update for city ${cityId} - invalid or missing city data from AJAX response`);
            return;
        }

        const $cityItem = $(`.city-item[data-city-id="${cityId}"]`);
        
        if ($cityItem.length === 0) {
            console.log(`City item with ID ${cityId} not found in DOM`);
            return;
        }

        console.log(`Updating DOM for city ${cityId}:`, cityData);

        // Update the hidden cache status field
        const $statusField = $cityItem.find('.cache-status-field');
        if ($statusField.length > 0) {
            $statusField.val(cityData.status);
            console.log(`Updated status field for city ${cityId} to: ${cityData.status}`);
        }

        // Update the status indicator
        const $statusIndicator = $cityItem.find('.cache-status-indicator');
        if ($statusIndicator.length > 0) {
            $statusIndicator
                .removeClass()
                .addClass(`cache-status-indicator cache-status-${cityData.status}`)
                .attr('title', cityData.status.charAt(0).toUpperCase() + cityData.status.slice(1) + ' cache status')
                .html(getCacheStatusIcon(cityData.status));
            
            console.log(`Updated status indicator for city ${cityId} to: ${cityData.status}`);
        }

        // Update temperature display if available
        if (cityData.temperature !== null) {
            let $temperatureDisplay = $cityItem.find('.city-temperature');
            
            if ($temperatureDisplay.length === 0) {
                // Create temperature display if it doesn't exist
                $cityItem.find('.city-info').prepend(
                    `<span class="city-temperature">${cityData.temperature}°C</span>`
                );
                console.log(`Created temperature display for city ${cityId}: ${cityData.temperature}°C`);
            } else {
                $temperatureDisplay.text(`${cityData.temperature}°C`);
                console.log(`Updated temperature display for city ${cityId} to: ${cityData.temperature}°C`);
            }
        }

        // Add visual feedback for status change
        $cityItem.addClass('status-updated');
        setTimeout(() => {
            $cityItem.removeClass('status-updated');
        }, 2000);

        console.log(`DOM update completed for city ${cityId}`);
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
