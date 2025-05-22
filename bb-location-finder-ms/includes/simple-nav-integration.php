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
        add_action('bp_nouveau_get_members_directory_nav_items', array($this, 'add_location_nav_item'));
        
        // Add CSS for proper styling
        add_action('wp_head', array($this, 'add_nav_css'));
    }
    
    /**
     * Add location search link to members directory (Legacy BP)
     */
    public function add_location_link() {
        // Only show if enabled and page is configured
        if (!$this->should_show_nav()) {
            return;
        }
        
        $page_id = get_option('bb_location_search_page_id');
        $nav_text = get_option('bb_location_nav_text', __('Location Search', 'bb-location-finder'));
        
        if ($page_id && get_post($page_id)) {
            $page_url = get_permalink($page_id);
            ?>
            <li id="members-location-search">
                <a href="<?php echo esc_url($page_url); ?>">
                    <?php echo esc_html($nav_text); ?>
                </a>
            </li>
            <?php
        }
    }
    
    /**
     * Add location search link to BuddyBoss Nouveau template
     */
    public function add_location_nav_item($nav_items) {
        // Only show if enabled and page is configured
        if (!$this->should_show_nav()) {
            return $nav_items;
        }
        
        $page_id = get_option('bb_location_search_page_id');
        $nav_text = get_option('bb_location_nav_text', __('Location Search', 'bb-location-finder'));
        
        if ($page_id && get_post($page_id)) {
            $page_url = get_permalink($page_id);
            
            // Add our navigation item
            $nav_items['location-search'] = array(
                'component' => 'members',
                'slug' => 'location-search',
                'li_class' => array('location-search-link'),
                'link' => $page_url,
                'text' => $nav_text,
                'count' => false,
                'position' => 50
            );
        }
        
        return $nav_items;
    }
    
    /**
     * Check if navigation should be shown
     */
    private function should_show_nav() {
        // Only on members directory
        if (!bp_is_members_component() || bp_is_user()) {
            return false;
        }
        
        // Check if enabled
        $show_in_nav = get_option('bb_location_show_in_members_nav', 'yes');
        return ($show_in_nav === 'yes');
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
            /* Ensure location search link is visible and properly styled */
            .bp-navs ul li#members-location-search,
            .bp-navs ul li.location-search-link {
                display: inline-block !important;
            }
            
            .bp-navs ul li#members-location-search a,
            .bp-navs ul li.location-search-link a {
                display: block;
                padding: 0 5px;
                color: #939597;
                text-decoration: none;
                border: 0;
                transition: color 0.2s ease;
            }
            
            .bp-navs ul li#members-location-search a:hover,
            .bp-navs ul li.location-search-link a:hover {
                color: var(--bb-primary-color, #007CFF);
            }
            
            /* BuddyBoss Nouveau template specific styles */
            .buddypress-wrap .bp-navs li#members-location-search a,
            .buddypress-wrap .bp-navs li.location-search-link a {
                background: none;
                border-radius: 0;
                font-weight: 400;
            }
            
            .buddypress-wrap .bp-navs li#members-location-search a:hover,
            .buddypress-wrap .bp-navs li.location-search-link a:hover {
                background: none;
                color: var(--bb-primary-color, #007CFF);
            }
            
            /* Ensure proper alignment with other tabs */
            #buddypress div#subnav.item-list-tabs#subnav li#members-location-search,
            #buddypress .bp-navs li.location-search-link {
                margin: 0;
                float: left;
                list-style: none;
            }
            
            /* Icon support (optional) */
            .bp-navs ul li#members-location-search a:before,
            .bp-navs ul li.location-search-link a:before {
                content: "📍";
                margin-right: 5px;
                font-size: 14px;
            }
        </style>
        <?php
    }
}

// Initialize the simple navigation
add_action('bp_init', function() {
    if (class_exists('BB_Location_Finder') && function_exists('bp_is_active')) {
        new BB_Location_Simple_Nav();
    }
});