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
        
        // Add CSS for proper styling
        add_action('wp_head', array($this, 'add_nav_css'));
        
        // Add JavaScript to prevent AJAX handling
        add_action('wp_footer', array($this, 'add_nav_js'));
    }
    
    /**
     * Add location search link to members directory
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
            <li id="members-location-search" class="bb-location-external-link">
                <a href="<?php echo esc_url($page_url); ?>" data-external-link="true">
                    <span>📍 <?php echo esc_html($nav_text); ?></span>
                </a>
            </li>
            <?php
        }
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
            .bp-navs ul li.bb-location-external-link {
                display: inline-block !important;
            }
            
            .bp-navs ul li#members-location-search a,
            .bp-navs ul li.bb-location-external-link a {
                display: block;
                padding: 0 15px;
                color: #939597;
                text-decoration: none;
                border: 0;
                transition: color 0.2s ease;
                cursor: pointer;
            }
            
            .bp-navs ul li#members-location-search a:hover,
            .bp-navs ul li.bb-location-external-link a:hover {
                color: var(--bb-primary-color, #007CFF);
            }
            
            /* BuddyBoss Nouveau template specific styles */
            .buddypress-wrap .bp-navs li#members-location-search a,
            .buddypress-wrap .bp-navs li.bb-location-external-link a {
                background: none;
                border-radius: 0;
                font-weight: 400;
            }
            
            .buddypress-wrap .bp-navs li#members-location-search a:hover,
            .buddypress-wrap .bp-navs li.bb-location-external-link a:hover {
                background: none;
                color: var(--bb-primary-color, #007CFF);
            }
            
            /* Ensure proper alignment with other tabs */
            #buddypress div#subnav.item-list-tabs#subnav li#members-location-search,
            #buddypress .bp-navs li.bb-location-external-link {
                margin: 0;
                float: left;
                list-style: none;
            }
            
            /* Make it clear this is an external link */
            .bp-navs ul li#members-location-search a span,
            .bp-navs ul li.bb-location-external-link a span {
                display: inline-block;
            }
            
            /* Prevent the active/selected styling from applying */
            .bp-navs ul li#members-location-search.selected,
            .bp-navs ul li#members-location-search.current,
            .bp-navs ul li.bb-location-external-link.selected,
            .bp-navs ul li.bb-location-external-link.current {
                background: none !important;
            }
            
            .bp-navs ul li#members-location-search.selected a,
            .bp-navs ul li#members-location-search.current a,
            .bp-navs ul li.bb-location-external-link.selected a,
            .bp-navs ul li.bb-location-external-link.current a {
                color: #939597 !important;
                background: none !important;
            }
        </style>
        <?php
    }
    
    /**
     * Add JavaScript to prevent AJAX handling of our link
     */
    public function add_nav_js() {
        // Only on members directory
        if (!bp_is_members_component() || bp_is_user()) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Prevent BuddyBoss from treating our link as an AJAX navigation
            $('#members-location-search a[data-external-link="true"]').on('click', function(e) {
                // Stop BuddyBoss from intercepting this click
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Navigate to the page normally
                var url = $(this).attr('href');
                if (url) {
                    window.location.href = url;
                }
                
                return false;
            });
            
            // Also prevent any parent containers from handling the click
            $('#members-location-search').on('click', function(e) {
                var $link = $(this).find('a[data-external-link="true"]');
                if ($link.length) {
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    var url = $link.attr('href');
                    if (url) {
                        window.location.href = url;
                    }
                    
                    return false;
                }
            });
            
            // Remove any BuddyBoss AJAX event handlers from our link
            $('#members-location-search a').off('click.bp-nouveau');
            $('#members-location-search').off('click.bp-nouveau');
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
});