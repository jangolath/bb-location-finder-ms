<?php
/**
 * Plugin Name: BuddyBoss Location Finder
 * Description: Allows BuddyBoss users to set their location and search for other members by proximity, name, and profile type
 * Version: 1.1.10
 * Author: Jason Wood
 * Text Domain: bb-location-finder
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Network: true
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * Main Plugin Class
 */
class BB_Location_Finder {
    
    /**
     * Plugin version
     */
    const VERSION = '1.1.10';
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Define constants
        $this->define_constants();
        
        // Check dependencies
        if (!$this->check_dependencies()) {
            return;
        }
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Define plugin constants
     */
    private function define_constants() {
        define('BB_LOCATION_FINDER_VERSION', self::VERSION);
        define('BB_LOCATION_FINDER_FILE', __FILE__);
        define('BB_LOCATION_FINDER_DIR', plugin_dir_path(__FILE__));
        define('BB_LOCATION_FINDER_URL', plugin_dir_url(__FILE__));
        define('BB_LOCATION_FINDER_BASENAME', plugin_basename(__FILE__));
    }
    
    /**
     * Check if BuddyBoss is active
     */
    private function check_dependencies() {
        if (!function_exists('bp_is_active')) {
            add_action('admin_notices', array($this, 'buddyboss_missing_notice'));
            return false;
        }
        return true;
    }
    
