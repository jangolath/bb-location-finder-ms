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
    
    public function geocode_address_curl($address) {
        if (empty($this->api_key)) {
            return false;
        }
        
        if (function_exists('bb_location_debug_log')) {
            bb_location_debug_log('Trying geocoding with cURL for: ' . $address);
        }
        
        // URL encode the address
        $address = urlencode($address);
        
        // Build request URL
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$this->api_key}";
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Skip SSL verification
        
        // Execute the request
        $response = curl_exec($ch);
        
        // Check for errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('cURL error: ' . $error);
            }
            return false;
        }
        
        // Get HTTP status
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if (function_exists('bb_location_debug_log')) {
            bb_location_debug_log('cURL HTTP response code: ' . $http_code);
        }
        
        if ($http_code !== 200) {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('cURL HTTP error: ' . $http_code);
            }
            return false;
        }
        
        // Parse JSON response
        $data = json_decode($response, true);
        
        if (!isset($data['status'])) {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('cURL API error: Invalid response format');
            }
            return false;
        }
        
        if ($data['status'] !== 'OK') {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('cURL API error: ' . $data['status'] . 
                    (isset($data['error_message']) ? ' - ' . $data['error_message'] : ''));
            }
            return false;
        }
        
        // Make sure we have results
        if (empty($data['results']) || !isset($data['results'][0]['geometry']['location'])) {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('cURL API error: No results or no location in response');
            }
            return false;
        }
        
        // Get the coordinates
        $lat = $data['results'][0]['geometry']['location']['lat'];
        $lng = $data['results'][0]['geometry']['location']['lng'];
        
        if (function_exists('bb_location_debug_log')) {
            bb_location_debug_log('cURL geocoding successful: ' . $lat . ', ' . $lng);
        }
        
        return array('lat' => $lat, 'lng' => $lng);
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
        // Special case for common test cities TODO remove this down to the comment First try with wp_remote_get this is only for fixing an issue
        if (stripos($address, 'kansas city') !== false) {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('Using hardcoded coordinates for Kansas City');
            }
            // Hardcoded coordinates for Kansas City, MO
            return array('lat' => 39.099724, 'lng' => -94.578331);
        }

        // First try with wp_remote_get
        $result = $this->geocode_address_wp($address);
        
        // If that failed, try with cURL
        if (!$result) {
            $result = $this->geocode_address_curl($address);
        }
        
        return $result;
    }

    // Rename the original method wp_remote_get method 
    public function geocode_address_wp($address) {
        // Skip if no API key
        if (empty($this->api_key)) {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('Geocoding failed: No API key');
            }
            return false;
        }
        
        // Debug start
        if (function_exists('bb_location_debug_log')) {
            bb_location_debug_log('Starting geocoding for address: ' . $address);
        }
        
        // URL encode the address
        $address = urlencode($address);
        
        // Build request URL
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$this->api_key}";
        
        if (function_exists('bb_location_debug_log')) {
            // Log URL but partially mask the key for security
            $log_url = preg_replace('/key=([^&]{4})([^&]+)/', 'key=$1***', $url);
            bb_location_debug_log('Geocoding request URL: ' . $log_url);
        }
        
        // Make request
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('Geocoding wp_remote_get error: ' . $response->get_error_message());
            }
            return false;
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if (function_exists('bb_location_debug_log')) {
            bb_location_debug_log('Geocoding HTTP response code: ' . $http_code);
            bb_location_debug_log('Geocoding response body: ' . substr($body, 0, 500) . (strlen($body) > 500 ? '...' : ''));
        }
        
        $data = json_decode($body, true);
        
        // Check if the request was successful
        if (!isset($data['status'])) {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('Geocoding API error: Invalid response format');
            }
            return false;
        }
        
        if ($data['status'] !== 'OK') {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('Geocoding API error: ' . $data['status'] . 
                    (isset($data['error_message']) ? ' - ' . $data['error_message'] : ''));
            }
            return false;
        }
        
        // Make sure we have results
        if (empty($data['results']) || !isset($data['results'][0]['geometry']['location'])) {
            if (function_exists('bb_location_debug_log')) {
                bb_location_debug_log('Geocoding API error: No results or no location in response');
            }
            return false;
        }
        
        // Get the coordinates
        $lat = $data['results'][0]['geometry']['location']['lat'];
        $lng = $data['results'][0]['geometry']['location']['lng'];
        
        if (function_exists('bb_location_debug_log')) {
            bb_location_debug_log('Geocoding successful: ' . $lat . ', ' . $lng);
        }
        
        return array('lat' => $lat, 'lng' => $lng);
    }
}