<?php
// includes/profile-fields.php

class BB_Location_Profile_Fields {
    
    public function __construct() {
        // Add profile fields to BuddyBoss
        // Commented out to prevent fields showing on profile page
        //add_action('bp_after_profile_field_content', array($this, 'add_location_fields'));
        add_action('xprofile_updated_profile', array($this, 'save_location_data'), 10, 1);
        
        // Add privacy settings
        add_action('bp_custom_profile_edit_fields', array($this, 'add_location_privacy_setting'));
    }
    
    public function add_location_fields() {
        // Get current user's location data
        $user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
        $city = get_user_meta($user_id, 'bb_location_city', true);
        $state = get_user_meta($user_id, 'bb_location_state', true);
        $country = get_user_meta($user_id, 'bb_location_country', true);
        $lat = get_user_meta($user_id, 'bb_location_lat', true);
        $lng = get_user_meta($user_id, 'bb_location_lng', true);
        
        // Display the fields
        ?>
        <div class="bb-location-fields">
            <h3><?php _e('Your Location', 'bb-location-finder'); ?></h3>
            
            <div class="edit-field">
                <label for="bb_location_city"><?php _e('City', 'bb-location-finder'); ?></label>
                <input type="text" name="bb_location_city" id="bb_location_city" value="<?php echo esc_attr($city); ?>" />
            </div>
            
            <div class="edit-field">
                <label for="bb_location_state"><?php _e('State/Province', 'bb-location-finder'); ?></label>
                <input type="text" name="bb_location_state" id="bb_location_state" value="<?php echo esc_attr($state); ?>" />
            </div>
            
            <div class="edit-field">
                <label for="bb_location_country"><?php _e('Country', 'bb-location-finder'); ?></label>
                <input type="text" name="bb_location_country" id="bb_location_country" value="<?php echo esc_attr($country); ?>" />
            </div>
            
            <!-- Hidden fields for coordinates -->
            <input type="hidden" name="bb_location_lat" id="bb_location_lat" value="<?php echo esc_attr($lat); ?>" />
            <input type="hidden" name="bb_location_lng" id="bb_location_lng" value="<?php echo esc_attr($lng); ?>" />
        </div>
        <?php
    }
    
    public function add_location_privacy_setting() {
        $user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
        $location_searchable = get_user_meta($user_id, 'bb_location_searchable', true);
        
        // Default to true if not set
        if ($location_searchable === '') {
            $location_searchable = 'yes';
        }
        
        ?>
        <div class="bb-location-privacy">
            <h3><?php _e('Location Privacy', 'bb-location-finder'); ?></h3>
            
            <div class="edit-field">
                <label>
                    <input type="checkbox" name="bb_location_searchable" value="yes" <?php checked($location_searchable, 'yes'); ?> />
                    <?php _e('Allow others to find me in location searches', 'bb-location-finder'); ?>
                </label>
            </div>
        </div>
        <?php
    }
    
    public function save_location_data($user_id) {
        // Save location data
        if (isset($_POST['bb_location_city'])) {
            update_user_meta($user_id, 'bb_location_city', sanitize_text_field($_POST['bb_location_city']));
        }
        
        if (isset($_POST['bb_location_state'])) {
            update_user_meta($user_id, 'bb_location_state', sanitize_text_field($_POST['bb_location_state']));
        }
        
        if (isset($_POST['bb_location_country'])) {
            update_user_meta($user_id, 'bb_location_country', sanitize_text_field($_POST['bb_location_country']));
        }
        
        // Save coordinates
        if (isset($_POST['bb_location_lat']) && isset($_POST['bb_location_lng'])) {
            update_user_meta($user_id, 'bb_location_lat', sanitize_text_field($_POST['bb_location_lat']));
            update_user_meta($user_id, 'bb_location_lng', sanitize_text_field($_POST['bb_location_lng']));
        } else {
            // If coordinates weren't provided, geocode the address
            $geocoder = new BB_Location_Geocoding();
            $geocoder->geocode_user_location($user_id);
        }
        
        // Save privacy setting
        $searchable = isset($_POST['bb_location_searchable']) ? 'yes' : 'no';
        update_user_meta($user_id, 'bb_location_searchable', $searchable);
    }
}