    /**
     * Display admin notice for missing BuddyBoss
     */
    public function buddyboss_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('BuddyBoss Location Finder requires BuddyBoss Platform to be installed and activated.', 'bb-location-finder'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Register assets
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        
        // Initialize components
        add_action('plugins_loaded', array($this, 'init_components'), 20);
        
        // Add admin menu and settings pages
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('network_admin_menu', array($this, 'add_network_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add AJAX handlers
        add_action('init', array($this, 'register_ajax_handlers'));
        
        // Add action links
        add_filter('plugin_action_links_' . BB_LOCATION_FINDER_BASENAME, array($this, 'add_action_links'));
        add_filter('network_admin_plugin_action_links_' . BB_LOCATION_FINDER_BASENAME, array($this, 'add_network_action_links'));
        
        // Handle network settings
        add_action('network_admin_edit_bb_location_finder_update_network_options', array($this, 'handle_network_options'));
        
        // Add shortcode tester for admins only
        if (current_user_can('administrator')) {
            $this->add_shortcode_tester();
        }
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('bb-location-finder', false, dirname(BB_LOCATION_FINDER_BASENAME) . '/languages');
    }
    
    /**
     * Add admin menu for individual sites
     */
    public function add_admin_menu() {
        add_options_page(
            __('Location Finder Settings', 'bb-location-finder'),
            __('Location Finder', 'bb-location-finder'),
            'manage_options',
            'bb-location-finder',
            array($this, 'settings_page_content')
        );
    }
    
    /**
     * Add admin menu for network
     */
    public function add_network_admin_menu() {
        // Make sure the parent exists (create it if needed)
        if (!menu_page_url('buddyboss-advanced-enhancements', false)) {
            // Create parent menu if it doesn't exist
            add_menu_page(
                __('BuddyBoss Advanced Enhancements', 'bb-location-finder'),
                __('BB Advanced', 'bb-location-finder'),
                'manage_network_options',
                'buddyboss-advanced-enhancements',
                function() {
                    echo '<div class="wrap">';
                    echo '<h1>' . __('BuddyBoss Advanced Enhancements', 'bb-location-finder') . '</h1>';
                    echo '<p>' . __('Welcome to BuddyBoss Advanced Enhancements. Use the submenu to access specific features.', 'bb-location-finder') . '</p>';
                    echo '</div>';
                },
                'dashicons-buddicons-buddypress-logo',
                3
            );
        }
        
        // Add Location Finder as a submenu
        add_submenu_page(
            'buddyboss-advanced-enhancements',       // Parent slug
            __('Location Finder Settings', 'bb-location-finder'),
            __('Location Finder', 'bb-location-finder'),
            'manage_network_options',
            'bb-location-finder',
            array($this, 'settings_page_content')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('bb_location_finder_settings', 'bb_location_google_api_key');
    }
    
    /**
     * Settings page content
     */
    public function settings_page_content() {
        $is_network_admin = is_multisite() && is_network_admin();
        $form_action = $is_network_admin ? 'edit.php?action=bb_location_finder_update_network_options' : 'options.php';
        
        // Get API key
        $api_key = $is_network_admin ? 
                   get_site_option('bb_location_google_api_key', '') : 
                   get_option('bb_location_google_api_key', '');
        
        // Show settings updated message if needed
        if (isset($_GET['updated']) && $_GET['updated'] === 'true' && $is_network_admin) {
            ?>
            <div class="updated notice is-dismissible">
                <p><?php _e('Settings saved.', 'bb-location-finder'); ?></p>
            </div>
            <?php
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Location Finder Settings', 'bb-location-finder'); ?></h1>
            
            <?php if ($is_network_admin): ?>
            <p><?php _e('These settings will apply to all sites in the network.', 'bb-location-finder'); ?></p>
            <?php endif; ?>
            
            <!-- NEW: Quick links section -->
            <div class="card">
                <h2><?php _e('Quick Setup', 'bb-location-finder'); ?></h2>
                <p><?php _e('Get started with Location Finder:', 'bb-location-finder'); ?></p>
                <ul>
                    <li>
                        <?php if ($is_network_admin): ?>
                            <a href="<?php echo network_admin_url('admin.php?page=bb-location-pages'); ?>" class="button button-secondary">
                                <?php _e('Configure Location Search Pages', 'bb-location-finder'); ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo admin_url('options-general.php?page=bb-location-page'); ?>" class="button button-secondary">
                                <?php _e('Set Up Location Search Page', 'bb-location-finder'); ?>
                            </a>
                        <?php endif; ?>
                        - <?php _e('Create and configure your location search page', 'bb-location-finder'); ?>
                    </li>
                    <li>
                        <a href="<?php echo admin_url('options-general.php?page=bb-location-finder'); ?>" class="button button-secondary">
                            <?php _e('API Settings', 'bb-location-finder'); ?>
                        </a>
                        - <?php _e('Configure Google Maps API key', 'bb-location-finder'); ?>
                    </li>
                </ul>
            </div>
            
            <form method="post" action="<?php echo esc_url($form_action); ?>">
                <?php if ($is_network_admin): ?>
                    <?php wp_nonce_field('bb_location_finder_network_options'); ?>
                    <input type="hidden" name="action" value="bb_location_finder_update_network_options">
                <?php else: ?>
                    <?php settings_fields('bb_location_finder_settings'); ?>
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="bb_location_google_api_key"><?php _e('Google Maps API Key', 'bb-location-finder'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bb_location_google_api_key" name="bb_location_google_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                            <p class="description">
                                <?php _e('Enter your Google Maps API key with Geocoding, Places and Maps JavaScript APIs enabled.', 'bb-location-finder'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Register CSS and JS assets
     */
    public function register_assets() {
        // Register styles
        wp_register_style(
            'bb-location-finder-styles', 
            BB_LOCATION_FINDER_URL . 'assets/css/bb-location-finder.css', 
            array(), 
            BB_LOCATION_FINDER_VERSION
        );
        
        // Get API key for Google Maps - check network options first on multisite
        $api_key = '';
        if (is_multisite()) {
            $api_key = get_site_option('bb_location_google_api_key', '');
        }
        
        // If not set at network level or not a multisite, check local site options
        if (empty($api_key)) {
            $api_key = get_option('bb_location_google_api_key', '');
        }
        
        // Register scripts - IMPORTANT: Load Maps script before the plugin's script
        if (!empty($api_key)) {
            // Register Google Maps with Places library
            wp_register_script(
                'google-maps', 
                'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places', 
                array(), 
                null, 
                true
            );
            
            // Log for debugging
            error_log('BB Location Finder - Google Maps API script registered with key length: ' . strlen($api_key));
        } else {
            error_log('BB Location Finder - No Google Maps API key found');
        }
        
        // Register plugin script with dependency on Google Maps
        wp_register_script(
            'bb-location-finder-js', 
            BB_LOCATION_FINDER_URL . 'assets/js/bb-location-finder.js', 
            array('jquery', 'google-maps'), 
            BB_LOCATION_FINDER_VERSION, 
            true
        );
        
        // Localize script with API key and other variables
        wp_localize_script('bb-location-finder-js', 'bbLocationFinderVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'siteUrl' => site_url(),
            'apiKey' => $api_key,
            'nonce' => wp_create_nonce('bb-location-finder-nonce'),
            'strings' => array(
                'search_error' => __('Error searching for members. Please try again.', 'bb-location-finder'),
                'no_results' => __('No members found in this area.', 'bb-location-finder'),
                'search_center' => __('Search Center', 'bb-location-finder'),
                'view_profile' => __('View Profile', 'bb-location-finder'),
                'member' => __('member', 'bb-location-finder'),
                'members' => __('members', 'bb-location-finder'),
                'found' => __('found', 'bb-location-finder')
            )
        ));
    }
    
    /**
     * Initialize components
     */
    public function init_components() {
        // Include required files
        $this->include_files();
        
        // Initialize profile fields
        new BB_Location_Profile_Fields();
        
        // Initialize search functionality
        new BB_Location_Search();
        
        // Initialize map functionality
        new BB_Location_Map();
        
        // Initialize geocoding
        require_once BB_LOCATION_FINDER_DIR . 'includes/geocoding.php';
        new BB_Location_Geocoding();
        
        // Initialize page admin - NOTE: This class initializes itself
        require_once BB_LOCATION_FINDER_DIR . 'includes/page-admin.php';
        
        // Initialize simple navigation integration - NOTE: This initializes itself via bp_init hook
        require_once BB_LOCATION_FINDER_DIR . 'includes/simple-nav-integration.php';
        
        // Initialize geocode tester (admin only)
        if (current_user_can('administrator')) {
            require_once BB_LOCATION_FINDER_DIR . 'includes/geocode-tester.php';
            new BB_Location_Geocode_Tester();
        }
        
        // Create shortcodes instance
        global $bb_location_shortcodes;
        $bb_location_shortcodes = new BB_Location_Shortcodes();
        
        // Register shortcodes directly
        add_shortcode('bb_location_setter', array($bb_location_shortcodes, 'location_setter_shortcode'));
        add_shortcode('bb_location_search', array($bb_location_shortcodes, 'location_search_shortcode'));
        
        // Register admin-only geocode test shortcode
        if (current_user_can('administrator')) {
            add_shortcode('bb_location_geocode_test', array(new BB_Location_Geocode_Tester(), 'geocode_test_shortcode'));
        }
    }
        
    /**
     * Include required files
     */
    private function include_files() {
        require_once BB_LOCATION_FINDER_DIR . 'includes/profile-fields.php';
        require_once BB_LOCATION_FINDER_DIR . 'includes/search-functions.php';
        require_once BB_LOCATION_FINDER_DIR . 'includes/shortcodes.php';
        require_once BB_LOCATION_FINDER_DIR . 'includes/map-functions.php';
    }
    
    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        // Location update handlers
        add_action('wp_ajax_bb_location_update', array($this, 'ajax_update_location'));
        add_action('wp_ajax_nopriv_bb_location_update', array($this, 'ajax_update_location_unauthorized'));
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
    
    /**
     * Add settings link to plugins page for individual sites
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=bb-location-finder') . '">' . __('Settings', 'bb-location-finder') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Add settings link to plugins page for network admin
     */
    public function add_network_action_links($links) {
        $settings_link = '<a href="' . network_admin_url('admin.php?page=bb-location-finder') . '">' . __('Settings', 'bb-location-finder') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Handle saving network options
     */
    public function handle_network_options() {
        if (!current_user_can('manage_network_options')) {
            wp_die(__('Sorry, you are not allowed to access this page.', 'bb-location-finder'));
        }
        
        check_admin_referer('bb_location_finder_network_options');
        
        // Save API key
        if (isset($_POST['bb_location_google_api_key'])) {
            update_site_option('bb_location_google_api_key', sanitize_text_field($_POST['bb_location_google_api_key']));
        }
        
        // Redirect back to settings page
        $redirect_url = add_query_arg(array(
            'page' => 'bb-location-finder',
            'updated' => 'true',
        ), network_admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Activation hook
     */
    public static function activate($network_wide) {
        if (is_multisite() && $network_wide) {
            // Activated on the network
            global $wpdb;
            
            // Get all blog IDs
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                self::single_site_activate();
                restore_current_blog();
            }
            
            // Set network options
            add_site_option('bb_location_google_api_key', '');
            
        } else {
            // Activated on a single site
            self::single_site_activate();
        }
    }
    
    /**
     * Single site activation
     */
    public static function single_site_activate() {
        // Create default options if not already set
        if (!get_option('bb_location_google_api_key')) {
            add_option('bb_location_google_api_key', '');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Deactivation hook
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Uninstall hook
     */
    public static function uninstall() {
        // Check if multisite
        if (is_multisite()) {
            // Remove network options
            delete_site_option('bb_location_google_api_key');
            
            // Loop through sites
            global $wpdb;
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                self::single_site_uninstall();
                restore_current_blog();
            }
        } else {
            // Single site
            self::single_site_uninstall();
        }
    }
    
    /**
     * Single site uninstall
     */
    public static function single_site_uninstall() {
        // Remove options
        delete_option('bb_location_google_api_key');
    }

    /**
     * Add shortcode tester
     */
    public function add_shortcode_tester() {
        add_shortcode('bb_location_test', function() {
            return '<div style="border: 2px solid green; padding: 10px; margin: 10px 0; background: #f0f8f0;">
                <h3>BB Location Finder Shortcode Test</h3>
                <p>If you can see this message, WordPress shortcode processing is working correctly.</p>
                <p>Debug Info:</p>
                <ul>
                    <li>Plugin Version: ' . BB_LOCATION_FINDER_VERSION . '</li>
                    <li>bb_location_setter shortcode registered: ' . (shortcode_exists('bb_location_setter') ? 'Yes' : 'No') . '</li>
                    <li>bb_location_search shortcode registered: ' . (shortcode_exists('bb_location_search') ? 'Yes' : 'No') . '</li>
                    <li>Google Maps API Key set: ' . (empty(get_site_option('bb_location_google_api_key')) ? 'No' : 'Yes') . '</li>
                </ul>
            </div>';
        });
    }
}

// Force shortcode registration early
function bb_location_finder_force_shortcodes() {
    if (!shortcode_exists('bb_location_setter') || !shortcode_exists('bb_location_search')) {
        if (!class_exists('BB_Location_Shortcodes')) {
            $path = plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
            if (file_exists($path)) {
                require_once $path;
            } else {
                return;
            }
        }
        
        $shortcodes = new BB_Location_Shortcodes();
        add_shortcode('bb_location_setter', array($shortcodes, 'location_setter_shortcode'));
        add_shortcode('bb_location_search', array($shortcodes, 'location_search_shortcode'));
    }
}
add_action('init', 'bb_location_finder_force_shortcodes', 1);

// Initialize the plugin
function bb_location_finder_init() {
    $instance = BB_Location_Finder::get_instance();
    return $instance;
}

// Start the plugin
add_action('plugins_loaded', 'bb_location_finder_init', 5);

// Register activation, deactivation, and uninstall hooks
register_activation_hook(__FILE__, array('BB_Location_Finder', 'activate'));
register_deactivation_hook(__FILE__, array('BB_Location_Finder', 'deactivate'));
register_uninstall_hook(__FILE__, array('BB_Location_Finder', 'uninstall'));