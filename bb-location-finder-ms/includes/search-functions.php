<?php
// includes/search-functions.php

class BB_Location_Search {
    
    public function __construct() {
        // Register AJAX actions
        add_action('wp_ajax_bb_location_search', array($this, 'ajax_location_search'));
        add_action('wp_ajax_nopriv_bb_location_search', array($this, 'ajax_location_search'));
    }
    
    public function find_users_by_distance($center_lat, $center_lng, $radius, $unit = 'km') {
        global $wpdb;
        
        // Convert radius to meters for calculation
        $radius_multiplier = ($unit == 'mi') ? 3959 : 6371; // Earth radius in miles or km
        
        // SQL query to calculate distance and filter users
        $sql = $wpdb->prepare(
            "SELECT u.ID, u.display_name, 
                   um_city.meta_value as city,
                   um_state.meta_value as state,
                   um_country.meta_value as country,
                   um_lat.meta_value as lat,
                   um_lng.meta_value as lng,
                   ( %d * acos( cos( radians(%f) ) * cos( radians( um_lat.meta_value ) ) * 
                     cos( radians( um_lng.meta_value ) - radians(%f) ) + 
                     sin( radians(%f) ) * sin( radians( um_lat.meta_value ) ) ) ) AS distance
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_searchable ON u.ID = um_searchable.user_id AND um_searchable.meta_key = 'bb_location_searchable' AND um_searchable.meta_value = 'yes'
            INNER JOIN {$wpdb->usermeta} um_lat ON u.ID = um_lat.user_id AND um_lat.meta_key = 'bb_location_lat'
            INNER JOIN {$wpdb->usermeta} um_lng ON u.ID = um_lng.user_id AND um_lng.meta_key = 'bb_location_lng'
            LEFT JOIN {$wpdb->usermeta} um_city ON u.ID = um_city.user_id AND um_city.meta_key = 'bb_location_city'
            LEFT JOIN {$wpdb->usermeta} um_state ON u.ID = um_state.user_id AND um_state.meta_key = 'bb_location_state'
            LEFT JOIN {$wpdb->usermeta} um_country ON u.ID = um_country.user_id AND um_country.meta_key = 'bb_location_country'
            WHERE um_lat.meta_value IS NOT NULL AND um_lng.meta_value IS NOT NULL
            HAVING distance < %f
            ORDER BY distance ASC",
            $radius_multiplier,
            $center_lat,
            $center_lng,
            $center_lat,
            $radius
        );
        
        $results = $wpdb->get_results($sql);
        
        return $results;
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
        $show_map = isset($_POST['show_map']) ? sanitize_text_field($_POST['show_map']) : 'yes';
        $map_height = isset($_POST['map_height']) ? sanitize_text_field($_POST['map_height']) : '400px';
        
        if (empty($location) || $radius <= 0) {
            wp_send_json_error(array('message' => __('Invalid search parameters', 'bb-location-finder')));
        }
        
        // Geocode the search location
        $geocoder = new BB_Location_Geocoding();
        $coordinates = $geocoder->geocode_address($location);
        
        if (!$coordinates) {
            wp_send_json_error(array('message' => __('Could not find the location', 'bb-location-finder')));
        }
        
        // Find users within the radius
        $users = $this->find_users_by_distance($coordinates['lat'], $coordinates['lng'], $radius, $unit);
        
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
            'unit' => $unit,
            'show_map' => $show_map,
            'map_height' => $map_height
        ));
    }
}