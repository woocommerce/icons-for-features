<?php
/**
 * Plugin Name: Icons For Features
 * Plugin URI: http://www.woothemes.com/products/icons-for-features/
 * Description: Hey there! Do you want to display awesome icons for each of your features? Look no further, I'm here to help!
 * Version: 1.1.0
 * Author: WooThemes
 * Author URI: http://woothemes.com/
 * Requires at least: 3.8.1
 * Tested up to: 4.1.1
 *
 * Text Domain: icons-for-features
 * Domain Path: /languages/
 *
 * @package Icons_For_Features
 * @category Core
 * @author Matty
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Returns the main instance of Icons_For_Features to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Icons_For_Features
 */
function Icons_For_Features() {
	return Icons_For_Features::instance();
} // End Icons_For_Features()

Icons_For_Features();

/**
 * Main Icons_For_Features Class
 *
 * @class Icons_For_Features
 * @version	1.0.0
 * @since 1.0.0
 * @package	Kudos
 * @author Matty
 */
final class Icons_For_Features {
	/**
	 * Icons_For_Features The single instance of Icons_For_Features.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * An instance of the Icons_For_Features_Admin class.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * The name of the hook on which we will be working our magic.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $hook;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct () {
		$this->token 			= 'icons-for-features';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '2.0.0';

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		/* Conditionally load the admin. */
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'register_styles' ) );

			require_once( 'classes/class-icons-for-features-admin.php' );
			$this->admin = new Icons_For_Features_Admin();
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
			// An unfortunate caveat, as we need to wait for the entire page to load, in order to determine whether or not there have been Features called.
			add_action( 'wp_footer', array( $this, 'maybe_enqueue_styles' ) );
			add_filter( 'woothemes_features_item_template', array( $this, 'add_feature_icon_placeholder' ), 10, 2 );
			add_filter( 'woothemes_features_template', array( $this, 'override_feature_icon_placeholder' ), 10, 2 );
			add_filter( 'woothemes_features_html', array( $this, 'maybe_remove_override_filter' ) );
		}
	} // End __construct()

	/**
	 * Main Icons_For_Features Instance
	 *
	 * Ensures only one instance of Icons_For_Features is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Icons_For_Features()
	 * @return Main Icons_For_Features instance
	 */
	public static function instance () {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
        }
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'icons-for-features', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // End load_plugin_textdomain()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __wakeup()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		// Log the version number.
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number()

	/**
	 * Force has_post_thumbnail() to return false, to "skip over" images where there is an icon for the feature.
	 * This caters for older versions of the Features by WooThemes plugin, where there are a few useful filters that are missing.
	 * @access public
	 * @since  1.0.0
	 * @param  boolean $response  Force this to be false, somehow.
	 * @param  int $object_id The current object ID.
	 * @param  string $meta_key  The specified meta key to retrieve.
	 * @param  boolean $single    Whether this is a singular instance key or not.
	 * @return boolean            Always return a boolean.
	 */
	public function override_has_post_thumbnail ( $response, $object_id, $meta_key, $single ) {
		if ( '_thumbnail_id' != $meta_key ) {
            return $response;
        }
		if ( '' != get_post_meta( intval( $object_id ), '_icon', true ) ) {
            $response = false;
        }
		return $response;
	} // End override_has_post_thumbnail()

	/**
	 * Remove the filter used to force has_post_thumbnail() to return false.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function maybe_remove_override_filter ( $html ) {
		remove_filter( 'get_post_metadata', array( $this, 'override_has_post_thumbnail' ), 10, 4 );
		return $html;
	} // End maybe_remove_override_filter()

	/**
	 * Add an %%ICON%% placeholder to the feature template, replacing %%IMAGE%%, if it exists.
	 * If no %%IMAGE%% tag is present, the administrator doesn't want an image to display, so don't display an icon (respect their wishes).
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function add_feature_icon_placeholder ( $tpl, $args ) {
		add_filter( 'get_post_metadata', array( $this, 'override_has_post_thumbnail' ), 10, 4 );

		$result = str_replace( '%%IMAGE%%', '%%ICON%%%%IMAGE%%', $tpl );
		return $result;
	} // End add_feature_icon_placeholder()

	/**
	 * Override the %%ICON%% template tag, if an icon is available. If not, remove the template tag.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function override_feature_icon_placeholder ( $html, $post ) {
		$icon = $this->get_the_icon_html( get_the_ID() );
		$html = str_replace( '%%ICON%%', $icon, $html );
		return $html;
	} // End override_feature_icon_placeholder()

	/**
	 * Register the CSS files to be loaded for this plugin.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function register_styles () {
        $settings = get_option( $this->token . '-options', self::defaults() );
        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $location = $this->plugin_url . 'assets/lib/font-awesome/css/font-awesome' . $suffix . '.css';
        $dependency_pages = array($this->token . '-icons');
        $dependency_admin = array($this->token . '-icons');
        switch ($settings['stylesheet']) {
            case 'maxcdn':
                $location = 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css';
                break;
            case 'other':
                $location = $settings['stylesheet_location'];
                break;
            case 'none':
                $dependency_pages = array();
                break;
            case 'local': //do nothing
            default:
                break;
        }
        if('none'===$settings['stylesheet-admin']){
            $dependency_admin = array();
        }
		
		wp_register_style( $this->token . '-icons', esc_url( $location ), array(), '4.4.0', 'all' );
		wp_register_style( $this->token . '-icons-loader', esc_url( $this->plugin_url . 'assets/css/style.css' ), $dependency_pages, $this->version, 'all' );
		wp_register_style( $this->token . '-icons-admin', esc_url( $this->plugin_url . 'assets/css/admin.css' ), $dependency_admin, $this->version, 'all' );

	} // End register_styles()

	/**
	 * Conditionally load the CSS files for this plugin.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function maybe_enqueue_styles () {
		if ( ( is_singular() && 'feature' == get_post_type() ) || is_post_type_archive( 'feature' ) || did_action( 'woothemes_features_before' ) ) {
			wp_enqueue_style( $this->token . '-icons-loader' );
		}
	} // End maybe_enqueue_styles()

	/**
	 * Get the HTML for the stored icon for a given feature.
	 * @access public
	 * @since  1.0.0
	 * @param  int $post_id The feature for which to retrieve the icon.
	 * @return string       Formatted icon HTML.
	 */
	public function get_the_icon_html ( $post_id = null ) {
		if ( is_null( $post_id ) ) {
            $post_id = get_the_ID();
        }
        
		$response = '';
		$icon_color_html = '';
		$icon = get_post_meta( intval( $post_id ), '_icon', true );
		$url = get_post_meta( intval( $post_id ), '_url', true );
		$title = get_the_title( intval( $post_id ) );
        $icon_color = get_post_meta( intval( $post_id ), '_icon_color', true );
        
        if ( '' != $icon_color ) { 
            $icon_color_html = 'style="color: ' . esc_attr( $icon_color ) . ';"'; 
        }
		if ( '' != $icon ) {
            $settings = get_option( $this->token . '-options', self::defaults() );
            $prefix = esc_attr( isset($settings['prefix']) ? $settings['prefix'] : 'fa');
			if ( '' != $url ) {
				$response = '<a title="' . esc_attr ( $title ) . '" href="' . esc_url( $url ) . '"><div class="icon-preview ' . $prefix. ' ' . esc_attr( $icon ) . '" ' . $icon_color_html . '></div></a>' . "\n";
			} else {
                $response = '<div class="icon-preview ' . $prefix. ' ' . esc_attr( $icon ) . '" ' . $icon_color_html . '></div>' . "\n";
			}
		}
		return (string)apply_filters( 'icons_for_features_get_the_icon_html', $response );
	} // End get_the_icon_html()

	/**
	 * Transform a given icon key into a human-readable label.
	 * @access public
	 * @since  1.0.0
	 * @param  string $key Given icon key.
	 * @return string      Formatted icon label.
     * @todo Remove/deprecate this as it seems unused
	 */
	public function get_icon_label ( $key ) {
		$label = str_replace( '-', ' ', str_replace( 'fa-', '', $key ) );

		if ( ' o' == substr( $label, -2 ) ) {
			$label = substr( $label, 0, ( strlen( $label ) -2 ) );
		}

		return ucwords( $label );
	} // End get_icon_label()

	/**
	 * Returns a filterable list of supported icon keys.
	 * @access public
	 * @since  1.0.0
	 * @return array Supported icon keys.
     * @deprecated since version 2.0.0
	 */
	public function get_supported_icon_list () {
		return array();
	} // End get_supported_icon_list()
    
    /**
	 * Returns default plugin settings.
	 * @access public
	 * @since  2.0.0
	 * @return array Default settings for this plugin.
	 */
    public static function defaults () {
        return array(
          'stylesheet' => 'local',
          'stylesheet-admin' => 'same',
          'prefix' => 'fa'
        );
    }
} // End Class
