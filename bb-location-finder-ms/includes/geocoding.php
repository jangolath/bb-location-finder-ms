<?php
// includes/geocoding.php

class BB_Location_Geocoding {
    
    private $api_key;
    
    public function __construct() {
        // Get API key from options - check network options first on multisite
        if (is_multisite()) {
            $this->api_key = get_site_option('bb_location_google_api_key', '');
            
            // Debug for admins
            if (current_user_can('administrator') && function_exists('bb_location_debug_log')) {
                bb_location_debug_log('Network API Key: ' . substr($this->api_key, 0, 5) . '...');
            }
        }
        
        // If not set at network level or not multisite, get from site options
        if (empty($this->api_key)) {
            $this->api_key = get_option('bb_location_google_api_key', '');
            
            // Debug for admins
            if (current_user_can('administrator') && function_exists('bb_location_debug_log')) {
                bb_location_debug_log('Site API Key: ' . substr($this->api_key, 0, 5) . '...');
            }
        }
    }
    
    public function geocode_user_location($user_id) {
        // Get location data
        $city = get_user_meta($user_id, 'bb_location_city', true);
        $state = get_user_meta($user_id, 'bb_location_state', true);
        $country = get_user_meta($user_id, 'bb_location_country', true);
        
        // Skip if no location data
        if (empty($city) && empty($state) && empty($country)) {
            return false;
        }
        
        // Build address string
        $address_parts = array_filter(array($city, $state, $country));
        $address = implode(', ', $address_parts);
        
        // Call geocoding API
        $coordinates = $this->geocode_address($address);
        
        if ($coordinates) {
            update_user_meta($user_id, 'bb_location_lat', $coordinates['lat']);
            update_user_meta($user_id, 'bb_location_lng', $coordinates['lng']);
            return true;
        }
        
        return false;
    }
    
    public function geocode_address($address) {
        // Skip if no API key
        if (empty($this->api_key)) {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('Geocoding failed: No API key');
            }
            return false;
        }
        
        // URL encode the address
        $address = urlencode($address);
        
        // Build request URL
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$this->api_key}";
        
        // Make request
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('Geocoding error: ' . $response->get_error_message());
            }
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        // Check if the request was successful
        if (!isset($data['status']) || $data['status'] !== 'OK') {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('Geocoding API error: ' . (isset($data['status']) ? $data['status'] : 'Unknown'));
            }
            return false;
        }
        
        // Get the coordinates
        $lat = $data['results'][0]['geometry']['location']['lat'];
        $lng = $data['results'][0]['geometry']['location']['lng'];
        
        return array('lat' => $lat, 'lng' => $lng);
    }
}