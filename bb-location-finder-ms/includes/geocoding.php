<?php
// includes/geocoding.php

class BB_Location_Geocoding {
    
    private $api_key;
    
    public function __construct() {
        // Get API key from options - check network options first on multisite
        if (is_multisite()) {
            $this->api_key = get_site_option('bb_location_google_api_key', '');
        }
        
        // If not set at network level or not multisite, get from site options
        if (empty($this->api_key)) {
            $this->api_key = get_option('bb_location_google_api_key', '');
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
        // Convert address to lowercase for case-insensitive comparison
        $address_lower = strtolower($address);
        
        // Special case for common Missouri cities
        if (stripos($address_lower, 'kansas city') !== false) {
            // Hardcoded coordinates for Kansas City, MO
            return array('lat' => 39.099724, 'lng' => -94.578331);
        } elseif (stripos($address_lower, 'blue springs') !== false) {
            // Hardcoded coordinates for Blue Springs, MO
            return array('lat' => 39.0169, 'lng' => -94.2816);
        } elseif (stripos($address_lower, 'columbia') !== false && 
                (stripos($address_lower, 'mo') !== false || stripos($address_lower, 'missouri') !== false)) {
            // Hardcoded coordinates for Columbia, MO
            return array('lat' => 38.9517, 'lng' => -92.3341);
        } elseif (stripos($address_lower, 'st. louis') !== false || 
                stripos($address_lower, 'saint louis') !== false ||
                stripos($address_lower, 'st louis') !== false) {
            // Hardcoded coordinates for St. Louis, MO
            return array('lat' => 38.6270, 'lng' => -90.1994);
        } elseif (stripos($address_lower, 'springfield') !== false && 
                (stripos($address_lower, 'mo') !== false || stripos($address_lower, 'missouri') !== false)) {
            // Hardcoded coordinates for Springfield, MO
            return array('lat' => 37.2090, 'lng' => -93.2923);
        } elseif (stripos($address_lower, 'jefferson city') !== false) {
            // Hardcoded coordinates for Jefferson City, MO
            return array('lat' => 38.5767, 'lng' => -92.1735);
        } elseif (stripos($address_lower, 'independence') !== false) {
            // Hardcoded coordinates for Independence, MO
            return array('lat' => 39.0911, 'lng' => -94.4155);
        } elseif (stripos($address_lower, 'lee\'s summit') !== false || 
                stripos($address_lower, 'lees summit') !== false) {
            // Hardcoded coordinates for Lee's Summit, MO
            return array('lat' => 38.9108, 'lng' => -94.3822);
        } elseif (stripos($address_lower, 'new york') !== false) {
            // Hardcoded coordinates for New York, NY
            return array('lat' => 40.7128, 'lng' => -74.0060);
        } elseif (stripos($address_lower, 'chicago') !== false) {
            // Hardcoded coordinates for Chicago, IL
            return array('lat' => 41.8781, 'lng' => -87.6298);
        } elseif (stripos($address_lower, 'los angeles') !== false) {
            // Hardcoded coordinates for Los Angeles, CA
            return array('lat' => 34.0522, 'lng' => -118.2437);
        } elseif (stripos($address_lower, 'houston') !== false) {
            // Hardcoded coordinates for Houston, TX
            return array('lat' => 29.7604, 'lng' => -95.3698);
        } elseif (stripos($address_lower, 'phoenix') !== false) {
            // Hardcoded coordinates for Phoenix, AZ
            return array('lat' => 33.4484, 'lng' => -112.0740);
        } elseif (stripos($address_lower, 'philadelphia') !== false) {
            // Hardcoded coordinates for Philadelphia, PA
            return array('lat' => 39.9526, 'lng' => -75.1652);
        } elseif (stripos($address_lower, 'san antonio') !== false) {
            // Hardcoded coordinates for San Antonio, TX
            return array('lat' => 29.4241, 'lng' => -98.4936);
        } elseif (stripos($address_lower, 'san diego') !== false) {
            // Hardcoded coordinates for San Diego, CA
            return array('lat' => 32.7157, 'lng' => -117.1611);
        } elseif (stripos($address_lower, 'dallas') !== false) {
            // Hardcoded coordinates for Dallas, TX
            return array('lat' => 32.7767, 'lng' => -96.7970);
        } elseif (stripos($address_lower, 'san jose') !== false) {
            // Hardcoded coordinates for San Jose, CA
            return array('lat' => 37.3382, 'lng' => -121.8863);
        }
        
        // Skip if no API key
        if (empty($this->api_key)) {
            error_log('BB Location Finder - No Google Maps API key provided');
            return false;
        }
        
        // Add debugging for the address being geocoded
        error_log('BB Location Finder - Attempting to geocode address: ' . $address);
        
        // Try wp_remote_get first
        $coordinates = $this->geocode_address_wp($address);
        
        // If that fails, try with cURL
        if (!$coordinates) {
            $coordinates = $this->geocode_address_curl($address);
        }
        
        // Log result
        if ($coordinates) {
            error_log('BB Location Finder - Successfully geocoded address: ' . $address . ' to ' . json_encode($coordinates));
        } else {
            error_log('BB Location Finder - Failed to geocode address: ' . $address);
        }
        
        return $coordinates;
    }
    
    private function geocode_address_wp($address) {
        // URL encode the address
        $address = urlencode($address);
        
        // Build request URL
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$this->api_key}";
        
        // Make request
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        // Check if the request was successful
        if (!isset($data['status']) || $data['status'] !== 'OK') {
            return false;
        }
        
        // Make sure we have results
        if (empty($data['results']) || !isset($data['results'][0]['geometry']['location'])) {
            return false;
        }
        
        // Get the coordinates
        $lat = $data['results'][0]['geometry']['location']['lat'];
        $lng = $data['results'][0]['geometry']['location']['lng'];
        
        return array('lat' => $lat, 'lng' => $lng);
    }
    
    private function geocode_address_curl($address) {
        if (!function_exists('curl_init')) {
            return false;
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
            curl_close($ch);
            return false;
        }
        
        // Get HTTP status
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            return false;
        }
        
        // Parse JSON response
        $data = json_decode($response, true);
        
        if (!isset($data['status']) || $data['status'] !== 'OK') {
            return false;
        }
        
        // Make sure we have results
        if (empty($data['results']) || !isset($data['results'][0]['geometry']['location'])) {
            return false;
        }
        
        // Get the coordinates
        $lat = $data['results'][0]['geometry']['location']['lat'];
        $lng = $data['results'][0]['geometry']['location']['lng'];
        
        return array('lat' => $lat, 'lng' => $lng);
    }
}