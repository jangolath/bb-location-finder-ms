<?php
// includes/simple-nav-integration.php

/**
 * Simple navigation integration - adds a link to the location search page with forced redirect
 */
class BB_Location_Simple_Nav {
    
    private static $script_output = false; // Prevent multiple script outputs
    
    /**
     * Constructor
     */
    public function __construct() {
        // Try multiple hooks to ensure we catch BuddyBoss navigation
        add_action('bp_members_directory_tabs', array($this, 'add_location_link'), 10);
        add_action('bp_before_members_loop', array($this, 'add_location_link_fallback'), 5);
        
        // Also try the nouveau template hooks
        add_filter('bp_nouveau_get_members_directory_nav_items', array($this, 'add_location_nav_item'), 10, 1);
        
        // Add CSS and JavaScript for forced redirect behavior
        add_action('wp_head', array($this, 'add_nav_css'));
        add_action('wp_footer', array($this, 'add_redirect_script'));
        
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
        
        // Only output the fallback script once
        if (!self::$script_output) {
            $this->output_fallback_nav();
            self::$script_output = true;
        }
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
                'li_class' => array('location-search-link', 'bb-location-external-link'),
                'link' => $page_url,
                'text' => $nav_text, // Removed emoji
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
            <li id="members-location-search" class="bb-location-nav-item bb-location-external-link">
                <a href="<?php echo esc_url($page_url); ?>" class="bb-location-redirect-link" data-bb-location-url="<?php echo esc_url($page_url); ?>" data-bb-force-redirect="true">
                    <?php echo esc_html($nav_text); ?>
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
                
                // Prevent multiple additions
                if ($('#members-location-search').length > 0) {
                    console.log('BB Location Finder - Navigation link already exists, attaching handlers only');
                    // Just attach handlers to existing link
                    attachLocationRedirectHandlers();
                    return;
                }
                
                // Try to find the navigation container
                var $nav = $('.bp-navs ul, #subnav ul, .item-list-tabs ul').first();
                
                if ($nav.length) {
                    console.log('BB Location Finder - Found navigation container:', $nav[0]);
                    
                    var linkHtml = '<li id="members-location-search" class="bb-location-nav-item bb-location-external-link">' +
                                  '<a href="<?php echo esc_js($page_url); ?>" class="bb-location-redirect-link" data-bb-location-url="<?php echo esc_js($page_url); ?>" data-bb-force-redirect="true"><?php echo esc_js($nav_text); ?></a>' +
                                  '</li>';
                    
                    $nav.append(linkHtml);
                    console.log('BB Location Finder - Added fallback navigation link');
                    
                    // Attach handlers
                    attachLocationRedirectHandlers();
                } else {
                    console.log('BB Location Finder - Could not find navigation container');
                }
                
                // Function to attach redirect handlers
                function attachLocationRedirectHandlers() {
                    // Remove any existing handlers first
                    $('#members-location-search a, .bb-location-redirect-link').off('click.bb-location');
                    
                    // Attach new handlers with high priority
                    $('#members-location-search a, .bb-location-redirect-link').on('click.bb-location', function(e) {
                        console.log('BB Location Finder - Location search link clicked');
                        
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        
                        var url = $(this).attr('href') || $(this).data('bb-location-url');
                        
                        if (url) {
                            console.log('BB Location Finder - Forcing redirect to:', url);
                            
                            // Multiple ways to ensure redirect happens
                            setTimeout(function() {
                                window.location.href = url;
                            }, 10);
                            
                            // Also try immediate redirect
                            window.location.href = url;
                        } else {
                            console.error('BB Location Finder - No URL found for redirect');
                        }
                        
                        return false;
                    });
                    
                    console.log('BB Location Finder - Attached redirect handlers');
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
                color: #333 !important; /* Changed to black/dark color */
                text-decoration: none;
                border: 0;
                transition: color 0.2s ease;
                line-height: inherit;
                font-size: inherit;
                font-weight: normal;
            }
            
            .bp-navs ul li#members-location-search a:hover,
            .bp-navs ul li.bb-location-nav-item a:hover,
            #subnav li#members-location-search a:hover,
            .item-list-tabs li#members-location-search a:hover {
                color: var(--bb-primary-color, #007CFF) !important;
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
                color: #333 !important; /* Ensure black text */
            }
            
            .buddypress-wrap .bp-navs li#members-location-search a:hover,
            .buddypress-wrap .bp-navs li.bb-location-nav-item a:hover {
                background: none !important;
                color: var(--bb-primary-color, #007CFF) !important;
            }
            
            /* Ensure external links don't get AJAX styling */
            .bb-location-external-link a {
                cursor: pointer !important;
            }
            
            /* Additional specificity for BuddyBoss themes */
            .bp-navs .bb-location-redirect-link,
            #subnav .bb-location-redirect-link {
                color: #333 !important;
            }
            
            .bp-navs .bb-location-redirect-link:hover,
            #subnav .bb-location-redirect-link:hover {
                color: var(--bb-primary-color, #007CFF) !important;
            }
        </style>
        <?php
    }
    
