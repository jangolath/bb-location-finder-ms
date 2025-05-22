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
        
        // Handle form submissions
        add_action('admin_post_bb_location_create_page', array($this, 'handle_create_page'));
        add_action('network_admin_edit_bb_location_finder_update_page_options', array($this, 'handle_network_page_options'));
        
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
        register_setting('bb_location_page_settings', 'bb_location_page_shortcode_atts');
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
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('Location Search Page Setup Required', 'bb-location-finder'); ?></strong><br>
                    <?php _e('You need to configure a Location Search page to use the BuddyBoss integration.', 'bb-location-finder'); ?>
                    <a href="<?php echo admin_url('options-general.php?page=bb-location-page'); ?>" class="button button-secondary">
                        <?php _e('Set Up Page', 'bb-location-finder'); ?>
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
        $shortcode_atts = get_option('bb_location_page_shortcode_atts', 'radius_options="5,10,25,50,100,250" unit="mi" show_map="no" exclude_profile_types="staff,moderator"');
        
        // Get available pages
        $pages = get_pages(array('post_status' => 'publish'));
        
        ?>
        <div class="wrap">
            <h1><?php _e('Location Search Page Settings', 'bb-location-finder'); ?></h1>
            
            <?php if (isset($_GET['created']) && $_GET['created'] === 'success'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Location Search page created successfully!', 'bb-location-finder'); ?></p>
                </div>
            <?php endif; ?>
            
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
                                <?php _e('Select the page that will contain your location search functionality.', 'bb-location-finder'); ?>
                                <?php if (empty($pages)): ?>
                                    <br><strong><?php _e('No pages found. Create a page first, then return here to configure it.', 'bb-location-finder'); ?></strong>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bb_location_show_in_members_nav"><?php _e('Show in Members Navigation', 'bb-location-finder'); ?></label>
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
                    
                    <tr>
                        <th scope="row">
                            <label for="bb_location_page_shortcode_atts"><?php _e('Shortcode Attributes', 'bb-location-finder'); ?></label>
                        </th>
                        <td>
                            <textarea id="bb_location_page_shortcode_atts" name="bb_location_page_shortcode_atts" rows="3" class="large-text"><?php echo esc_textarea($shortcode_atts); ?></textarea>
                            <p class="description">
                                <?php _e('Customize the location search shortcode attributes for this page. Example:', 'bb-location-finder'); ?><br>
                                <code>radius_options="5,10,25,50,100,250" unit="mi" show_map="no" exclude_profile_types="staff,moderator"</code><br>
                                <?php _e('Available options: radius_options, unit (km/mi), show_map (yes/no), results_per_page, exclude_profile_types', 'bb-location-finder'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Quick Setup', 'bb-location-finder'); ?></h2>
            <p><?php _e('Don\'t have a page yet? Create one automatically with the location search shortcode already configured.', 'bb-location-finder'); ?></p>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="bb_location_create_page">
                <?php wp_nonce_field('bb_location_create_page'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="page_title"><?php _e('Page Title', 'bb-location-finder'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="page_title" name="page_title" value="<?php _e('Find Members Near Me', 'bb-location-finder'); ?>" class="regular-text" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="page_slug"><?php _e('Page Slug', 'bb-location-finder'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="page_slug" name="page_slug" value="find-members-near-me" class="regular-text" />
                            <p class="description"><?php _e('Leave blank to generate automatically from title.', 'bb-location-finder'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Create Location Search Page', 'bb-location-finder'), 'primary', 'create_page'); ?>
            </form>
            
            <?php if ($page_id && get_post($page_id)): ?>
                <hr>
                <h2><?php _e('Current Page Preview', 'bb-location-finder'); ?></h2>
                <p>
                    <strong><?php _e('Page:', 'bb-location-finder'); ?></strong> 
                    <a href="<?php echo get_permalink($page_id); ?>" target="_blank">
                        <?php echo get_the_title($page_id); ?>
                    </a>
                    <a href="<?php echo get_edit_post_link($page_id); ?>" class="button button-secondary">
                        <?php _e('Edit Page', 'bb-location-finder'); ?>
                    </a>
                </p>
                
                <p><strong><?php _e('Current shortcode on page:', 'bb-location-finder'); ?></strong></p>
                <code>[bb_location_search <?php echo esc_html($shortcode_atts); ?>]</code>
                
                <?php if ($show_in_nav === 'yes'): ?>
                    <p><strong><?php _e('BuddyBoss Integration:', 'bb-location-finder'); ?></strong> 
                        <?php _e('Enabled - Link appears in members directory as', 'bb-location-finder'); ?> 
                        "<strong><?php echo esc_html($nav_text); ?></strong>"
                    </p>
                <?php else: ?>
                    <p><strong><?php _e('BuddyBoss Integration:', 'bb-location-finder'); ?></strong> 
                        <?php _e('Disabled - Page can be accessed directly but won\'t appear in members directory', 'bb-location-finder'); ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Network admin page settings
     */
    public function network_page_settings_content() {
        // Get all sites in network
        $sites = get_sites();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Location Search Pages - Network Overview', 'bb-location-finder'); ?></h1>
            
            <p><?php _e('Manage Location Search page settings across all sites in your network.', 'bb-location-finder'); ?></p>
            
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
                                    <small>"<?php echo esc_html($nav_text); ?>"</small>
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
            
            <h2><?php _e('Network-wide Quick Actions', 'bb-location-finder'); ?></h2>
            <p><?php _e('Apply settings to sites that don\'t have Location Search pages configured yet.', 'bb-location-finder'); ?></p>
            
            <form method="post" action="edit.php?action=bb_location_finder_update_page_options">
                <?php wp_nonce_field('bb_location_finder_page_options'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_page_title"><?php _e('Default Page Title', 'bb-location-finder'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="default_page_title" name="default_page_title" value="<?php _e('Find Members Near Me', 'bb-location-finder'); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="default_nav_text"><?php _e('Default Navigation Text', 'bb-location-finder'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="default_nav_text" name="default_nav_text" value="<?php _e('Location Search', 'bb-location-finder'); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="enable_navigation"><?php _e('Enable Navigation', 'bb-location-finder'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="enable_navigation" name="enable_navigation" value="yes" checked />
                                <?php _e('Enable BuddyBoss navigation integration by default', 'bb-location-finder'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Create Pages for Unconfigured Sites', 'bb-location-finder'), 'primary', 'bulk_create'); ?>
            </form>
        </div>
        
        <style>
            .success { color: #46b450; }
            .error { color: #dc3232; }
        </style>
        <?php
    }
    
    /**
     * Handle page creation
     */
    public function handle_create_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'bb-location-finder'));
        }
        
        check_admin_referer('bb_location_create_page');
        
        $page_title = sanitize_text_field($_POST['page_title']);
        $page_slug = sanitize_title($_POST['page_slug']);
        
        if (empty($page_slug)) {
            $page_slug = sanitize_title($page_title);
        }
        
        // Use your preferred shortcode attributes
        $shortcode_atts = 'radius_options="5,10,25,50,100,250" unit="mi" show_map="no" exclude_profile_types="staff,moderator"';
        
        // Create page content
        $page_content = '<h2>' . __('Find Members Near You', 'bb-location-finder') . '</h2>' . "\n\n";
        $page_content .= '<p>' . __('Enter your location below to find community members in your area.', 'bb-location-finder') . '</p>' . "\n\n";
        $page_content .= '[bb_location_search ' . $shortcode_atts . ']';
        
        // Create the page
        $page_id = wp_insert_post(array(
            'post_title' => $page_title,
            'post_name' => $page_slug,
            'post_content' => $page_content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
        ));
        
        if ($page_id && !is_wp_error($page_id)) {
            // Update settings
            update_option('bb_location_search_page_id', $page_id);
            update_option('bb_location_show_in_members_nav', 'yes');
            
            // Store the shortcode attributes that were used
            update_option('bb_location_page_shortcode_atts', $shortcode_atts);
            
            // Redirect with success message
            wp_redirect(admin_url('options-general.php?page=bb-location-page&created=success'));
        } else {
            wp_die(__('Failed to create page. Please try again.', 'bb-location-finder'));
        }
        
        exit;
    }
    
    /**
     * Handle network page options
     */
    public function handle_network_page_options() {
        if (!current_user_can('manage_network_options')) {
            wp_die(__('You do not have permission to perform this action.', 'bb-location-finder'));
        }
        
        check_admin_referer('bb_location_finder_page_options');
        
        $page_title = sanitize_text_field($_POST['default_page_title']);
        $nav_text = sanitize_text_field($_POST['default_nav_text']);
        $enable_nav = isset($_POST['enable_navigation']) ? 'yes' : 'no';
        
        // Get all sites
        $sites = get_sites();
        $created_count = 0;
        
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            
            // Check if site already has a location page configured
            $existing_page_id = get_option('bb_location_search_page_id');
            
            if (empty($existing_page_id) || !get_post($existing_page_id)) {
                // Create page for this site with proper shortcode content
                $page_content = '<h2>' . __('Find Members Near You', 'bb-location-finder') . '</h2>' . "\n\n";
                $page_content .= '<p>' . __('Enter your location below to find community members in your area.', 'bb-location-finder') . '</p>' . "\n\n";
                $page_content .= '[bb_location_search radius_options="5,10,25,50,100,250" unit="mi" show_map="no" exclude_profile_types="staff,moderator"]';
                
                $page_id = wp_insert_post(array(
                    'post_title' => $page_title,
                    'post_name' => sanitize_title($page_title),
                    'post_content' => $page_content,
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1, // Default to admin user
                ));
                
                if ($page_id && !is_wp_error($page_id)) {
                    update_option('bb_location_search_page_id', $page_id);
                    update_option('bb_location_show_in_members_nav', $enable_nav);
                    update_option('bb_location_nav_text', $nav_text);
                    
                    // Also update the shortcode attributes option to match what we put in the page
                    update_option('bb_location_page_shortcode_atts', 'radius_options="5,10,25,50,100,250" unit="mi" show_map="no" exclude_profile_types="staff,moderator"');
                    
                    $created_count++;
                }
            }
            
            restore_current_blog();
        }
        
        // Redirect with success message
        $redirect_url = add_query_arg(array(
            'page' => 'bb-location-pages',
            'created' => $created_count,
        ), network_admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
}

// Initialize the page admin
new BB_Location_Page_Admin();