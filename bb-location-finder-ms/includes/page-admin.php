<?php
// includes/page-admin.php

/**
 * Admin interface for Location Search page management
 */
class BB_Location_Page_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add admin menu items
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('network_admin_menu', array($this, 'add_network_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add quick setup notice
        add_action('admin_notices', array($this, 'show_setup_notice'));
        add_action('network_admin_notices', array($this, 'show_setup_notice'));
    }
    
    /**
     * Add admin menu for individual sites
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            __('Location Search Page', 'bb-location-finder'),
            __('Location Search Page', 'bb-location-finder'),
            'manage_options',
            'bb-location-page',
            array($this, 'page_settings_content')
        );
    }
    
    /**
     * Add network admin menu
     */
    public function add_network_admin_menu() {
        add_submenu_page(
            'buddyboss-advanced-enhancements',
            __('Location Search Pages', 'bb-location-finder'),
            __('Location Search Pages', 'bb-location-finder'),
            'manage_network_options',
            'bb-location-pages',
            array($this, 'network_page_settings_content')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('bb_location_page_settings', 'bb_location_search_page_id');
        register_setting('bb_location_page_settings', 'bb_location_show_in_members_nav');
        register_setting('bb_location_page_settings', 'bb_location_nav_text');
    }
    
    /**
     * Show setup notice if page isn't configured
     */
    public function show_setup_notice() {
        // Only show on relevant admin pages
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, ['settings_page_bb-location-finder', 'settings_page_bb-location-page'])) {
            return;
        }
        
        $page_id = get_option('bb_location_search_page_id');
        
        if (empty($page_id) || !get_post($page_id)) {
            ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Location Search Page Setup', 'bb-location-finder'); ?></strong><br>
                    <?php _e('To show the Location Search link in your members directory, you need to configure which page it should link to.', 'bb-location-finder'); ?>
                    <a href="<?php echo admin_url('options-general.php?page=bb-location-page'); ?>" class="button button-secondary">
                        <?php _e('Configure Page', 'bb-location-finder'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Individual site page settings
     */
    public function page_settings_content() {
        $page_id = get_option('bb_location_search_page_id');
        $show_in_nav = get_option('bb_location_show_in_members_nav', 'yes');
        $nav_text = get_option('bb_location_nav_text', __('Location Search', 'bb-location-finder'));
        
        // Get available pages
        $pages = get_pages(array('post_status' => 'publish'));
        
        ?>
        <div class="wrap">
            <h1><?php _e('Location Search Page Settings', 'bb-location-finder'); ?></h1>
            
            <div class="card">
                <h2><?php _e('How It Works', 'bb-location-finder'); ?></h2>
                <p><?php _e('This creates a "Location Search" link in your BuddyBoss members directory that takes visitors to a page where they can search for members by location.', 'bb-location-finder'); ?></p>
                <ol>
                    <li><?php _e('Create a page manually and add this shortcode to it:', 'bb-location-finder'); ?><br>
                        <code>[bb_location_search radius_options="5,10,25,50,100,250" unit="mi" show_map="no" exclude_profile_types="staff,moderator"]</code>
                    </li>
                    <li><?php _e('Select that page below to make the navigation link point to it', 'bb-location-finder'); ?></li>
                    <li><?php _e('Enable the navigation link and customize the text if desired', 'bb-location-finder'); ?></li>
                </ol>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('bb_location_page_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="bb_location_search_page_id"><?php _e('Location Search Page', 'bb-location-finder'); ?></label>
                        </th>
                        <td>
                            <select id="bb_location_search_page_id" name="bb_location_search_page_id" class="regular-text">
                                <option value=""><?php _e('Select a page...', 'bb-location-finder'); ?></option>
                                <?php foreach ($pages as $page): ?>
                                    <option value="<?php echo $page->ID; ?>" <?php selected($page_id, $page->ID); ?>>
                                        <?php echo esc_html($page->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Select the page that contains your location search shortcode.', 'bb-location-finder'); ?>
                                <?php if (empty($pages)): ?>
                                    <br><strong><?php _e('No pages found. Create a page first, then return here to configure it.', 'bb-location-finder'); ?></strong>
                                <?php else: ?>
                                    <br><a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" target="_blank"><?php _e('Create a new page', 'bb-location-finder'); ?></a>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bb_location_show_in_members_nav"><?php _e('Show Navigation Link', 'bb-location-finder'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="bb_location_show_in_members_nav" name="bb_location_show_in_members_nav" value="yes" <?php checked($show_in_nav, 'yes'); ?> />
                                <?php _e('Add "Location Search" link to BuddyBoss members directory navigation', 'bb-location-finder'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, a link to your Location Search page will appear in the members directory.', 'bb-location-finder'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bb_location_nav_text"><?php _e('Navigation Link Text', 'bb-location-finder'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bb_location_nav_text" name="bb_location_nav_text" value="<?php echo esc_attr($nav_text); ?>" class="regular-text" />
                            <p class="description">
                                <?php _e('The text that will appear for the navigation link.', 'bb-location-finder'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <?php if ($page_id && get_post($page_id)): ?>
                <hr>
                <h2><?php _e('Current Configuration', 'bb-location-finder'); ?></h2>
                <p>
                    <strong><?php _e('Target Page:', 'bb-location-finder'); ?></strong> 
                    <a href="<?php echo get_permalink($page_id); ?>" target="_blank">
                        <?php echo get_the_title($page_id); ?>
                    </a>
                    <a href="<?php echo get_edit_post_link($page_id); ?>" class="button button-secondary">
                        <?php _e('Edit Page', 'bb-location-finder'); ?>
                    </a>
                </p>
                
                <?php if ($show_in_nav === 'yes'): ?>
                    <p><strong><?php _e('Navigation Link:', 'bb-location-finder'); ?></strong> 
                        <?php _e('Enabled - Link appears in members directory as', 'bb-location-finder'); ?> 
                        "<strong>📍 <?php echo esc_html($nav_text); ?></strong>"
                    </p>
                    <p><strong><?php _e('Link URL:', 'bb-location-finder'); ?></strong> 
                        <code><?php echo get_permalink($page_id); ?></code>
                    </p>
                <?php else: ?>
                    <p><strong><?php _e('Navigation Link:', 'bb-location-finder'); ?></strong> 
                        <?php _e('Disabled - Page can be accessed directly but won\'t appear in members directory', 'bb-location-finder'); ?>
                    </p>
                <?php endif; ?>
                
                <div class="notice notice-info inline">
                    <p><strong><?php _e('Recommended Shortcode:', 'bb-location-finder'); ?></strong><br>
                    <code>[bb_location_search radius_options="5,10,25,50,100,250" unit="mi" show_map="no" exclude_profile_types="staff,moderator"]</code></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Network admin page settings - simplified version
     */
    public function network_page_settings_content() {
        // Get all sites in network
        $sites = get_sites();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Location Search Pages - Network Overview', 'bb-location-finder'); ?></h1>
            
            <p><?php _e('View Location Search page settings across all sites in your network. Configure each site individually using the links below.', 'bb-location-finder'); ?></p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Site', 'bb-location-finder'); ?></th>
                        <th><?php _e('Location Page', 'bb-location-finder'); ?></th>
                        <th><?php _e('Navigation', 'bb-location-finder'); ?></th>
                        <th><?php _e('Actions', 'bb-location-finder'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sites as $site): ?>
                        <?php 
                        switch_to_blog($site->blog_id);
                        $page_id = get_option('bb_location_search_page_id');
                        $show_in_nav = get_option('bb_location_show_in_members_nav', 'yes');
                        $nav_text = get_option('bb_location_nav_text', __('Location Search', 'bb-location-finder'));
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo get_bloginfo('name'); ?></strong><br>
                                <small><?php echo get_site_url(); ?></small>
                            </td>
                            <td>
                                <?php if ($page_id && get_post($page_id)): ?>
                                    <a href="<?php echo get_permalink($page_id); ?>" target="_blank">
                                        <?php echo get_the_title($page_id); ?>
                                    </a>
                                    <br><small><?php _e('Page ID:', 'bb-location-finder'); ?> <?php echo $page_id; ?></small>
                                <?php else: ?>
                                    <span class="error"><?php _e('Not configured', 'bb-location-finder'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($show_in_nav === 'yes'): ?>
                                    <span class="success">✓ <?php _e('Enabled', 'bb-location-finder'); ?></span><br>
                                    <small>"📍 <?php echo esc_html($nav_text); ?>"</small>
                                <?php else: ?>
                                    <span class="error">✗ <?php _e('Disabled', 'bb-location-finder'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo get_admin_url($site->blog_id, 'options-general.php?page=bb-location-page'); ?>" class="button button-secondary button-small">
                                    <?php _e('Configure', 'bb-location-finder'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php restore_current_blog(); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="card">
                <h2><?php _e('Setup Instructions', 'bb-location-finder'); ?></h2>
                <p><?php _e('To set up Location Search on each site:', 'bb-location-finder'); ?></p>
                <ol>
                    <li><?php _e('Click "Configure" for each site above', 'bb-location-finder'); ?></li>
                    <li><?php _e('Create a page with the location search shortcode', 'bb-location-finder'); ?></li>
                    <li><?php _e('Select that page in the settings and enable the navigation link', 'bb-location-finder'); ?></li>
                </ol>
                <p><strong><?php _e('Recommended Shortcode:', 'bb-location-finder'); ?></strong><br>
                <code>[bb_location_search radius_options="5,10,25,50,100,250" unit="mi" show_map="no" exclude_profile_types="staff,moderator"]</code></p>
            </div>
        </div>
        
        <style>
            .success { color: #46b450; }
            .error { color: #dc3232; }
        </style>
        <?php
    }
}

// Initialize the page admin
new BB_Location_Page_Admin();