<?php
// includes/map-functions.php

class BB_Location_Map {
    
    public function __construct() {
        // Add AJAX handler for getting map data
        add_action('wp_ajax_bb_location_map_data', array($this, 'ajax_map_data'));
        add_action('wp_ajax_nopriv_bb_location_map_data', array($this, 'ajax_map_data'));
    }
    
    public function ajax_map_data() {
        // Check nonce
        check_ajax_referer('bb_location_map_nonce', 'nonce');
        
        $user_ids = isset($_POST['user_ids']) ? array_map('intval', $_POST['user_ids']) : array();
        
        if (empty($user_ids)) {
            wp_send_json_error(array('message' => __('No users specified', 'bb-location-finder')));
        }
        
        // Get map data for users
        $map_data = $this->get_user_map_data($user_ids);
        
        wp_send_json_success($map_data);
    }
    
    public function get_user_map_data($user_ids) {
        $map_data = array(
            'markers' => array()
        );
        
        foreach ($user_ids as $user_id) {
            $lat = get_user_meta($user_id, 'bb_location_lat', true);
            $lng = get_user_meta($user_id, 'bb_location_lng', true);
            
            if (!empty($lat) && !empty($lng)) {
                $city = get_user_meta($user_id, 'bb_location_city', true);
                $state = get_user_meta($user_id, 'bb_location_state', true);
                $country = get_user_meta($user_id, 'bb_location_country', true);
                
                $location_parts = array_filter(array($city, $state, $country));
                $location = implode(', ', $location_parts);
                
                $map_data['markers'][] = array(
                    'id' => $user_id,
                    'name' => bp_core_get_user_displayname($user_id),
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'location' => $location,
                    'profile_url' => bp_core_get_user_domain($user_id),
                    'avatar' => bp_core_fetch_avatar(array(
                        'item_id' => $user_id,
                        'type' => 'thumb',
                        'html' => false
                    ))
                );
            }
        }
        
        return $map_data;
    }
}