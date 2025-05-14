<?php
// includes/shortcodes.php

class BB_Location_Shortcodes {
    
    public function __construct() {
        // Keep only the necessary AJAX handlers
        add_action('wp_ajax_bb_location_update', array($this, 'ajax_update_location'));
        add_action('wp_ajax_nopriv_bb_location_update', array($this, 'ajax_update_location_unauthorized'));
    }
    
    /**
     * Location setter shortcode
     */
    public function location_setter_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'button_text' => __('Update Location', 'bb-location-finder'),
            'redirect' => '',
        ), $atts);
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('bb-location-finder-styles');
        wp_enqueue_script('google-maps');
        wp_enqueue_script('bb-location-finder-js');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="bb-location-error">' . __('You must be logged in to set your location.', 'bb-location-finder') . '</div>';
        }
        
        // Get user data
        $user_id = get_current_user_id();
        $city = get_user_meta($user_id, 'bb_location_city', true);
        $state = get_user_meta($user_id, 'bb_location_state', true);
        $country = get_user_meta($user_id, 'bb_location_country', true);
        $searchable = get_user_meta($user_id, 'bb_location_searchable', true);
        
        if ($searchable === '') {
            $searchable = 'yes'; // Default to yes
        }
        
        // Create output
        $output = '<div class="bb-location-setter-form">';
        $output .= '<form id="bb-location-setter" method="post">';
        $output .= wp_nonce_field('bb_location_setter_nonce', 'bb_location_nonce', true, false);
        
        $output .= '<div class="form-field">';
        $output .= '<label for="bb_location_city">' . __('City', 'bb-location-finder') . '</label>';
        $output .= '<input type="text" id="bb_location_city" name="bb_location_city" value="' . esc_attr($city) . '" />';
        $output .= '</div>';
        
        $output .= '<div class="form-field">';
        $output .= '<label for="bb_location_state">' . __('State/Province', 'bb-location-finder') . '</label>';
        $output .= '<input type="text" id="bb_location_state" name="bb_location_state" value="' . esc_attr($state) . '" />';
        $output .= '</div>';
        
        $output .= '<div class="form-field">';
        $output .= '<label for="bb_location_country">' . __('Country', 'bb-location-finder') . '</label>';
        $output .= '<input type="text" id="bb_location_country" name="bb_location_country" value="' . esc_attr($country) . '" />';
        $output .= '</div>';
        
        // Hidden fields for coordinates
        $output .= '<input type="hidden" name="bb_location_lat" id="bb_location_lat" value="' . esc_attr(get_user_meta($user_id, 'bb_location_lat', true)) . '" />';
        $output .= '<input type="hidden" name="bb_location_lng" id="bb_location_lng" value="' . esc_attr(get_user_meta($user_id, 'bb_location_lng', true)) . '" />';
        
        $output .= '<div class="form-field privacy-field">';
        $output .= '<label>';
        $output .= '<input type="checkbox" name="bb_location_searchable" value="yes" ' . checked($searchable, 'yes', false) . ' />';
        $output .= __('Allow others to find me in location searches', 'bb-location-finder');
        $output .= '</label>';
        $output .= '</div>';
        
        $output .= '<div class="form-field">';
        $output .= '<button type="submit" class="button">' . esc_html($atts['button_text']) . '</button>';
        $output .= '</div>';
        
        if ($atts['redirect']) {
            $output .= '<input type="hidden" name="redirect" value="' . esc_url($atts['redirect']) . '" />';
        }
        
        $output .= '</form>';
        $output .= '<div id="bb-location-message" style="display: none;"></div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Location search shortcode
     */
    public function location_search_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'radius_options' => '5,10,25,50,100',
            'unit' => 'km', // km or mi
            'show_map' => 'yes',
            'map_height' => '400px',
            'results_per_page' => '10',
            'show_profile_type_filter' => 'yes', // New parameter
        ), $atts);
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('bb-location-finder-styles');
        
        // Only enqueue map scripts if showing map
        if ($atts['show_map'] === 'yes') {
            wp_enqueue_script('google-maps');
        }
        
        wp_enqueue_script('bb-location-finder-js');
        
        // Prepare radius options
        $radius_values = explode(',', $atts['radius_options']);
        $radius_options = '';
        foreach ($radius_values as $value) {
            $value = trim($value);
            $radius_options .= '<option value="' . esc_attr($value) . '">' . esc_html($value) . '</option>';
        }
        
        // Unit display
        $unit_display = ($atts['unit'] == 'mi') ? __('miles', 'bb-location-finder') : __('kilometers', 'bb-location-finder');
        
        // Create output
        $output = '<div class="bb-location-search-container">';
        $output .= '<form id="bb-location-search-form">';
        $output .= wp_nonce_field('bb_location_search_nonce', 'search_nonce', true, false);
        
        $output .= '<div class="search-fields">';
        $output .= '<div class="form-field">';
        $output .= '<label for="bb_search_location">' . __('Location', 'bb-location-finder') . '</label>';
        $output .= '<input type="text" id="bb_search_location" name="location" placeholder="' . esc_attr__('Enter city, state, or country', 'bb-location-finder') . '" />';
        $output .= '</div>';
        
        $output .= '<div class="form-field">';
        $output .= '<label for="bb_search_radius">' . __('Radius', 'bb-location-finder') . '<span class="unit" style="font-weight: normal"> (' . esc_html($unit_display) . ')</span></label>';
        $output .= '<select id="bb_search_radius" name="radius">';
        $output .= $radius_options;
        $output .= '</select>';
        $output .= '</div>';
        
        $output .= '<div class="form-field">';
        $output .= '<button type="submit" class="button">' . __('Search', 'bb-location-finder') . '</button>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Add filter container (only visible after results are loaded)
        $output .= '<div class="filter-container" style="display:none; margin-top:15px;">';
        
        // Add name search filter
        $output .= '<div class="form-field">';
        $output .= '<label for="bb_name_search">' . __('Filter by Name', 'bb-location-finder') . '</label>';
        $output .= '<input type="text" id="bb_name_search" name="name_search" placeholder="' . esc_attr__('Enter member name', 'bb-location-finder') . '" />';
        $output .= '</div>';
        
        // Add profile type filter (only if enabled)
        if ($atts['show_profile_type_filter'] === 'yes') {
            // Get profile types dynamically if BuddyBoss Profile Types are active
            $profile_types = array();
            
            if (function_exists('bp_get_member_types')) {
                $member_types = bp_get_member_types(array(), 'objects');
                
                if (!empty($member_types)) {
                    foreach ($member_types as $type => $object) {
                        $profile_types[$type] = $object->labels['singular_name'];
                    }
                }
            }
            
            if (!empty($profile_types)) {
                $output .= '<div class="form-field profile-type-filter">';
                $output .= '<label for="bb_profile_type_filter">' . __('Filter by Profile Type', 'bb-location-finder') . '</label>';
                $output .= '<select id="bb_profile_type_filter" name="profile_type">';
                $output .= '<option value="">' . __('All Profile Types', 'bb-location-finder') . '</option>';
                
                foreach ($profile_types as $type => $label) {
                    $output .= '<option value="' . esc_attr($type) . '">' . esc_html($label) . '</option>';
                }
                
                $output .= '</select>';
                $output .= '</div>';
            }
        }
        
        $output .= '</div>'; // End filter container
        
        $output .= '<input type="hidden" name="unit" value="' . esc_attr($atts['unit']) . '" />';
        $output .= '<input type="hidden" name="show_map" value="' . esc_attr($atts['show_map']) . '" />';
        $output .= '<input type="hidden" name="results_per_page" value="' . esc_attr($atts['results_per_page']) . '" />';
        $output .= '<input type="hidden" name="current_page" value="1" />';
        $output .= '</form>';
        
        $output .= '<div id="bb-location-results" class="location-results">';
        $output .= '<div class="result-count"></div>';
        
        $output .= '<div class="result-container">';
        
        // Only add map if show_map is yes
        if ($atts['show_map'] === 'yes') {
            $output .= '<div id="bb-location-map" style="height: ' . esc_attr($atts['map_height']) . ';"></div>';
        }
        
        $output .= '<div id="bb-location-users" class="user-results"></div>';
        $output .= '</div>';
        
        // Add pagination container
        $output .= '<div id="bb-location-pagination" class="location-pagination" style="display:none; margin-top:15px; text-align:center;"></div>';
        
        $output .= '</div>';
        $output .= '</div>';
        
        // Add inline script to handle profile type filtering
        ob_start();
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle profile type filter changes
            $(document).on('change', '#bb_profile_type_filter', function() {
                var selectedType = $(this).val();
                
                if (window.allUsers && window.filteredByName) {
                    // First filter by name
                    if ($('#bb_name_search').val().length === 0) {
                        window.filteredByName = window.allUsers.slice();
                    } else {
                        var searchValue = $('#bb_name_search').val().toLowerCase();
                        window.filteredByName = window.allUsers.filter(function(user) {
                            return user.name.toLowerCase().indexOf(searchValue) !== -1;
                        });
                    }
                    
                    // Then filter by profile type
                    if (selectedType === '') {
                        window.filteredUsers = window.filteredByName.slice();
                    } else {
                        window.filteredUsers = window.filteredByName.filter(function(user) {
                            return user.profile_type === selectedType;
                        });
                    }
                    
                    // Reset to first page
                    window.currentPage = 1;
                    $('input[name="current_page"]').val(1);
                    
                    // Display filtered results
                    displayUserResults(window.filteredUsers);
                }
            });
            
            // Override name search to work with profile type filter
            $(document).on('input', '#bb_name_search', function() {
                var searchValue = $(this).val().toLowerCase();
                
                if (window.allUsers) {
                    // First filter by name
                    if (searchValue.length === 0) {
                        window.filteredByName = window.allUsers.slice();
                    } else {
                        window.filteredByName = window.allUsers.filter(function(user) {
                            return user.name.toLowerCase().indexOf(searchValue) !== -1;
                        });
                    }
                    
                    // Then apply profile type filter if selected
                    var selectedType = $('#bb_profile_type_filter').val();
                    if (selectedType === '') {
                        window.filteredUsers = window.filteredByName.slice();
                    } else {
                        window.filteredUsers = window.filteredByName.filter(function(user) {
                            return user.profile_type === selectedType;
                        });
                    }
                    
                    // Reset to first page
                    window.currentPage = 1;
                    $('input[name="current_page"]').val(1);
                    
                    // Display filtered results
                    displayUserResults(window.filteredUsers);
                }
            });
        });
        </script>
        
        <style>
        /* Filter Container Styles */
        .filter-container {
            margin-top: 15px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 4px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filter-container .form-field {
            flex: 1;
            min-width: 200px;
        }
        
        @media (max-width: 767px) {
            .filter-container {
                flex-direction: column;
            }
        }
        </style>
        <?php
        $output .= ob_get_clean();
        
        return $output;
    }
    
    /**
     * AJAX handler for unauthorized users
     */
    public function ajax_update_location_unauthorized() {
        wp_send_json_error(array('message' => __('You must be logged in to update your location.', 'bb-location-finder')));
    }
    
    /**
     * AJAX handler for location updates
     */
    public function ajax_update_location() {
        // Check nonce
        check_ajax_referer('bb_location_setter_nonce', 'nonce');
        
        // Get form data
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
        $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
        $lat = isset($_POST['lat']) ? sanitize_text_field($_POST['lat']) : '';
        $lng = isset($_POST['lng']) ? sanitize_text_field($_POST['lng']) : '';
        $searchable = isset($_POST['searchable']) && $_POST['searchable'] === 'yes' ? 'yes' : 'no';
        $redirect = isset($_POST['redirect']) ? esc_url_raw($_POST['redirect']) : '';
        
        // Validate
        if (empty($city) && empty($state) && empty($country)) {
            wp_send_json_error(array('message' => __('Please enter at least one location field.', 'bb-location-finder')));
        }
        
        // Get current user
        $user_id = get_current_user_id();
        
        // Update user meta
        update_user_meta($user_id, 'bb_location_city', $city);
        update_user_meta($user_id, 'bb_location_state', $state);
        update_user_meta($user_id, 'bb_location_country', $country);
        update_user_meta($user_id, 'bb_location_searchable', $searchable);
        
        // Update coordinates if provided
        if (!empty($lat) && !empty($lng)) {
            update_user_meta($user_id, 'bb_location_lat', $lat);
            update_user_meta($user_id, 'bb_location_lng', $lng);
        } else {
            // Geocode the address
            $geocoder = new BB_Location_Geocoding();
            $geocoder->geocode_user_location($user_id);
        }
        
        // Send success response
        wp_send_json_success(array(
            'message' => __('Your location has been updated successfully.', 'bb-location-finder'),
            'redirect' => $redirect
        ));
    }
}