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
        // Try multiple hooks to ensure we catch BuddyBoss navigation
        add_action('bp_members_directory_tabs', array($this, 'add_location_link'), 10);
        add_action('bp_before_members_loop', array($this, 'add_location_link_fallback'), 5);
        
        // Also try the nouveau template hooks
        add_filter('bp_nouveau_get_members_directory_nav_items', array($this, 'add_location_nav_item'), 10, 1);
        
        // Add CSS and debug info
        add_action('wp_head', array($this, 'add_nav_css'));
        add_action('wp_footer', array($this, 'debug_info'));
        
        // Log when the class is constructed
        error_log('BB Location Finder - BB_Location_Simple_Nav constructed');
    }
    
    /**
     * Add location search link via bp_members_directory_tabs hook
     */
    public function add_location_link() {
        error_log('BB Location Finder - add_location_link() hook fired');
        
        if (!$this->should_show_nav()) {
            error_log('BB Location Finder - should_show_nav() returned false');
            return;
        }
        
        $this->output_nav_link();
    }
    
    /**
     * Fallback method to add navigation link
     */
    public function add_location_link_fallback() {
        error_log('BB Location Finder - add_location_link_fallback() hook fired');
        
        if (!$this->should_show_nav()) {
            return;
        }
        
        // This will add the link via JavaScript as a fallback
        $this->output_fallback_nav();
    }
    
    /**
     * Add location search link via nouveau template filter
     */
    public function add_location_nav_item($nav_items) {
        error_log('BB Location Finder - add_location_nav_item() filter fired with ' . count($nav_items) . ' existing items');
        
        if (!$this->should_show_nav()) {
            return $nav_items;
        }
        
        $page_id = get_option('bb_location_search_page_id');
        $nav_text = get_option('bb_location_nav_text', __('Location Search', 'bb-location-finder'));
        
        if ($page_id && get_post($page_id)) {
            $page_url = get_permalink($page_id);
            
            $nav_items['location-search'] = array(
                'component' => 'members',
                'slug' => 'location-search',
                'li_class' => array('location-search-link'),
                'link' => $page_url,
                'text' => '📍 ' . $nav_text,
                'count' => false,
                'position' => 50
            );
            
            error_log('BB Location Finder - Added nav item to nouveau template');
        }
        
        return $nav_items;
    }
    
    /**
     * Output the navigation link HTML
     */
    private function output_nav_link() {
        $page_id = get_option('bb_location_search_page_id');
        $nav_text = get_option('bb_location_nav_text', __('Location Search', 'bb-location-finder'));
        
        if ($page_id && get_post($page_id)) {
            $page_url = get_permalink($page_id);
            error_log('BB Location Finder - Outputting nav link HTML for page: ' . $page_url);
            ?>
            <li id="members-location-search" class="bb-location-nav-item">
                <a href="<?php echo esc_url($page_url); ?>">
                    📍 <?php echo esc_html($nav_text); ?>
                </a>
            </li>
            <?php
        }
    }
    
    /**
     * Output fallback navigation via JavaScript
     */
    private function output_fallback_nav() {
        $page_id = get_option('bb_location_search_page_id');
        $nav_text = get_option('bb_location_nav_text', __('Location Search', 'bb-location-finder'));
        
        if ($page_id && get_post($page_id)) {
            $page_url = get_permalink($page_id);
            ?>
            <script>
            jQuery(document).ready(function($) {
                console.log('BB Location Finder - Adding fallback navigation link');
                
                // Try to find the navigation container
                var $nav = $('.bp-navs ul, #subnav ul, .item-list-tabs ul').first();
                
                if ($nav.length) {
                    console.log('BB Location Finder - Found navigation container:', $nav[0]);
                    
                    // Check if our link already exists
                    if ($('#members-location-search').length === 0) {
                        var linkHtml = '<li id="members-location-search" class="bb-location-nav-item">' +
                                      '<a href="<?php echo esc_js($page_url); ?>">📍 <?php echo esc_js($nav_text); ?></a>' +
                                      '</li>';
                        
                        $nav.append(linkHtml);
                        console.log('BB Location Finder - Added fallback navigation link');
                    } else {
                        console.log('BB Location Finder - Navigation link already exists');
                    }
                } else {
                    console.log('BB Location Finder - Could not find navigation container');
                }
            });
            </script>
            <?php
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
        
        // Check if page is configured
        $page_id = get_option('bb_location_search_page_id');
        $page_exists = $page_id && get_post($page_id);
        
        $should_show = $is_members_directory && ($show_in_nav === 'yes') && $page_exists;
        
        error_log('BB Location Finder - should_show_nav(): members_dir=' . ($is_members_directory ? 'yes' : 'no') . 
                  ', enabled=' . $show_in_nav . ', page_exists=' . ($page_exists ? 'yes' : 'no') . 
                  ', result=' . ($should_show ? 'yes' : 'no'));
        
        return $should_show;
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
            .bp-navs ul li#members-location-search,
            .bp-navs ul li.bb-location-nav-item,
            #subnav li#members-location-search,
            .item-list-tabs li#members-location-search {
                display: inline-block !important;
                margin: 0;
                float: left;
                list-style: none;
            }
            
            .bp-navs ul li#members-location-search a,
            .bp-navs ul li.bb-location-nav-item a,
            #subnav li#members-location-search a,
            .item-list-tabs li#members-location-search a {
                display: block !important;
                padding: 0 15px;
                color: #939597;
                text-decoration: none;
                border: 0;
                transition: color 0.2s ease;
                line-height: inherit;
                font-size: inherit;
            }
            
            .bp-navs ul li#members-location-search a:hover,
            .bp-navs ul li.bb-location-nav-item a:hover,
            #subnav li#members-location-search a:hover,
            .item-list-tabs li#members-location-search a:hover {
                color: var(--bb-primary-color, #007CFF);
                text-decoration: none;
            }
            
            /* BuddyBoss specific overrides */
            .buddypress-wrap .bp-navs li#members-location-search,
            .buddypress-wrap .bp-navs li.bb-location-nav-item {
                display: inline-block !important;
            }
            
            .buddypress-wrap .bp-navs li#members-location-search a,
            .buddypress-wrap .bp-navs li.bb-location-nav-item a {
                background: none !important;
                border-radius: 0;
                font-weight: 400;
            }
            
            .buddypress-wrap .bp-navs li#members-location-search a:hover,
            .buddypress-wrap .bp-navs li.bb-location-nav-item a:hover {
                background: none !important;
                color: var(--bb-primary-color, #007CFF);
            }
        </style>
        <?php
    }
    
    /**
     * Debug information output
     */
    public function debug_info() {
        // Only on members directory
        if (!bp_is_members_component() || bp_is_user()) {
            return;
        }
        
        $page_id = get_option('bb_location_search_page_id');
        $show_in_nav = get_option('bb_location_show_in_members_nav', 'yes');
        $nav_text = get_option('bb_location_nav_text', __('Location Search', 'bb-location-finder'));
        $page_exists = $page_id && get_post($page_id);
        
        ?>
        <script>
        // Always output debug info for troubleshooting
        console.log('=== BB Location Finder Debug Info ===');
        console.log('Current URL:', window.location.href);
        console.log('Page Body Classes:', document.body.className);
        console.log('Configuration:', {
            'Page ID': '<?php echo $page_id; ?>',
            'Show in Nav': '<?php echo $show_in_nav; ?>',
            'Nav Text': '<?php echo esc_js($nav_text); ?>',
            'Page Exists': <?php echo $page_exists ? 'true' : 'false'; ?>,
            'Should Show': <?php echo $this->should_show_nav() ? 'true' : 'false'; ?>
        });
        
        // Check for BuddyPress/BuddyBoss elements
        jQuery(document).ready(function($) {
            console.log('Navigation containers found:');
            console.log('  .bp-navs ul:', $('.bp-navs ul').length);
            console.log('  #subnav ul:', $('#subnav ul').length);
            console.log('  .item-list-tabs ul:', $('.item-list-tabs ul').length);
            
            console.log('Location search nav element:', $('#members-location-search').length ? 'Found' : 'Not Found');
            
            if ($('#members-location-search').length) {
                console.log('Location search nav element details:', $('#members-location-search')[0]);
            }
            
            // List all navigation items for debugging
            $('.bp-navs ul li, #subnav ul li, .item-list-tabs ul li').each(function(index) {
                console.log('Nav item ' + index + ':', $(this).attr('id') || 'no-id', $(this).text().trim());
            });
        });
        </script>
        <?php
    }
}

// Initialize the simple navigation with higher priority
add_action('bp_init', function() {
    error_log('BB Location Finder - bp_init hook fired, checking for BuddyBoss');
    
    if (class_exists('BB_Location_Finder') && function_exists('bp_is_active')) {
        error_log('BB Location Finder - BuddyBoss detected, initializing navigation');
        new BB_Location_Simple_Nav();
    } else {
        error_log('BB Location Finder - BuddyBoss not detected or plugin not loaded');
    }
}, 20);