<?php
/**
 * Plugin Name: Icons For Features
 * Plugin URI: http://www.woothemes.com/products/icons-for-features/
 * Description: Hey there! Do you want to display awesome icons for each of your features? Look no further, I'm here to help!
 * Version: 1.2.0
 * Author: WooThemes
 * Author URI: http://woothemes.com/
 * Requires at least: 3.8.1
 * Tested up to: 4.6.1
 *
 * Text Domain: icons-for-features
 * Domain Path: /languages/
 *
 * @package Icons_For_Features
 * @category Core
 * @author Matty
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
		$this->version 			= '1.1.0';

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
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
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
		if ( '_thumbnail_id' != $meta_key ) return $response;
		if ( '' != get_post_meta( intval( $object_id ), '_icon', true ) ) $response = false;
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

		$tpl = str_replace( '%%IMAGE%%', '%%ICON%%%%IMAGE%%', $tpl );
		return $tpl;
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
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_style( $this->token . '-icons', esc_url( $this->plugin_url . 'assets/lib/font-awesome/css/font-awesome' . $suffix . '.css' ), array(), '4.0.3', 'all' );
		wp_register_style( $this->token . '-icons-loader', esc_url( $this->plugin_url . 'assets/css/style.css' ), array( $this->token . '-icons' ), $this->version, 'all' );
		wp_register_style( $this->token . '-icons-admin', esc_url( $this->plugin_url . 'assets/css/admin.css' ), array( $this->token . '-icons' ), $this->version, 'all' );
		wp_register_style( $this->token . '-icons-chosen', esc_url( $this->plugin_url . 'assets/lib/chosen/chosen' . $suffix . '.css' ), array( $this->token . '-icons' ), $this->version, 'all' );
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
		if ( is_null( $post_id ) ) $post_id = get_the_ID();
		$response = '';

		$icon = get_post_meta( intval( $post_id ), '_icon', true );
		$url = get_post_meta( intval( $post_id ), '_url', true );
		$title = get_the_title( intval( $post_id ) );
		$icon_color_html = '';
		$icon_color = get_post_meta( intval( $post_id ), '_icon_color', true );

		if ( '' != $icon && in_array( $icon, $this->get_supported_icon_list() ) ) {
			if ( '' != $icon_color ) { $icon_color_html = 'style="color: ' . esc_attr( $icon_color ) . ';"'; }
			if ( '' != $url ) {
				$response = '<a title="' . esc_attr ( $title ) . '" href="' . esc_url( $url ) . '"><div class="icon-preview fa ' . esc_attr( $icon ) . '" ' . $icon_color_html . '></div></a>' . "\n";
			} else {
				$response = '<div class="icon-preview fa ' . esc_attr( $icon ) . '" ' . $icon_color_html . '></div>' . "\n";
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
	 */
	public function get_icon_label ( $key ) {
		$label = $key;
		$label = str_replace( 'fa-', '', $label );
		$label = str_replace( '-', ' ', $label );

		if ( ' o' == substr( $label, -2 ) ) {
			$label = substr( $label, 0, ( strlen( $label ) -2 ) );
			$label .= ' Outline';
		}

		$label = ucwords( $label );
		return $label;
	} // End get_icon_label()

	/**
	 * Returns a filterable list of supported icon keys.
	 * @access public
	 * @since  1.0.0
	 * @return array Supported icon keys.
	 */
	public function get_supported_icon_list () {
		return (array)apply_filters( 'icons_for_features_supported_icons', array(
			'fa-500px',
			'fa-adjust',
			'fa-adn',
			'fa-align-center',
			'fa-align-justify',
			'fa-align-left',
			'fa-align-right',
			'fa-amazon',
			'fa-ambulance',
			'fa-american-sign-language-interpreting',
			'fa-anchor',
			'fa-android',
			'fa-angellist',
			'fa-angle-double-down',
			'fa-angle-double-left',
			'fa-angle-double-right',
			'fa-angle-double-up',
			'fa-angle-down',
			'fa-angle-left',
			'fa-angle-right',
			'fa-angle-up',
			'fa-apple',
			'fa-archive',
			'fa-area-chart',
			'fa-arrow-circle-down',
			'fa-arrow-circle-left',
			'fa-arrow-circle-o-down',
			'fa-arrow-circle-o-left',
			'fa-arrow-circle-o-right',
			'fa-arrow-circle-o-up',
			'fa-arrow-circle-right',
			'fa-arrow-circle-up',
			'fa-arrow-down',
			'fa-arrow-left',
			'fa-arrow-right',
			'fa-arrow-up',
			'fa-arrows-alt',
			'fa-arrows-h',
			'fa-arrows-v',
			'fa-arrows',
			'fa-asl-interpreting',
			'fa-assistive-listening-systems',
			'fa-asterisk',
			'fa-at',
			'fa-audio-description',
			'fa-automobile',
			'fa-backward',
			'fa-balance-scale',
			'fa-ban',
			'fa-bank',
			'fa-bar-chart-o',
			'fa-bar-chart',
			'fa-barcode',
			'fa-bars',
			'fa-battery-0',
			'fa-battery-1',
			'fa-battery-2',
			'fa-battery-3',
			'fa-battery-4',
			'fa-battery-empty',
			'fa-battery-full',
			'fa-battery-half',
			'fa-battery-quarter',
			'fa-battery-three-quarters',
			'fa-bed',
			'fa-beer',
			'fa-behance-square',
			'fa-behance',
			'fa-bell-o',
			'fa-bell-slash-o',
			'fa-bell-slash',
			'fa-bell',
			'fa-bicycle',
			'fa-binoculars',
			'fa-birthday-cake',
			'fa-bitbucket-square',
			'fa-bitbucket',
			'fa-bitcoin',
			'fa-black-tie',
			'fa-blind',
			'fa-bluetooth-b',
			'fa-bluetooth',
			'fa-bold',
			'fa-bolt',
			'fa-bomb',
			'fa-book',
			'fa-bookmark-o',
			'fa-bookmark',
			'fa-braille',
			'fa-briefcase',
			'fa-btc',
			'fa-bug',
			'fa-building-o',
			'fa-building',
			'fa-bullhorn',
			'fa-bullseye',
			'fa-bus',
			'fa-buysellads',
			'fa-cab',
			'fa-calculator',
			'fa-calendar-check-o',
			'fa-calendar-minus-o',
			'fa-calendar-o',
			'fa-calendar-plus-o',
			'fa-calendar-times-o',
			'fa-calendar',
			'fa-camera-retro',
			'fa-camera',
			'fa-car',
			'fa-caret-down',
			'fa-caret-left',
			'fa-caret-right',
			'fa-caret-square-o-down',
			'fa-caret-square-o-left',
			'fa-caret-square-o-right',
			'fa-caret-square-o-up',
			'fa-caret-up',
			'fa-cart-arrow-down',
			'fa-cart-plus',
			'fa-cc-amex',
			'fa-cc-diners-club',
			'fa-cc-discover',
			'fa-cc-jcb',
			'fa-cc-mastercard',
			'fa-cc-paypal',
			'fa-cc-stripe',
			'fa-cc-visa',
			'fa-cc',
			'fa-certificate',
			'fa-chain-broken',
			'fa-chain',
			'fa-check-circle-o',
			'fa-check-circle',
			'fa-check-square-o',
			'fa-check-square',
			'fa-check',
			'fa-chevron-circle-down',
			'fa-chevron-circle-left',
			'fa-chevron-circle-right',
			'fa-chevron-circle-up',
			'fa-chevron-down',
			'fa-chevron-left',
			'fa-chevron-right',
			'fa-chevron-up',
			'fa-child',
			'fa-chrome',
			'fa-circle-o-notch',
			'fa-circle-o',
			'fa-circle-thin',
			'fa-circle',
			'fa-clipboard',
			'fa-clock-o',
			'fa-clone',
			'fa-close',
			'fa-cloud-download',
			'fa-cloud-upload',
			'fa-cloud',
			'fa-cny',
			'fa-code-fork',
			'fa-code',
			'fa-codepen',
			'fa-codiepie',
			'fa-coffee',
			'fa-cog',
			'fa-cogs',
			'fa-columns',
			'fa-comment-o',
			'fa-comment',
			'fa-commenting-o',
			'fa-commenting',
			'fa-comments-o',
			'fa-comments',
			'fa-compass',
			'fa-compress',
			'fa-connectdevelop',
			'fa-contao',
			'fa-copy',
			'fa-copyright',
			'fa-creative-commons',
			'fa-credit-card-alt',
			'fa-credit-card',
			'fa-crop',
			'fa-crosshairs',
			'fa-css3',
			'fa-cube',
			'fa-cubes',
			'fa-cut',
			'fa-cutlery',
			'fa-dashboard',
			'fa-dashcube',
			'fa-database',
			'fa-deaf',
			'fa-deafness',
			'fa-dedent',
			'fa-delicious',
			'fa-desktop',
			'fa-deviantart',
			'fa-diamond',
			'fa-digg',
			'fa-dollar',
			'fa-dot-circle-o',
			'fa-download',
			'fa-dribbble',
			'fa-dropbox',
			'fa-drupal',
			'fa-edge',
			'fa-edit',
			'fa-eject',
			'fa-ellipsis-h',
			'fa-ellipsis-v',
			'fa-empire',
			'fa-envelope-o',
			'fa-envelope-square',
			'fa-envelope',
			'fa-envira',
			'fa-eraser',
			'fa-eur',
			'fa-euro',
			'fa-exchange',
			'fa-exclamation-circle',
			'fa-exclamation-triangle',
			'fa-exclamation',
			'fa-expand',
			'fa-expeditedssl',
			'fa-external-link-square',
			'fa-external-link',
			'fa-eye-slash',
			'fa-eye',
			'fa-eyedropper',
			'fa-fa',
			'fa-facebook-f',
			'fa-facebook-official',
			'fa-facebook-square',
			'fa-facebook',
			'fa-fast-backward',
			'fa-fast-forward',
			'fa-fax',
			'fa-feed',
			'fa-female',
			'fa-fighter-jet',
			'fa-file-archive-o',
			'fa-file-audio-o',
			'fa-file-code-o',
			'fa-file-excel-o',
			'fa-file-image-o',
			'fa-file-movie-o',
			'fa-file-o',
			'fa-file-pdf-o',
			'fa-file-photo-o',
			'fa-file-picture-o',
			'fa-file-powerpoint-o',
			'fa-file-sound-o',
			'fa-file-text-o',
			'fa-file-text',
			'fa-file-video-o',
			'fa-file-word-o',
			'fa-file-zip-o',
			'fa-file',
			'fa-files-o',
			'fa-film',
			'fa-filter',
			'fa-fire-extinguisher',
			'fa-fire',
			'fa-firefox',
			'fa-first-order',
			'fa-flag-checkered',
			'fa-flag-o',
			'fa-flag',
			'fa-flash',
			'fa-flask',
			'fa-flickr',
			'fa-floppy-o',
			'fa-folder-o',
			'fa-folder-open-o',
			'fa-folder-open',
			'fa-folder',
			'fa-font-awesome',
			'fa-font',
			'fa-fonticons',
			'fa-fort-awesome',
			'fa-forumbee',
			'fa-forward',
			'fa-foursquare',
			'fa-frown-o',
			'fa-futbol-o',
			'fa-gamepad',
			'fa-gavel',
			'fa-gbp',
			'fa-ge',
			'fa-gear',
			'fa-gears',
			'fa-genderless',
			'fa-get-pocket',
			'fa-gg-circle',
			'fa-gg',
			'fa-gift',
			'fa-git-square',
			'fa-git',
			'fa-github-alt',
			'fa-github-square',
			'fa-github',
			'fa-gitlab',
			'fa-gittip',
			'fa-glass',
			'fa-glide-g',
			'fa-glide',
			'fa-globe',
			'fa-google-plus-circle',
			'fa-google-plus-official',
			'fa-google-plus-square',
			'fa-google-plus',
			'fa-google-wallet',
			'fa-google',
			'fa-graduation-cap',
			'fa-gratipay',
			'fa-group',
			'fa-h-square',
			'fa-hacker-news',
			'fa-hand-grab-o',
			'fa-hand-lizard-o',
			'fa-hand-o-down',
			'fa-hand-o-left',
			'fa-hand-o-right',
			'fa-hand-o-up',
			'fa-hand-paper-o',
			'fa-hand-peace-o',
			'fa-hand-pointer-o',
			'fa-hand-rock-o',
			'fa-hand-scissors-o',
			'fa-hand-spock-o',
			'fa-hand-stop-o',
			'fa-hard-of-hearing',
			'fa-hashtag',
			'fa-hdd-o',
			'fa-header',
			'fa-headphones',
			'fa-heart-o',
			'fa-heart',
			'fa-heartbeat',
			'fa-history',
			'fa-home',
			'fa-hospital-o',
			'fa-hotel',
			'fa-hourglass-1',
			'fa-hourglass-2',
			'fa-hourglass-3',
			'fa-hourglass-end',
			'fa-hourglass-half',
			'fa-hourglass-o',
			'fa-hourglass-start',
			'fa-hourglass',
			'fa-houzz',
			'fa-html5',
			'fa-i-cursor',
			'fa-ils',
			'fa-image',
			'fa-inbox',
			'fa-indent',
			'fa-industry',
			'fa-info-circle',
			'fa-info',
			'fa-inr',
			'fa-instagram',
			'fa-institution',
			'fa-internet-explorer',
			'fa-intersex',
			'fa-ioxhost',
			'fa-italic',
			'fa-joomla',
			'fa-jpy',
			'fa-jsfiddle',
			'fa-key',
			'fa-keyboard-o',
			'fa-krw',
			'fa-language',
			'fa-laptop',
			'fa-lastfm-square',
			'fa-lastfm',
			'fa-leaf',
			'fa-leanpub',
			'fa-legal',
			'fa-lemon-o',
			'fa-level-down',
			'fa-level-up',
			'fa-life-bouy',
			'fa-life-buoy',
			'fa-life-ring',
			'fa-life-saver',
			'fa-lightbulb-o',
			'fa-line-chart',
			'fa-link',
			'fa-linkedin-square',
			'fa-linkedin',
			'fa-linux',
			'fa-list-alt',
			'fa-list-ol',
			'fa-list-ul',
			'fa-list',
			'fa-location-arrow',
			'fa-lock',
			'fa-long-arrow-down',
			'fa-long-arrow-left',
			'fa-long-arrow-right',
			'fa-long-arrow-up',
			'fa-low-vision',
			'fa-magic',
			'fa-magnet',
			'fa-mail-forward',
			'fa-mail-reply-all',
			'fa-mail-reply',
			'fa-male',
			'fa-map-marker',
			'fa-map-o',
			'fa-map-pin',
			'fa-map-signs',
			'fa-map',
			'fa-mars-double',
			'fa-mars-stroke-h',
			'fa-mars-stroke-v',
			'fa-mars-stroke',
			'fa-mars',
			'fa-maxcdn',
			'fa-meanpath',
			'fa-medium',
			'fa-medkit',
			'fa-meh-o',
			'fa-mercury',
			'fa-microphone-slash',
			'fa-microphone',
			'fa-minus-circle',
			'fa-minus-square-o',
			'fa-minus-square',
			'fa-minus',
			'fa-mixcloud',
			'fa-mobile-phone',
			'fa-mobile',
			'fa-modx',
			'fa-money',
			'fa-moon-o',
			'fa-mortar-board',
			'fa-motorcycle',
			'fa-mouse-pointer',
			'fa-music',
			'fa-navicon',
			'fa-neuter',
			'fa-newspaper-o',
			'fa-object-group',
			'fa-object-ungroup',
			'fa-odnoklassniki-square',
			'fa-odnoklassniki',
			'fa-opencart',
			'fa-openid',
			'fa-opera',
			'fa-optin-monster',
			'fa-outdent',
			'fa-pagelines',
			'fa-paint-brush',
			'fa-paper-plane-o',
			'fa-paper-plane',
			'fa-paperclip',
			'fa-paragraph',
			'fa-paste',
			'fa-pause-circle-o',
			'fa-pause-circle',
			'fa-pause',
			'fa-paw',
			'fa-paypal',
			'fa-pencil-square-o',
			'fa-pencil-square',
			'fa-pencil',
			'fa-percent',
			'fa-phone-square',
			'fa-phone',
			'fa-photo',
			'fa-picture-o',
			'fa-pie-chart',
			'fa-pied-piper-alt',
			'fa-pied-piper-pp',
			'fa-pied-piper',
			'fa-pinterest-p',
			'fa-pinterest-square',
			'fa-pinterest',
			'fa-plane',
			'fa-play-circle-o',
			'fa-play-circle',
			'fa-play',
			'fa-plug',
			'fa-plus-circle',
			'fa-plus-square-o',
			'fa-plus-square',
			'fa-plus',
			'fa-power-off',
			'fa-print',
			'fa-product-hunt',
			'fa-puzzle-piece',
			'fa-qq',
			'fa-qrcode',
			'fa-question-circle-o',
			'fa-question-circle',
			'fa-question',
			'fa-quote-left',
			'fa-quote-right',
			'fa-ra',
			'fa-random',
			'fa-rebel',
			'fa-recycle',
			'fa-reddit-alien',
			'fa-reddit-square',
			'fa-reddit',
			'fa-refresh',
			'fa-registered',
			'fa-remove',
			'fa-renren',
			'fa-reorder',
			'fa-repeat',
			'fa-reply-all',
			'fa-reply',
			'fa-resistance',
			'fa-retweet',
			'fa-rmb',
			'fa-road',
			'fa-rocket',
			'fa-rotate-left',
			'fa-rotate-right',
			'fa-rouble',
			'fa-rss-square',
			'fa-rss',
			'fa-rub',
			'fa-ruble',
			'fa-rupee',
			'fa-safari',
			'fa-save',
			'fa-scissors',
			'fa-scribd',
			'fa-search-minus',
			'fa-search-plus',
			'fa-search',
			'fa-sellsy',
			'fa-send-o',
			'fa-send',
			'fa-server',
			'fa-share-alt-square',
			'fa-share-alt',
			'fa-share-square-o',
			'fa-share-square',
			'fa-share',
			'fa-shekel',
			'fa-sheqel',
			'fa-shield',
			'fa-ship',
			'fa-shirtsinbulk',
			'fa-shopping-bag',
			'fa-shopping-basket',
			'fa-shopping-cart',
			'fa-sign-in',
			'fa-sign-language',
			'fa-sign-out',
			'fa-signal',
			'fa-signing',
			'fa-simplybuilt',
			'fa-sitemap',
			'fa-skyatlas',
			'fa-skype',
			'fa-slack',
			'fa-sliders',
			'fa-slideshare',
			'fa-smile-o',
			'fa-snapchat-ghost',
			'fa-snapchat-square',
			'fa-snapchat',
			'fa-soccer-ball-o',
			'fa-sort-alpha-asc',
			'fa-sort-alpha-desc',
			'fa-sort-amount-asc',
			'fa-sort-amount-desc',
			'fa-sort-asc',
			'fa-sort-desc',
			'fa-sort-down',
			'fa-sort-numeric-asc',
			'fa-sort-numeric-desc',
			'fa-sort-up',
			'fa-sort',
			'fa-soundcloud',
			'fa-space-shuttle',
			'fa-spinner',
			'fa-spoon',
			'fa-spotify',
			'fa-square-o',
			'fa-square',
			'fa-stack-exchange',
			'fa-stack-overflow',
			'fa-star-half-empty',
			'fa-star-half-full',
			'fa-star-half-o',
			'fa-star-half',
			'fa-star-o',
			'fa-star',
			'fa-steam-square',
			'fa-steam',
			'fa-step-backward',
			'fa-step-forward',
			'fa-stethoscope',
			'fa-sticky-note-o',
			'fa-sticky-note',
			'fa-stop-circle-o',
			'fa-stop-circle',
			'fa-stop',
			'fa-street-view',
			'fa-strikethrough',
			'fa-stumbleupon-circle',
			'fa-stumbleupon',
			'fa-subscript',
			'fa-subway',
			'fa-suitcase',
			'fa-sun-o',
			'fa-superscript',
			'fa-support',
			'fa-table',
			'fa-tablet',
			'fa-tachometer',
			'fa-tag',
			'fa-tags',
			'fa-tasks',
			'fa-taxi',
			'fa-television',
			'fa-tencent-weibo',
			'fa-terminal',
			'fa-text-height',
			'fa-text-width',
			'fa-th-large',
			'fa-th-list',
			'fa-th',
			'fa-themeisle',
			'fa-thumb-tack',
			'fa-thumbs-down',
			'fa-thumbs-o-down',
			'fa-thumbs-o-up',
			'fa-thumbs-up',
			'fa-ticket',
			'fa-times-circle-o',
			'fa-times-circle',
			'fa-times',
			'fa-tint',
			'fa-toggle-down',
			'fa-toggle-left',
			'fa-toggle-off',
			'fa-toggle-on',
			'fa-toggle-right',
			'fa-toggle-up',
			'fa-trademark',
			'fa-train',
			'fa-transgender-alt',
			'fa-transgender',
			'fa-trash-o',
			'fa-trash',
			'fa-tree',
			'fa-trello',
			'fa-tripadvisor',
			'fa-trophy',
			'fa-truck',
			'fa-try',
			'fa-tty',
			'fa-tumblr-square',
			'fa-tumblr',
			'fa-turkish-lira',
			'fa-tv',
			'fa-twitch',
			'fa-twitter-square',
			'fa-twitter',
			'fa-umbrella',
			'fa-underline',
			'fa-undo',
			'fa-universal-access',
			'fa-university',
			'fa-unlink',
			'fa-unlock-alt',
			'fa-unlock',
			'fa-unsorted',
			'fa-upload',
			'fa-usb',
			'fa-usd',
			'fa-user-md',
			'fa-user-plus',
			'fa-user-secret',
			'fa-user-times',
			'fa-user',
			'fa-users',
			'fa-venus-double',
			'fa-venus-mars',
			'fa-venus',
			'fa-viacoin',
			'fa-viadeo-square',
			'fa-viadeo',
			'fa-video-camera',
			'fa-vimeo-square',
			'fa-vimeo',
			'fa-vine',
			'fa-vk',
			'fa-volume-control-phone',
			'fa-volume-down',
			'fa-volume-off',
			'fa-volume-up',
			'fa-warning',
			'fa-wechat',
			'fa-weibo',
			'fa-weixin',
			'fa-whatsapp',
			'fa-wheelchair-alt',
			'fa-wheelchair',
			'fa-wifi',
			'fa-wikipedia-w',
			'fa-windows',
			'fa-won',
			'fa-wordpress',
			'fa-wpbeginner',
			'fa-wpforms',
			'fa-wrench',
			'fa-xing-square',
			'fa-xing',
			'fa-y-combinator-square',
			'fa-y-combinator',
			'fa-yahoo',
			'fa-yc-square',
			'fa-yc',
			'fa-yelp',
			'fa-yen',
			'fa-yoast',
			'fa-youtube-play',
			'fa-youtube-square',
			'fa-youtube',

			) );
	} // End get_supported_icon_list()
} // End Class
?>