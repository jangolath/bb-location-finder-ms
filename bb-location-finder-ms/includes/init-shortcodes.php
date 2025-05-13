<?php
// includes/init-shortcodes.php

// Global variable for shortcodes class
global $bb_location_shortcodes;

// Initialize shortcodes on init action to ensure proper timing
function bb_location_finder_init_shortcodes() {
    global $bb_location_shortcodes;
    
    // If shortcodes aren't already registered, do it now
    if (!shortcode_exists('bb_location_setter')) {
        // Create shortcodes class instance if not already created
        if (!isset($bb_location_shortcodes) || !is_object($bb_location_shortcodes)) {
            $bb_location_shortcodes = new BB_Location_Shortcodes();
        }
        
        // Manually register shortcodes
        add_shortcode('bb_location_setter', array($bb_location_shortcodes, 'location_setter_shortcode'));
        add_shortcode('bb_location_search', array($bb_location_shortcodes, 'location_search_shortcode'));
    }
}

// Register on init (priority 1 to run before most other init actions)
add_action('init', 'bb_location_finder_init_shortcodes', 1);