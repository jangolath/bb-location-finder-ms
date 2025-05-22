<?php
// includes/simple-nav-integration.php

/**
 * Simple navigation integration - just adds a link to the location search page
 */
class BB_Location_Simple_Nav {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add navigation link to BuddyBoss members directory
        add_action('bp_members_directory_tabs', array($this, 'add_location_link'));
        
        // Add CSS for styling only
        add_action('wp_head', array($this, 'add_nav_css'));
        
        // Debug logging
        add_action('wp_footer', array($this, 'debug_nav_status'));
    }
    
    /**
     * Add location search link to members directory
     */
    public function add_location_link() {
        // Debug logging
        error_log('BB Location Finder - add_location_link() called');
        
        // Only show if enabled and page is configured
        if (!$this->should_show_nav()) {
            error_log('BB Location Finder - Navigation should not show');
            return;
        }
        
        $page_id = get_option('bb_location_search_page_id');
        $nav_text = get_option('bb_location_nav_text', __('Location Search', 'bb-location-finder'));
        
        error_log('BB Location Finder - Page ID: ' . $page_id . ', Nav Text: ' . $nav_text);
        
        if ($page_id && get_post($page_id)) {
            $page_url = get_permalink($page_id);
            error_log('BB Location Finder - Adding navigation link to: ' . $page_url);
            ?>
            <li id="members-location-search">
                <a href="<?php echo esc_url($page_url); ?>" target="_self">
                    📍 <?php echo esc_html($nav_text); ?>
                </a>
            </li>
            <?php
        } else {
            error_log('BB Location Finder - No valid page found for navigation');
        }
    }
    
    /**
     * Check if navigation should be shown
     */
    private function should_show_nav() {
        // Check if we're on the members directory
        $is_members_directory = bp_is_members_component() && !bp_is_user();
        
        // Check if enabled in settings
        $show_in_nav = get_option('bb_location_show_in_members_nav', 'yes');
        
        error_log('BB Location Finder - Is members directory: ' . ($is_members_directory ? 'yes' : 'no') . ', Show in nav: ' . $show_in_nav);
        
        return $is_members_directory && ($show_in_nav === 'yes');
    }
    
    /**
     * Add CSS for navigation styling
     */
    public function add_nav_css() {
        // Only on members directory
        if (!bp_is_members_component() || bp_is_user()) {
            return;
        }
        
        ?>
        <style>
            /* Force location search link to be visible */
            .bp-navs ul li#members-location-search {
                display: inline-block !important;
                margin: 0;
                float: left;
                list-style: none;
            }
            
            .bp-navs ul li#members-location-search a {
                display: block !important;
                padding: 0 15px;
                color: #939597;
                text-decoration: none;
                border: 0;
                transition: color 0.2s ease;
                line-height: inherit;
                font-size: inherit;
            }
            
            .bp-navs ul li#members-location-search a:hover {
                color: var(--bb-primary-color, #007CFF);
                text-decoration: none;
            }
            
            /* BuddyBoss specific overrides */
            .buddypress-wrap .bp-navs li#members-location-search {
                display: inline-block !important;
            }
            
            .buddypress-wrap .bp-navs li#members-location-search a {
                background: none !important;
                border-radius: 0;
                font-weight: 400;
            }
            
            .buddypress-wrap .bp-navs li#members-location-search a:hover {
                background: none !important;
                color: var(--bb-primary-color, #007CFF);
            }
            
            /* Ensure it appears alongside other navigation items */
            #buddypress div#subnav.item-list-tabs#subnav li#members-location-search {
                display: inline-block !important;
                margin: 0;
                float: left;
            }
        </style>
        <?php
    }
    
    /**
     * Debug navigation status
     */
    public function debug_nav_status() {
        // Only on members directory and only for admins
        if (!bp_is_members_component() || bp_is_user() || !current_user_can('administrator')) {
            return;
        }
        
        $page_id = get_option('bb_location_search_page_id');
        $show_in_nav = get_option('bb_location_show_in_members_nav', 'yes');
        $nav_text = get_option('bb_location_nav_text', __('Location Search', 'bb-location-finder'));
        
        ?>
        <script>
        console.log('BB Location Finder Debug:', {
            'Page ID': '<?php echo $page_id; ?>',
            'Show in Nav': '<?php echo $show_in_nav; ?>',
            'Nav Text': '<?php echo esc_js($nav_text); ?>',
            'Page Exists': <?php echo ($page_id && get_post($page_id)) ? 'true' : 'false'; ?>,
            'Should Show': <?php echo $this->should_show_nav() ? 'true' : 'false'; ?>,
            'Current URL': window.location.href,
            'Navigation Element': document.getElementById('members-location-search') ? 'Found' : 'Not Found'
        });
        </script>
        <?php
    }
}

// Initialize the simple navigation
add_action('bp_init', function() {
    if (class_exists('BB_Location_Finder') && function_exists('bp_is_active')) {
        new BB_Location_Simple_Nav();
    }
}, 15);