    /**
     * Add JavaScript to force redirect behavior
     */
    public function add_redirect_script() {
        // Only on members directory
        if (!bp_is_members_component() || bp_is_user()) {
            return;
        }
        
        // Only output once
        if (self::$script_output) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('BB Location Finder - Setting up aggressive redirect behavior');
            
            // Function to handle the redirect with maximum prevention
            function forceLocationRedirect(e) {
                console.log('BB Location Finder - Force redirect triggered');
                
                // Stop everything
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                var url = $(this).attr('href') || $(this).data('bb-location-url');
                
                if (url) {
                    console.log('BB Location Finder - Redirecting to:', url);
                    
                    // Multiple redirect attempts to ensure it works
                    window.location.replace(url);
                    
                    // Backup methods
                    setTimeout(function() {
                        window.location.href = url;
                    }, 50);
                    
                    setTimeout(function() {
                        document.location = url;
                    }, 100);
                } else {
                    console.error('BB Location Finder - No URL found for redirect');
                }
                
                return false;
            }
            
            // Attach handlers with maximum priority
            function attachAggressiveHandlers() {
                // Remove all existing handlers first
                $('#members-location-search a, .bb-location-redirect-link').off();
                
                // Attach our handler with maximum priority
                $('#members-location-search a, .bb-location-redirect-link').on('click', forceLocationRedirect);
                
                console.log('BB Location Finder - Attached aggressive redirect handlers to', 
                    $('#members-location-search a, .bb-location-redirect-link').length, 'elements');
            }
            
            // Initial attachment
            setTimeout(attachAggressiveHandlers, 100);
            
            // Re-attach periodically to override BuddyBoss handlers
            setInterval(attachAggressiveHandlers, 2000);
            
            // Use capture phase to intercept clicks before BuddyBoss
            document.addEventListener('click', function(e) {
                var target = e.target;
                if (target && (
                    target.id === 'members-location-search' ||
                    target.closest('#members-location-search') ||
                    target.classList.contains('bb-location-redirect-link') ||
                    (target.getAttribute('href') && target.getAttribute('href').indexOf('location-search') !== -1)
                )) {
                    console.log('BB Location Finder - Captured click in capture phase');
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    var url = target.getAttribute('href') || target.getAttribute('data-bb-location-url');
                    if (url) {
                        console.log('BB Location Finder - Capture phase redirect to:', url);
                        window.location.href = url;
                    }
                    
                    return false;
                }
            }, true); // Use capture phase
            
            // Override BuddyBoss AJAX handling with more specific selectors
            $(document).on('click', '.bp-navs a, #subnav a, .item-list-tabs a', function(e) {
                var href = $(this).attr('href');
                var isLocationLink = $(this).hasClass('bb-location-redirect-link') || 
                                    $(this).closest('#members-location-search').length > 0 ||
                                    $(this).attr('data-bb-force-redirect') === 'true';
                
                if (isLocationLink && href) {
                    console.log('BB Location Finder - Intercepting BuddyBoss AJAX navigation');
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    window.location.href = href;
                    return false;
                }
            });
            
            // Watch for DOM changes and reattach handlers
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    var needsReattach = false;
                    
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            var $target = $(mutation.target);
                            if ($target.find('#members-location-search').length > 0 || 
                                $target.is('#members-location-search') ||
                                $target.find('.bb-location-redirect-link').length > 0) {
                                needsReattach = true;
                            }
                        }
                    });
                    
                    if (needsReattach) {
                        setTimeout(attachAggressiveHandlers, 100);
                    }
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        });
        </script>
        <?php
        
        self::$script_output = true;
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