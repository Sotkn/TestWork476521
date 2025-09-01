/**
 * Cities Search JavaScript
 * Handles AJAX search functionality for cities and countries
 */
(function($) {
    'use strict';

    // Cache DOM elements
    const $searchInput = $('#cities-search');
    const $searchBtn = $('#cities-search-btn');
    const $resetBtn = $('#cities-reset-btn');
    const $tableContainer = $('#cities-table-container');
    const $searchHint = $('.search-hint');

    // Search state
    let searchTimeout;
    let isSearching = false;

    /**
     * Initialize the search functionality
     */
    function init() {
        bindEvents();
        updateSearchHint();
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Search button click
        $searchBtn.on('click', handleSearch);

        // Reset button click
        $resetBtn.on('click', handleReset);

        // Search input events
        $searchInput.on('input', handleInputChange);
        $searchInput.on('keypress', handleKeyPress);

        // Initial state
        updateResetButtonState();
    }

    /**
     * Handle search input changes
     */
    function handleInputChange() {
        const searchTerm = $searchInput.val().trim();
        
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Update UI state
        updateResetButtonState();
        updateSearchHint();

        // Auto-search after 500ms of no typing (if term is long enough)
        if (searchTerm.length >= 2) {
            searchTimeout = setTimeout(() => {
                performSearch(searchTerm);
            }, 500);
        } else if (searchTerm.length === 0) {
            // Reset to show all cities
            performSearch('');
        }
    }

    /**
     * Handle search button click
     */
    function handleSearch() {
        const searchTerm = $searchInput.val().trim();
        performSearch(searchTerm);
    }

    /**
     * Handle reset button click
     */
    function handleReset() {
        $searchInput.val('');
        performSearch('');
        $searchInput.focus();
    }

    /**
     * Handle Enter key press
     */
    function handleKeyPress(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            handleSearch();
        }
    }

    /**
     * Perform the actual search via AJAX
     */
    function performSearch(searchTerm) {
        if (isSearching) {
            return; // Prevent multiple simultaneous requests
        }

        isSearching = true;
        updateSearchButtonState(true);

        // Show loading state
        $tableContainer.html('<div class="loading">' + citiesSearchAjax.searching + '</div>');

        // Make AJAX request
        $.ajax({
            url: citiesSearchAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cities_search',
                search_term: searchTerm,
                nonce: citiesSearchAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $tableContainer.html(response.data.html);
                    updateSearchHint(response.data.count, searchTerm);
                } else {
                    $tableContainer.html('<div class="error">' + citiesSearchAjax.noResults + '</div>');
                }
            },
            error: function() {
                $tableContainer.html('<div class="error">' + citiesSearchAjax.noResults + '</div>');
            },
            complete: function() {
                isSearching = false;
                updateSearchButtonState(false);
            }
        });
    }

    /**
     * Update search button state
     */
    function updateSearchButtonState(searching) {
        if (searching) {
            $searchBtn.prop('disabled', true).text(citiesSearchAjax.searching);
        } else {
            $searchBtn.prop('disabled', false).text('Search');
        }
    }

    /**
     * Update reset button state
     */
    function updateResetButtonState() {
        const hasValue = $searchInput.val().trim().length > 0;
        $resetBtn.prop('disabled', !hasValue);
    }

    /**
     * Update search hint with results count
     */
    function updateSearchHint(count = null, searchTerm = '') {
        if (count !== null && searchTerm.length > 0) {
            $searchHint.text(
                count === 1 
                    ? 'Found 1 result for "' + searchTerm + '"'
                    : 'Found ' + count + ' results for "' + searchTerm + '"'
            );
        } else {
            $searchHint.text('Type at least 2 characters to search, or leave empty to show all cities');
        }
    }

    // Initialize when DOM is ready
    $(document).ready(init);

})(jQuery);
