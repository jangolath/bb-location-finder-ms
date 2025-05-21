<?php
// includes/tab-integration.php

/**
 * Class for integrating a Location Search tab into the BuddyBoss members directory
 */
class BB_Location_Tab_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Debug hooks to check if directory tabs are being rendered
        add_action('bp_before_directory_members_tabs', array($this, 'debug_before_tabs'));
        add_action('bp_after_directory_members_tabs', array($this, 'debug_after_tabs'));
        
        // BuddyBoss Platform uses different hooks depending on version
        // For BuddyBoss Platform 1.x
        add_action('bp_members_directory_member_sub_types', array($this, 'add_location_tab_legacy'), 10);
        
        // For BuddyBoss Platform 2.x with Nouveau template
        add_action('bp_nouveau_directory_nav_items', array($this, 'add_location_tab_nouveau'), 10);
        
        // Standard BP hook (used by both as fallback)
        add_action('bp_members_directory_tabs', array($this, 'add_location_tab'), 10);
        
        // Add location search content when tab is active
        add_action('bp_members_directory_member_types', array($this, 'location_search_content'));
        add_action('bp_members_directory_member_sub_types', array($this, 'location_search_content'));
        
        // Add AJAX handler for tab-specific location search
        add_action('wp_ajax_bb_tab_location_search', array($this, 'ajax_tab_location_search'));
        add_action('wp_ajax_nopriv_bb_tab_location_search', array($this, 'ajax_tab_location_search'));
        
        // Add necessary scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_tab_scripts'));
        
        // Register handler for BuddyBoss ajax filtering
        add_filter('bp_ajax_querystring', array($this, 'handle_location_ajax_query_filter'), 20, 2);
        
        // Setup screen for location tab
        add_action('bp_screens', array($this, 'setup_location_screen'));
    }
    
    /**
     * Debug hooks
     */
    public function debug_before_tabs() {
        error_log('BB Location Finder - Before directory members tabs hook fired');
    }
    
    public function debug_after_tabs() {
        error_log('BB Location Finder - After directory members tabs hook fired');
    }
    
    /**
     * Setup screen for location tab
     */
    public function setup_location_screen() {
        if (bp_is_members_component() && !bp_is_user() && isset($_GET['type']) && $_GET['type'] === 'location-search') {
            add_filter('bp_is_current_component', array($this, 'force_members_component'), 10, 2);
            add_filter('bp_get_current_member_type', array($this, 'force_location_member_type'));
            add_action('bp_template_content', array($this, 'location_tab_template_content'));
        }
    }
    
    /**
     * Force members component for our tab
     */
    public function force_members_component($is_current_component, $component) {
        if ($component === 'members') {
            return true;
        }
        return $is_current_component;
    }
    
    /**
     * Force our tab as the current member type
     */
    public function force_location_member_type($member_type) {
        return 'location-search';
    }
    
    /**
     * Handle location-search as a custom filter for BP Ajax
     */
    public function handle_location_ajax_query_filter($query_string, $object) {
        // Only process for members component
        if ($object !== 'members') {
            return $query_string;
        }
        
        // Check if our filter is active
        if (isset($_POST['filter']) && $_POST['filter'] === 'location-search') {
            // Here you would typically modify the query
            // But since we're completely replacing the content, we'll just
            // return an empty query that will return no results
            return 'type=location-search&per_page=1&page=0';
        }
        
        return $query_string;
    }
    
    /**
     * Template content for location tab via BP's template system
     */
    public function location_tab_template_content() {
        // This will be shown when BuddyPress loads templates directly
        $this->location_search_content();
    }
    
    /**
     * Backward compatibility for BuddyBoss 1.x
     */
    public function add_location_tab_legacy() {
        $this->add_location_tab();
    }
    
    /**
     * Add tab for BuddyBoss 2.x with Nouveau template
     */
    public function add_location_tab_nouveau() {
        $is_active = $this->is_location_tab_active();
        
        // Add the tab
        ?>
        <li id="members-location-search" class="<?php echo $is_active ? 'selected current' : ''; ?>">
            <a href="<?php echo esc_url(add_query_arg('type', 'location-search', bp_get_members_directory_permalink())); ?>" 
               id="location-search" 
               data-bp-filter="location-search" 
               data-bp-object="members">
                <?php _e('Location Search', 'bb-location-finder'); ?>
            </a>
        </li>
        <?php
    }
    
    /**
     * Add location tab to members directory - standard method
     */
    public function add_location_tab() {
        $is_active = $this->is_location_tab_active();
        
        // Only add tab on members directory
        if (bp_is_user() || !bp_is_members_component()) {
            return;
        }
        
        ?>
        <li id="members-location-search" class="<?php echo $is_active ? 'selected' : ''; ?>">
            <a href="<?php echo esc_url(add_query_arg('type', 'location-search', bp_get_members_directory_permalink())); ?>" 
               data-bp-filter="location-search" 
               data-bp-object="members">
                <?php _e('Location Search', 'bb-location-finder'); ?>
            </a>
        </li>
        <?php
    }
    
    /**
     * Check if location tab is active
     */
    private function is_location_tab_active() {
        $is_active = false;
        
        // Check URL parameter
        if (isset($_GET['type']) && $_GET['type'] === 'location-search') {
            $is_active = true;
        }
        
        // Check cookie
        if (isset($_COOKIE['bp-members-filter']) && $_COOKIE['bp-members-filter'] === 'location-search') {
            $is_active = true;
        }
        
        // Check BP's internal state
        if (function_exists('bp_get_current_member_type') && bp_get_current_member_type() === 'location-search') {
            $is_active = true;
        }
        
        // Check AJAX request
        if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['filter']) && $_POST['filter'] === 'location-search') {
            $is_active = true;
        }
        
        return $is_active;
    }
    
    /**
     * Display the location search content when the tab is active
     */
    public function location_search_content() {
        // Add a log to check if this function is being called
        error_log('BB Location Finder - Tab content function called');
        
        // Only show content if this tab is active
        if (!$this->is_location_tab_active()) {
            return;
        }
        
        // Log that we're displaying the tab content
        error_log('BB Location Finder - Displaying location tab content');
        
        // Get shortcode attributes from options or use defaults
        $radius_options = apply_filters('bb_location_tab_radius_options', '5,10,25,50,100');
        $unit = apply_filters('bb_location_tab_unit', 'km');
        $results_per_page = apply_filters('bb_location_tab_results_per_page', '10');
        $show_profile_type_filter = apply_filters('bb_location_tab_show_profile_filter', 'yes');
        
        // Create search form
        echo '<div class="bb-location-tab-container">';
        echo '<div class="bb-location-search-container">';
        echo '<form id="bb-tab-location-search-form">';
        echo wp_nonce_field('bb_tab_location_search_nonce', 'search_nonce', true, false);
        
        echo '<div class="search-fields">';
        echo '<div class="form-field">';
        echo '<label for="bb_tab_search_location">' . __('Location', 'bb-location-finder') . '</label>';
        echo '<input type="text" id="bb_tab_search_location" name="location" placeholder="' . esc_attr__('Enter city, state, or country', 'bb-location-finder') . '" />';
        echo '</div>';
        
        // Prepare radius options
        $radius_values = explode(',', $radius_options);
        $radius_options_html = '';
        foreach ($radius_values as $value) {
            $value = trim($value);
            $radius_options_html .= '<option value="' . esc_attr($value) . '">' . esc_html($value) . '</option>';
        }
        
        // Unit display
        $unit_display = ($unit == 'mi') ? __('miles', 'bb-location-finder') : __('kilometers', 'bb-location-finder');
        
        echo '<div class="form-field">';
        echo '<label for="bb_tab_search_radius">' . __('Radius', 'bb-location-finder') . '<span class="unit" style="font-weight: normal"> (' . esc_html($unit_display) . ')</span></label>';
        echo '<select id="bb_tab_search_radius" name="radius">';
        echo $radius_options_html;
        echo '</select>';
        echo '</div>';
        
        echo '<div class="form-field">';
        echo '<button type="submit" class="button">' . __('Search', 'bb-location-finder') . '</button>';
        echo '</div>';
        echo '</div>';
        
        // Add filter container (hidden initially)
        echo '<div class="filter-container" style="display:none; margin-top:15px;">';
        
        // Add name search filter
        echo '<div class="form-field">';
        echo '<label for="bb_tab_name_search">' . __('Filter by Name', 'bb-location-finder') . '</label>';
        echo '<input type="text" id="bb_tab_name_search" name="name_search" placeholder="' . esc_attr__('Enter member name', 'bb-location-finder') . '" />';
        echo '</div>';
        
        // Add profile type filter if enabled
        if ($show_profile_type_filter === 'yes' && function_exists('bp_get_member_types')) {
            $member_types = bp_get_member_types(array(), 'objects');
            
            if (!empty($member_types)) {
                echo '<div class="form-field profile-type-filter">';
                echo '<label for="bb_tab_profile_type_filter">' . __('Filter by Profile Type', 'bb-location-finder') . '</label>';
                echo '<select id="bb_tab_profile_type_filter" name="profile_type">';
                echo '<option value="">' . __('All Profile Types', 'bb-location-finder') . '</option>';
                
                foreach ($member_types as $type => $object) {
                    echo '<option value="' . esc_attr($type) . '">' . esc_html($object->labels['singular_name']) . '</option>';
                }
                
                echo '</select>';
                echo '</div>';
            }
        }
        
        // Add Apply Filter button
        echo '<div class="form-field filter-button">';
        echo '<button type="button" id="bb-tab-apply-filters" class="button">' . __('Apply Filters', 'bb-location-finder') . '</button>';
        echo '</div>';
        
        echo '</div>'; // End filter container
        
        echo '<input type="hidden" name="unit" value="' . esc_attr($unit) . '" />';
        echo '<input type="hidden" name="show_map" value="no" />'; // Always set to no in tab view
        echo '<input type="hidden" name="results_per_page" value="' . esc_attr($results_per_page) . '" />';
        echo '<input type="hidden" name="current_page" value="1" />';
        echo '</form>';
        
        // Results container
        echo '<div id="bb-tab-location-results" class="location-results">';
        echo '<div class="result-count"></div>';
        echo '<div class="result-container">';
        echo '<div id="bb-tab-location-users" class="user-results"></div>';
        echo '</div>';
        
        // Pagination container
        echo '<div id="bb-tab-location-pagination" class="location-pagination" style="display:none;"></div>';
        
        echo '</div>'; // End results
        echo '</div>'; // End search container
        echo '</div>'; // End tab container
    }
    
    /**
     * Enqueue necessary scripts and styles for the location tab
     */
    public function enqueue_tab_scripts() {
        // Only enqueue on members directory page
        if (!bp_is_members_component() || bp_is_user()) {
            return;
        }
        
        // Enqueue main CSS
        wp_enqueue_style('bb-location-finder-styles');
        
        // Enqueue Google Maps for autocomplete
        wp_enqueue_script('google-maps');
        wp_enqueue_script('bb-location-finder-js');
        
        // Add custom JS for tab-specific functionality
        wp_add_inline_script('bb-location-finder-js', $this->get_tab_inline_js());
    }
    
    /**
     * Get tab-specific JavaScript
     */
    private function get_tab_inline_js() {
        ob_start();
        ?>
        jQuery(document).ready(function($) {
            // Debug log function
            function bbTabDebug(message, data) {
                if (typeof console !== 'undefined') {
                    console.log('[BB Location Tab] ' + message, data || '');
                }
            }
            
            bbTabDebug('Tab JavaScript loaded');
            
            // Check if we're on the location search tab
            function isLocationTab() {
                if (window.location.href.indexOf('type=location-search') > -1) {
                    bbTabDebug('Location tab active via URL parameter');
                    return true;
                }
                
                if (document.cookie.indexOf('bp-members-filter=location-search') > -1) {
                    bbTabDebug('Location tab active via cookie');
                    return true;
                }
                
                if ($('#members-location-search').hasClass('selected') || $('#members-location-search').hasClass('current')) {
                    bbTabDebug('Location tab active via class');
                    return true;
                }
                
                bbTabDebug('Location tab not active');
                return false;
            }
            
            // Fix for BuddyBoss tab switching
            $(document).on('click', '#members-location-search a', function(e) {
                bbTabDebug('Location tab clicked');
                e.preventDefault();
                
                // Set cookie for BuddyBoss filter
                document.cookie = 'bp-members-filter=location-search; path=/';
                
                // Update URL without refreshing
                history.pushState(null, null, $(this).attr('href'));
                
                // Hide default members list and show our container
                $('#members-dir-list').hide();
                $('.bb-location-tab-container').show();
                
                // Activate our tab
                $('.bp-navs ul li').removeClass('selected current');
                $('#members-location-search').addClass('selected current');
                
                return false;
            });
            
            // If we're on the location tab, hide the standard member directory content
            if (isLocationTab()) {
                bbTabDebug('Hiding standard members directory content');
                $('#members-dir-list').hide();
                $('.bb-location-tab-container').show();
            }
            
            // Handle search form submission
            $(document).on('submit', '#bb-tab-location-search-form', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $results = $('#bb-tab-location-results');
                
                bbTabDebug('Location search form submitted');
                
                // Show loading indicator
                $results.addClass('loading').html('<div class="loading-indicator">Searching...</div>');
                
                // Get form data
                var formData = {
                    action: 'bb_tab_location_search',
                    nonce: $form.find('[name="search_nonce"]').val(),
                    location: $form.find('[name="location"]').val(),
                    radius: $form.find('[name="radius"]').val(),
                    unit: $form.find('[name="unit"]').val(),
                    results_per_page: $form.find('[name="results_per_page"]').val()
                };
                
                bbTabDebug('Search form data', formData);
                
                // Make AJAX request
                $.ajax({
                    url: bbLocationFinderVars.ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $results.removeClass('loading');
                        
                        if (response.success) {
                            bbTabDebug('Search successful', response.data);
                            displayTabSearchResults(response.data);
                        } else {
                            bbTabDebug('Search error', response.data);
                            $results.html('<div class="search-error">' + response.data.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        bbTabDebug('AJAX error', { status: status, error: error });
                        $results.removeClass('loading');
                        $results.html('<div class="search-error">' + bbLocationFinderVars.strings.search_error + '</div>');
                    }
                });
            });
            
            // Handle name filter input
            $(document).on('input', '#bb_tab_name_search', function() {
                var searchValue = $(this).val().toLowerCase();
                
                if (window.tabAllUsers) {
                    // First filter by name
                    if (searchValue.length === 0) {
                        window.tabFilteredByName = window.tabAllUsers.slice();
                    } else {
                        window.tabFilteredByName = window.tabAllUsers.filter(function(user) {
                            return user.name.toLowerCase().indexOf(searchValue) !== -1;
                        });
                    }
                    
                    // Then apply profile type filter if selected
                    var selectedType = $('#bb_tab_profile_type_filter').val();
                    if (!selectedType || selectedType === '') {
                        window.tabFilteredUsers = window.tabFilteredByName.slice();
                    } else {
                        window.tabFilteredUsers = window.tabFilteredByName.filter(function(user) {
                            return user.profile_type === selectedType;
                        });
                    }
                    
                    // Reset to first page
                    window.tabCurrentPage = 1;
                    $('input[name="current_page"]').val(1);
                    
                    // Display filtered results
                    displayTabUserResults(window.tabFilteredUsers);
                }
            });
            
            // Handle profile type filter change
            $(document).on('change', '#bb_tab_profile_type_filter', function() {
                var selectedType = $(this).val();
                
                if (window.tabAllUsers && window.tabFilteredByName) {
                    // Filter by profile type
                    if (!selectedType || selectedType === '') {
                        window.tabFilteredUsers = window.tabFilteredByName.slice();
                    } else {
                        window.tabFilteredUsers = window.tabFilteredByName.filter(function(user) {
                            return user.profile_type === selectedType;
                        });
                    }
                    
                    // Reset to first page
                    window.tabCurrentPage = 1;
                    $('input[name="current_page"]').val(1);
                    
                    // Display filtered results
                    displayTabUserResults(window.tabFilteredUsers);
                }
            });
            
            // Apply filters button
            $(document).on('click', '#bb-tab-apply-filters', function() {
                if (window.tabAllUsers) {
                    applyTabFilters();
                }
            });
            
            // Pagination click handler
            $(document).on('click', '#bb-tab-location-pagination .page-number', function(e) {
                e.preventDefault();
                
                if (window.tabFilteredUsers) {
                    window.tabCurrentPage = parseInt($(this).data('page'));
                    $('input[name="current_page"]').val(window.tabCurrentPage);
                    
                    // Display current page
                    displayTabUserResults(window.tabFilteredUsers);
                    
                    // Scroll to results
                    $('html, body').animate({
                        scrollTop: $('#bb-tab-location-results').offset().top - 50
                    }, 200);
                }
            });
            
            // Setup location field autocomplete if on the tab
            if (isLocationTab()) {
                setTimeout(function() {
                    var searchField = document.getElementById('bb_tab_search_location');
                    
                    if (searchField && typeof google !== 'undefined' && 
                        typeof google.maps !== 'undefined' && 
                        typeof google.maps.places !== 'undefined') {
                        
                        bbTabDebug('Setting up location autocomplete');
                        
                        var searchAutocomplete = new google.maps.places.Autocomplete(searchField, {
                            types: ['(cities)']
                        });
                        
                        searchAutocomplete.addListener('place_changed', function() {
                            var place = searchAutocomplete.getPlace();
                            
                            if (!place.geometry) {
                                bbTabDebug('No place geometry in autocomplete response');
                                return;
                            }
                            
                            bbTabDebug('Place selected', place.formatted_address);
                        });
                    } else {
                        bbTabDebug('Could not initialize autocomplete', { 
                            fieldExists: !!searchField,
                            googleExists: typeof google !== 'undefined',
                            mapsExists: typeof google !== 'undefined' && typeof google.maps !== 'undefined',
                            placesExists: typeof google !== 'undefined' && 
                                         typeof google.maps !== 'undefined' && 
                                         typeof google.maps.places !== 'undefined'
                        });
                    }
                }, 1000); // Short delay to ensure DOM is ready
            }
            
            // Helper function to apply all filters
            function applyTabFilters() {
                if (!window.tabAllUsers) {
                    return;
                }
                
                // Get filter values
                var nameFilter = $('#bb_tab_name_search').val().toLowerCase();
                var profileTypeFilter = $('#bb_tab_profile_type_filter').val();
                
                // Start with all users
                var filtered = window.tabAllUsers.slice();
                
                // Apply name filter if specified
                if (nameFilter.length > 0) {
                    filtered = filtered.filter(function(user) {
                        return user.name.toLowerCase().indexOf(nameFilter) !== -1;
                    });
                }
                
                // Apply profile type filter if specified
                if (profileTypeFilter && profileTypeFilter !== '') {
                    filtered = filtered.filter(function(user) {
                        return user.profile_type === profileTypeFilter;
                    });
                }
                
                // Update filtered users
                window.tabFilteredUsers = filtered;
                
                // Reset to first page
                window.tabCurrentPage = 1;
                $('input[name="current_page"]').val(1);
                
                // Display filtered results
                displayTabUserResults(window.tabFilteredUsers);
                
                // Update result count text
                var countText = window.tabFilteredUsers.length + ' ' + 
                    (window.tabFilteredUsers.length === 1 ? bbLocationFinderVars.strings.member : bbLocationFinderVars.strings.members) + 
                    ' ' + bbLocationFinderVars.strings.found;
                $('#bb-tab-location-results .result-count').text(countText);
            }
            
            // Function to display search results
            function displayTabSearchResults(data) {
                var $results = $('#bb-tab-location-results');
                var $resultCount = $('<div class="result-count"></div>');
                
                bbTabDebug('Displaying search results', { count: data.count });
                
                // Store data for filtering/pagination
                window.tabAllUsers = data.users || [];
                window.tabFilteredUsers = data.users ? data.users.slice() : [];
                window.tabCurrentPage = 1;
                
                // Clear previous results
                $results.empty();
                
                // Add result count
                var countText = data.count + ' ' + 
                    (data.count === 1 ? bbLocationFinderVars.strings.member : bbLocationFinderVars.strings.members) + 
                    ' ' + bbLocationFinderVars.strings.found;
                $resultCount.text(countText);
                $results.append($resultCount);
                
                // Create result container
                var $resultContainer = $('<div class="result-container"></div>');
                $results.append($resultContainer);
                
                // Add users container
                var $userResults = $('<div id="bb-tab-location-users" class="user-results"></div>');
                $resultContainer.append($userResults);
                
                // Add pagination container if it doesn't exist
                if ($('#bb-tab-location-pagination').length === 0) {
                    $results.append('<div id="bb-tab-location-pagination" class="location-pagination"></div>');
                }
                
                // Show filters if we have results
                if (data.count > 0) {
                    $('.filter-container').show();
                } else {
                    $('.filter-container').hide();
                }
                
                // Display users with pagination
                displayTabUserResults(window.tabFilteredUsers);
            }
            
            // Function to display user results with pagination
            function displayTabUserResults(users) {
                if (!users) {
                    bbTabDebug('No users to display');
                    return;
                }
                
                bbTabDebug('Displaying user results', { count: users.length });
                
                var $userResults = $('#bb-tab-location-users');
                $userResults.empty();
                
                // Show no results message if needed
                if (users.length === 0) {
                    $userResults.html('<div class="no-results">' + bbLocationFinderVars.strings.no_results + '</div>');
                    $('#bb-tab-location-pagination').hide();
                    return;
                }
                
                // Calculate pagination
                var resultsPerPage = parseInt($('input[name="results_per_page"]').val()) || 10;
                var currentPage = window.tabCurrentPage || 1;
                var totalPages = Math.ceil(users.length / resultsPerPage);
                var startIndex = (currentPage - 1) * resultsPerPage;
                var endIndex = Math.min(startIndex + resultsPerPage, users.length);
                var pageUsers = users.slice(startIndex, endIndex);
                
                // Add user results
                $.each(pageUsers, function(index, user) {
                    var locationDisplay = user.location.join(', ');
                    var distanceText = user.distance + ' ' + (user.unit === 'mi' ? 'miles' : 'km') + ' away';
                    
                    var $userItem = $('<div class="user-item"></div>').attr('data-id', user.id);
                    if (user.profile_type) {
                        $userItem.attr('data-profile-type', user.profile_type);
                    }
                    
                    var $avatar = $('<div class="user-avatar"><img src="' + user.avatar + '" alt=""></div>');
                    var $info = $('<div class="user-info"></div>');
                    
                    $info.append('<h4><a href="' + user.profile_url + '">' + user.name + '</a></h4>');
                    
                    // Add profile type badge if available
                    if (user.profile_type_label) {
                        $info.append('<span class="profile-type-badge">' + user.profile_type_label + '</span>');
                    }
                    
                    $info.append('<p class="user-location">' + locationDisplay + '</p>');
                    $info.append('<p class="user-distance">' + distanceText + '</p>');
                    
                    $userItem.append($avatar).append($info);
                    $userResults.append($userItem);
                });
                
                // After all items are added, ensure proper wrapping with clearfix
                $userResults.append('<div style="clear:both;"></div>');
                
                // Update pagination
                updateTabPagination(totalPages, currentPage);
            }
            
            // Function to update pagination UI
            function updateTabPagination(totalPages, currentPage) {
                var $pagination = $('#bb-tab-location-pagination');
                $pagination.empty();
                
                if (totalPages <= 1) {
                    $pagination.hide();
                    return;
                }
                
                $pagination.show();
                
                // Previous button
                if (currentPage > 1) {
                    $pagination.append('<a href="#" class="page-number prev" data-page="' + (currentPage - 1) + '">' + '&laquo; ' + 'Previous' + '</a>');
                }
                
                // Page numbers
                var startPage = Math.max(1, currentPage - 2);
                var endPage = Math.min(totalPages, startPage + 4);
                
                if (endPage - startPage < 4 && startPage > 1) {
                    startPage = Math.max(1, endPage - 4);
                }
                
                for (var i = startPage; i <= endPage; i++) {
                    var $pageLink = $('<a href="#" class="page-number" data-page="' + i + '">' + i + '</a>');
                    
                    if (i === currentPage) {
                        $pageLink.addClass('current');
                    }
                    
                    $pagination.append($pageLink);
                }
                
                // Next button
                if (currentPage < totalPages) {
                    $pagination.append('<a href="#" class="page-number next" data-page="' + (currentPage + 1) + '">' + 'Next' + ' &raquo;</a>');
                }
            }
        });
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for tab location search
     */
    public function ajax_tab_location_search() {
        // Check nonce
        check_ajax_referer('bb_tab_location_search_nonce', 'nonce');
        
        $location = sanitize_text_field($_POST['location']);
        $radius = floatval($_POST['radius']);
        $unit = sanitize_text_field($_POST['unit']);
        $results_per_page = isset($_POST['results_per_page']) ? intval($_POST['results_per_page']) : 10;
        
        if (empty($location) || $radius <= 0) {
            wp_send_json_error(array('message' => __('Invalid search parameters', 'bb-location-finder')));
        }
        
        // Geocode the search location
        $geocoder = new BB_Location_Geocoding();
        $coordinates = $geocoder->geocode_address($location);
        
        if (!$coordinates) {
            wp_send_json_error(array(
                'message' => __('Could not find the location', 'bb-location-finder'),
                'location' => $location
            ));
        }
        
        // Get search instance
        $search = new BB_Location_Search();
        
        // Find users within the radius
        $users = $search->find_users_by_distance($coordinates['lat'], $coordinates['lng'], $radius, $unit);
        
        // Format results for response (same as standard search)
        $formatted_users = array();
        
        foreach ($users as $user) {
            // Skip current user
            if ($user->ID == get_current_user_id()) {
                continue;
            }
            
            $location_parts = array_filter(array($user->city, $user->state, $user->country));
            
            // Get user's profile type
            $profile_type = '';
            $profile_type_label = '';
            
            if (function_exists('bp_get_member_type')) {
                $member_types = bp_get_member_type($user->ID, false);
                if (!empty($member_types) && is_array($member_types)) {
                    $profile_type = $member_types[0]; // Just use the first one if multiple exist
                    
                    // Get the label
                    $type_object = bp_get_member_type_object($profile_type);
                    if ($type_object) {
                        $profile_type_label = $type_object->labels['singular_name'];
                    }
                }
            }
            
            $formatted_users[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'location' => $location_parts,
                'distance' => round($user->distance, 1),
                'profile_url' => bp_core_get_user_domain($user->ID),
                'avatar' => bp_core_fetch_avatar(array(
                    'item_id' => $user->ID,
                    'type' => 'thumb',
                    'html' => false
                )),
                'lat' => $user->lat,
                'lng' => $user->lng,
                'unit' => $unit,
                'profile_type' => $profile_type,
                'profile_type_label' => $profile_type_label
            );
        }
        
        wp_send_json_success(array(
            'users' => $formatted_users,
            'center' => $coordinates,
            'count' => count($formatted_users),
            'unit' => $unit
        ));
    }
}

// Initialize the tab integration
add_action('bp_init', function() {
    if (class_exists('BB_Location_Finder')) {
        new BB_Location_Tab_Integration();
        
        // Log that we've initialized the tab integration
        error_log('BB Location Finder - Tab integration initialized');
    }
});