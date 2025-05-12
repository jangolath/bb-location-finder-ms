<?php
// includes/shortcodes.php

class BB_Location_Shortcodes {
    
    public function __construct() {
        // Register shortcodes
        add_shortcode('bb_location_setter', array($this, 'location_setter_shortcode'));
        add_shortcode('bb_location_search', array($this, 'location_search_shortcode'));
        
        // Add AJAX handlers
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
        
        // Start output buffering
        ob_start();
        ?>
        <div class="bb-location-setter-form">
            <form id="bb-location-setter" method="post">
                <?php wp_nonce_field('bb_location_setter_nonce', 'bb_location_nonce'); ?>
                
                <div class="form-field">
                    <label for="bb_location_city"><?php _e('City', 'bb-location-finder'); ?></label>
                    <input type="text" id="bb_location_city" name="bb_location_city" value="<?php echo esc_attr($city); ?>" />
                </div>
                
                <div class="form-field">
                    <label for="bb_location_state"><?php _e('State/Province', 'bb-location-finder'); ?></label>
                    <input type="text" id="bb_location_state" name="bb_location_state" value="<?php echo esc_attr($state); ?>" />
                </div>
                
                <div class="form-field">
                    <label for="bb_location_country"><?php _e('Country', 'bb-location-finder'); ?></label>
                    <input type="text" id="bb_location_country" name="bb_location_country" value="<?php echo esc_attr($country); ?>" />
                </div>
                
                <!-- Hidden fields for coordinates -->
                <input type="hidden" name="bb_location_lat" id="bb_location_lat" value="<?php echo esc_attr(get_user_meta($user_id, 'bb_location_lat', true)); ?>" />
                <input type="hidden" name="bb_location_lng" id="bb_location_lng" value="<?php echo esc_attr(get_user_meta($user_id, 'bb_location_lng', true)); ?>" />
                
                <div class="form-field privacy-field">
                    <label>
                        <input type="checkbox" name="bb_location_searchable" value="yes" <?php checked($searchable, 'yes'); ?> />
                        <?php _e('Allow others to find me in location searches', 'bb-location-finder'); ?>
                    </label>
                </div>
                
                <div class="form-field">
                    <button type="submit" class="button"><?php echo esc_html($atts['button_text']); ?></button>
                </div>
                
                <?php if ($atts['redirect']): ?>
                    <input type="hidden" name="redirect" value="<?php echo esc_url($atts['redirect']); ?>" />
                <?php endif; ?>
            </form>
            
            <div id="bb-location-message" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
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
        ), $atts);
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('bb-location-finder-styles');
        wp_enqueue_script('google-maps');
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
        
        // Start output buffering
        ob_start();
        ?>
        <div class="bb-location-search-container">
            <form id="bb-location-search-form">
                <?php wp_nonce_field('bb_location_search_nonce', 'search_nonce'); ?>
                
                <div class="search-fields">
                    <div class="form-field">
                        <label for="bb_search_location"><?php _e('Location', 'bb-location-finder'); ?></label>
                        <input type="text" id="bb_search_location" name="location" placeholder="<?php esc_attr_e('Enter city, state, or country', 'bb-location-finder'); ?>" />
                    </div>
                    
                    <div class="form-field">
                        <label for="bb_search_radius"><?php _e('Radius', 'bb-location-finder'); ?></label>
                        <select id="bb_search_radius" name="radius">
                            <?php echo $radius_options; ?>
                        </select>
                        <span class="unit"><?php echo esc_html($unit_display); ?></span>
                    </div>
                    
                    <div class="form-field">
                        <button type="submit" class="button"><?php _e('Search', 'bb-location-finder'); ?></button>
                    </div>
                </div>
                
                <input type="hidden" name="unit" value="<?php echo esc_attr($atts['unit']); ?>" />
            </form>
            
            <div id="bb-location-results" class="location-results">
                <div class="result-count"></div>
                
                <div class="result-container">
                    <?php if ($atts['show_map'] == 'yes'): ?>
                    <div id="bb-location-map" style="height: <?php echo esc_attr($atts['map_height']); ?>;"></div>
                    <?php endif; ?>
                    
                    <div id="bb-location-users" class="user-results"></div>
                </div>
            </div>
        </div>
        
        <?php if ($atts['show_map'] == 'yes'): ?>
        <script>
        jQuery(document).ready(function($) {
            // Initialize the map
            if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                if (typeof window.bbLocationFinder !== 'undefined' && typeof window.bbLocationFinder.initMap === 'function') {
                    window.bbLocationFinder.initMap();
                }
            }
        });
        </script>
        <?php endif; ?>
        <?php
        return ob_get_clean();
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

// Initialize shortcodes
new BB_Location_Shortcodes();