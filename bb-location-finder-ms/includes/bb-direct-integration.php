<?php
// Create a new file: includes/bb-direct-integration.php

/**
 * Direct integration with BuddyBoss for Location Finder
 */
class BB_Direct_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add our custom tab using BuddyBoss's preferred method
        add_action('bp_nouveau_get_members_directory_nav_items', array($this, 'add_location_tab_to_nav_items'), 10, 1);
        
        // Filter the members directory nav
        add_filter('bp_nouveau_get_nav_directories', array($this, 'add_location_directory'), 10, 1);

        // Register JavaScript to help with tab switching
        add_action('wp_footer', array($this, 'add_tab_script'));
        
        // Add AJAX handler for location search in tab
        add_action('wp_ajax_bb_direct_location_search', array($this, 'ajax_location_search'));
        add_action('wp_ajax_nopriv_bb_direct_location_search', array($this, 'ajax_location_search'));
        
        // Handle the tab content display
        add_action('bp_template_content', array($this, 'location_tab_content'));
        
        // Handle tab selection
        add_action('bp_template_include_reset_dummy_post_data', array($this, 'maybe_set_location_tab'), 10);
    }
    
    /**
     * Add our tab to BuddyBoss nav items
     */
    public function add_location_tab_to_nav_items($nav_items) {
        // Add our location search tab
        $nav_items['location-search'] = array(
            'component' => 'members',
            'slug' => 'location-search', 
            'li_class' => array('dynamic'),
            'link' => bp_get_members_directory_permalink(),
            'text' => __('Location Search', 'bb-location-finder'),
            'count' => false,
            'position' => 50 // Position after default tabs
        );
        
        return $nav_items;
    }
    
    /**
     * Add location directory to BP Nouveau nav directories
     */
    public function add_location_directory($directories) {
        if (!isset($directories['members'])) {
            return $directories;
        }
        
        // Make sure our tab is part of the members component
        if (!isset($directories['members']['location-search'])) {
            $directories['members']['location-search'] = array(
                'tab' => __('Location Search', 'bb-location-finder'),
                'has_directory' => true
            );
        }
        
        return $directories;
    }
    
    /**
     * Add tab selection script to footer
     */
    public function add_tab_script() {
        // Only on members directory
        if (!bp_is_members_component() || bp_is_user()) {
            return;
        }
        
        // Add CSS to ensure tab visibility
        echo '<style>
            .bp-navs ul li a[data-bp-item="location-search"] {
                display: block !important;
            }
            #buddypress div#subnav.item-list-tabs#subnav a.location-search,
            .buddypress-wrap .bp-navs li:not(.selected) a.location-search,
            .buddypress #buddypress .bp-navs li > a.location-search,
            .buddypress-wrap .bp-navs li > a.location-search {
                display: flex !important;
                align-items: center;
            }
            
            /* Hide location content when not active */
            body:not(.location-search-active) .location-search-container {
                display: none !important;
            }
            
            /* Hide normal content when location tab is active */
            body.location-search-active #members-dir-list {
                display: none !important;
            }
            
            /* Show location content when tab is active */
            body.location-search-active .location-search-container {
                display: block !important;
                margin-top: 20px;
            }
            
            /* User results styling for BuddyBoss compatibility */
            .location-search-container .user-results {
                width: auto;
                margin-top: 15px;
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            @media (max-width: 767px) {
                .location-search-container .user-results {
                    grid-template-columns: 1fr; /* Single column on mobile */
                }
            }
            
            .location-search-container .user-item {
                margin: 0;
                padding: 15px;
                border-radius: 4px;
                box-shadow: 0 2px 7px 0 rgba(0,0,0,0.1);
                background: #fff;
                display: flex;
                align-items: center;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            
            .location-search-container .user-item:hover {
                transform: translateY(-3px);
                box-shadow: 0 5px 15px 0 rgba(0,0,0,0.15);
            }
            
            .location-search-container .result-count {
                margin-bottom: 15px;
                font-weight: bold;
            }
            
            #members-location-search a[data-bp-item="location-search"] {
                display: inline-block !important;
            }
        </style>';
        
        // Add JavaScript for tab handling
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('BuddyBoss Direct Integration script loaded');
            
            // Check if location search tab is active
            function isLocationTab() {
                // Check if URL indicates location tab
                if (window.location.href.indexOf('/?type=location-search') > -1 || 
                    window.location.href.indexOf('/?members-type=location-search') > -1 ||
                    window.location.href.indexOf('&type=location-search') > -1) {
                    return true;
                }
                
                // Check if location tab has 'selected' or 'current' class
                if ($('.bp-navs .location-search').hasClass('selected') || 
                    $('.bp-navs .location-search').hasClass('current') ||
                    $('.bp-navs a[data-bp-item="location-search"]').parent().hasClass('selected') ||
                    $('.bp-navs a[data-bp-item="location-search"]').parent().hasClass('current')) {
                    return true;
                }
                
                // Check if tab was previously selected
                if (localStorage.getItem('bp-location-tab-active') === 'true') {
                    return true;
                }
                
                return false;
            }
            
            // Initial check and setup
            if (isLocationTab()) {
                $('body').addClass('location-search-active');
                localStorage.setItem('bp-location-tab-active', 'true');
                
                // Set tab as active
                $('.bp-navs li').removeClass('selected current');
                $('.bp-navs li a[data-bp-item="location-search"]').parent().addClass('selected current');
                $('.bp-navs li.location-search').addClass('selected current');
                
                // Show location content
                $('#members-dir-list').hide();
                $('.location-search-container').show();
            } else {
                localStorage.removeItem('bp-location-tab-active');
            }
            
            // Handle tab click events
            $(document).on('click', '.bp-navs li a[data-bp-item="location-search"], .bp-navs li.location-search a', function(e) {
                console.log('Location tab clicked');
                e.preventDefault();
                
                // Set as active
                $('body').addClass('location-search-active');
                localStorage.setItem('bp-location-tab-active', 'true');
                
                // Update tab classes
                $('.bp-navs li').removeClass('selected current');
                $(this).parent().addClass('selected current');
                
                // Show location content
                $('#members-dir-list').hide();
                $('.location-search-container').show();
                
                // Update URL without reloading
                history.pushState(null, null, $(this).attr('href') || window.location.pathname + '?type=location-search');
                
                return false;
            });
            
            // Handle other tab clicks to deactivate location tab
            $(document).on('click', '.bp-navs li a:not([data-bp-item="location-search"])', function() {
                if ($(this).parent().hasClass('location-search')) {
                    return;
                }
                
                $('body').removeClass('location-search-active');
                localStorage.removeItem('bp-location-tab-active');
            });
            
            // Handle form submission
            $(document).on('submit', '#bb-location-search-form', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $results = $('#bb-location-results');
                
                // Show loading indicator
                $results.addClass('loading').html('<div class="loading-indicator">Searching...</div>');
                
                // Get form data
                var formData = {
                    action: 'bb_direct_location_search',
                    nonce: $('#location_search_nonce').val(),
                    location: $form.find('[name="location"]').val(),
                    radius: $form.find('[name="radius"]').val(),
                    unit: $form.find('[name="unit"]').val(),
                    results_per_page: 10
                };
                
                // Make AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $results.removeClass('loading');
                        
                        if (response.success) {
                            displaySearchResults(response.data);
                        } else {
                            $results.html('<div class="search-error">' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        $results.removeClass('loading');
                        $results.html('<div class="search-error">Error searching for members. Please try again.</div>');
                    }
                });
            });
            
            // Handle name filter
            $(document).on('input', '#bb_name_search', function() {
                var searchValue = $(this).val().toLowerCase();
                
                if (window.locationAllUsers) {
                    // Filter users by name
                    if (searchValue.length === 0) {
                        window.locationFilteredUsers = window.locationAllUsers.slice();
                    } else {
                        window.locationFilteredUsers = window.locationAllUsers.filter(function(user) {
                            return user.name.toLowerCase().indexOf(searchValue) !== -1;
                        });
                    }
                    
                    // Reset to first page and display results
                    window.locationCurrentPage = 1;
                    displayUserResults(window.locationFilteredUsers);
                }
            });
            
            // Handle pagination
            $(document).on('click', '.location-pagination .page-number', function(e) {
                e.preventDefault();
                
                if (window.locationFilteredUsers) {
                    window.locationCurrentPage = parseInt($(this).data('page'));
                    
                    // Display current page
                    displayUserResults(window.locationFilteredUsers);
                    
                    // Scroll to results
                    $('html, body').animate({
                        scrollTop: $('#bb-location-results').offset().top - 50
                    }, 200);
                }
            });
            
            // Display search results
            function displaySearchResults(data) {
                var $results = $('#bb-location-results');
                var $resultCount = $('<div class="result-count"></div>');
                
                // Store data for filtering/pagination
                window.locationAllUsers = data.users || [];
                window.locationFilteredUsers = data.users ? data.users.slice() : [];
                window.locationCurrentPage = 1;
                
                // Clear previous results
                $results.empty();
                
                // Add result count
                var countText = data.count + ' ' + 
                    (data.count === 1 ? 'member' : 'members') + 
                    ' found';
                $resultCount.text(countText);
                $results.append($resultCount);
                
                // Create result container
                var $resultContainer = $('<div class="result-container"></div>');
                $results.append($resultContainer);
                
                // Add users container
                var $userResults = $('<div id="bb-location-users" class="user-results"></div>');
                $resultContainer.append($userResults);
                
                // Add pagination container if it doesn't exist
                if ($('#bb-location-pagination').length === 0) {
                    $results.append('<div id="bb-location-pagination" class="location-pagination"></div>');
                }
                
                // Show filters if we have results
                if (data.count > 0) {
                    $('.filter-container').show();
                } else {
                    $('.filter-container').hide();
                }
                
                // Display users with pagination
                displayUserResults(window.locationFilteredUsers);
            }
            
            // Display user results with pagination
            function displayUserResults(users) {
                if (!users) {
                    return;
                }
                
                var $userResults = $('#bb-location-users');
                $userResults.empty();
                
                // Show no results message if needed
                if (users.length === 0) {
                    $userResults.html('<div class="no-results">No members found in this area.</div>');
                    $('#bb-location-pagination').hide();
                    return;
                }
                
                // Calculate pagination
                var resultsPerPage = 10;
                var currentPage = window.locationCurrentPage || 1;
                var totalPages = Math.ceil(users.length / resultsPerPage);
                var startIndex = (currentPage - 1) * resultsPerPage;
                var endIndex = Math.min(startIndex + resultsPerPage, users.length);
                var pageUsers = users.slice(startIndex, endIndex);
                
                // Add user results
                $.each(pageUsers, function(index, user) {
                    var locationDisplay = user.location.join(', ');
                    var distanceText = user.distance + ' ' + (user.unit === 'mi' ? 'miles' : 'km') + ' away';
                    
                    var $userItem = $('<div class="user-item"></div>').attr('data-id', user.id);
                    
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
                
                // Update pagination
                updatePagination(totalPages, currentPage);
            }
            
            // Update pagination UI
            function updatePagination(totalPages, currentPage) {
                var $pagination = $('#bb-location-pagination');
                $pagination.empty();
                
                if (totalPages <= 1) {
                    $pagination.hide();
                    return;
                }
                
                $pagination.show();
                
                // Previous button
                if (currentPage > 1) {
                    $pagination.append('<a href="#" class="page-number prev" data-page="' + (currentPage - 1) + '">&laquo; Previous</a>');
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
                    $pagination.append('<a href="#" class="page-number next" data-page="' + (currentPage + 1) + '">Next &raquo;</a>');
                }
            }
        });
        </script>
        <?php
    }
    
    /**
     * Set location tab active if needed
     */
    public function maybe_set_location_tab() {
        if (!bp_is_members_component() || bp_is_user()) {
            return;
        }
        
        // Check if our tab is being requested
        if (isset($_GET['type']) && $_GET['type'] === 'location-search') {
            add_filter('bp_get_template_part', array($this, 'load_location_template'), 10, 3);
            add_action('bp_directory_members_item', array($this, 'location_tab_content'), 9);
        }
    }
    
    /**
     * Load our template if location tab is active
     */
    public function load_location_template($templates, $slug, $name) {
        if ($slug === 'members/members-loop' && !bp_is_user()) {
            // Add our content before the loop
            add_action('bp_before_directory_members_list', array($this, 'location_tab_content'));
            
            // Hide the regular members list
            add_action('bp_before_directory_members_list', function() {
                echo '<style>#members-dir-list .item-list { display: none; }</style>';
            });
        }
        
        return $templates;
    }
    
    /**
     * Location tab content
     */
    public function location_tab_content() {
        // Make sure this only runs once
        static $rendered = false;
        if ($rendered) {
            return;
        }
        $rendered = true;
        
        // Get settings via filters
        $radius_options = apply_filters('bb_location_tab_radius_options', '5,10,25,50,100');
        $unit = apply_filters('bb_location_tab_unit', 'km');
        
        // Unit display
        $unit_display = ($unit == 'mi') ? __('miles', 'bb-location-finder') : __('kilometers', 'bb-location-finder');
        
        // Create radius options HTML
        $radius_values = explode(',', $radius_options);
        $radius_html = '';
        foreach ($radius_values as $value) {
            $value = trim($value);
            $radius_html .= '<option value="' . esc_attr($value) . '">' . esc_html($value) . '</option>';
        }
        
        // Output the form
        ?>
        <div class="location-search-container">
            <form id="bb-location-search-form">
                <?php wp_nonce_field('bb_location_search_nonce', 'location_search_nonce'); ?>
                
                <div class="bp-search-form-wrapper">
                    <div class="form-field-wrapper">
                        <div class="bb-search-form">
                            <div class="location-search-fields">
                                <div class="form-field">
                                    <label for="bb_search_location"><?php _e('Location', 'bb-location-finder'); ?></label>
                                    <input type="text" id="bb_search_location" name="location" placeholder="<?php esc_attr_e('Enter city, state, or country', 'bb-location-finder'); ?>" />
                                </div>
                                
                                <div class="form-field">
                                    <label for="bb_search_radius"><?php _e('Radius', 'bb-location-finder'); ?> <span class="unit">(<?php echo esc_html($unit_display); ?>)</span></label>
                                    <select id="bb_search_radius" name="radius">
                                        <?php echo $radius_html; ?>
                                    </select>
                                </div>
                                
                                <div class="form-field">
                                    <button type="submit" class="button bp-search-button">
                                        <?php _e('Search', 'bb-location-finder'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter container -->
                <div class="filter-container" style="display:none;">
                    <div class="form-field">
                        <label for="bb_name_search"><?php _e('Filter by Name', 'bb-location-finder'); ?></label>
                        <input type="text" id="bb_name_search" name="name_search" placeholder="<?php esc_attr_e('Enter member name', 'bb-location-finder'); ?>" />
                    </div>
                </div>
                
                <input type="hidden" name="unit" value="<?php echo esc_attr($unit); ?>" />
            </form>
            
            <div id="bb-location-results" class="location-results">
                <!-- Results will be loaded here via AJAX -->
            </div>
        </div>
        
        <style>
            /* Extra styles for BuddyBoss compatibility */
            .location-search-container .bb-search-form {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-bottom: 20px;
            }
            
            .location-search-container .location-search-fields {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                width: 100%;
            }
            
            .location-search-container .form-field {
                flex: 1;
                min-width: 200px;
            }
            
            .location-search-container .form-field input,
            .location-search-container .form-field select {
                width: 100%;
                min-height: 40px;
                border: 1px solid #e7e9ec;
                padding: 8px 12px;
                border-radius: 4px;
                background-color: #fff;
            }
            
            .location-search-container .form-field button {
                min-height: 40px;
                padding: 8px 15px;
                border-radius: 4px;
                background-color: var(--bb-primary-color, #007CFF);
                color: white;
                border: none;
                cursor: pointer;
            }
            
            .user-item .user-avatar {
                margin-right: 15px;
            }
            
            .user-item .user-avatar img {
                width: 50px;
                height: 50px;
                border-radius: 50%;
            }
            
            .user-item .user-info {
                flex: 1;
            }
            
            .user-item .user-info h4 {
                margin: 0 0 5px;
            }
            
            .user-location {
                color: #666;
                margin: 0 0 5px;
            }
            
            .user-distance {
                margin: 0;
                font-style: italic;
                color: #888;
            }
            
            .filter-container {
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #eee;
                border-radius: 4px;
                margin-bottom: 20px;
            }
            
            .profile-type-badge {
                display: inline-block;
                background: #f0f0f0;
                color: #555;
                font-size: 12px;
                padding: 2px 8px;
                border-radius: 10px;
                margin-left: 8px;
                border: 1px solid #ddd;
                vertical-align: middle;
            }
            
            .location-pagination {
                margin-top: 20px;
                text-align: center;
            }
            
            .location-pagination .page-number {
                display: inline-block;
                padding: 5px 10px;
                margin: 0 3px;
                background: #f5f5f5;
                border: 1px solid #ddd;
                border-radius: 3px;
                text-decoration: none;
                color: #333;
            }
            
            .location-pagination .page-number.current {
                background: var(--bb-primary-color, #007CFF);
                color: white;
                border-color: var(--bb-primary-color, #007CFF);
            }
            
            .no-results,
            .search-error {
                padding: 20px;
                text-align: center;
                background-color: #f5f5f5;
                border-radius: 4px;
            }
            
            /* Loading indicator */
            .location-results.loading {
                position: relative;
                min-height: 100px;
            }
            
            .location-results.loading:before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.7);
                z-index: 1;
            }
            
            .location-results.loading:after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 40px;
                height: 40px;
                margin: -20px 0 0 -20px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid var(--bb-primary-color, #007CFF);
                border-radius: 50%;
                z-index: 2;
                animation: spin 1.5s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            /* Force necessary BuddyBoss elements to display properly */
            .bp-navs ul li a[data-bp-item="location-search"] {
                display: block !important;
                padding: 0 5px;
            }
            
            .bp-navs ul li.current a[data-bp-item="location-search"] {
                color: var(--bb-primary-color, #007CFF);
            }
        </style>
        
        <script>
            // Initialize Google Places Autocomplete
            document.addEventListener('DOMContentLoaded', function() {
                console.log('Attempting to initialize location autocomplete');
                
                var searchField = document.getElementById('bb_search_location');
                if (searchField && typeof google !== 'undefined' && typeof google.maps !== 'undefined' && typeof google.maps.places !== 'undefined') {
                    var searchAutocomplete = new google.maps.places.Autocomplete(searchField, {
                        types: ['(cities)']
                    });
                }
            });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for location search
     */
    public function ajax_location_search() {
        // Check nonce
        check_ajax_referer('bb_location_search_nonce', 'nonce');
        
        $location = sanitize_text_field($_POST['location']);
        $radius = floatval($_POST['radius']);
        $unit = sanitize_text_field($_POST['unit']);
        
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
        
        // Format results for response
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

// Initialize the direct integration
add_action('bp_init', function() {
    if (class_exists('BB_Location_Finder') && function_exists('bp_is_active')) {
        new BB_Direct_Integration();
    }
});