<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Icons_For_Features_Admin Class
 *
 * All functionality pertaining to the icons for features administration interface.
 *
 * @package WordPress
 * @subpackage Icons_For_Features
 * @category Plugin
 * @author Matty
 * @since 1.0.0
 */
class Icons_For_Features_Admin {
	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * Constructor function.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct () {
		$this->token = 'icons-for-features';

		add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
		add_action( 'save_post', array( $this, 'meta_box_save' ) );

		// Register necessary scripts and styles, to enable others to enqueue them at will as well.
		add_action( 'admin_print_styles', array( $this, 'maybe_load_styles' ) );
		add_action( 'admin_print_scripts', array( $this, 'maybe_load_scripts' ) );

		add_filter( 'manage_edit-feature_columns', array( $this, 'register_custom_column_headings' ), 20, 1 );
		add_action( 'manage_posts_custom_column', array( $this, 'register_custom_columns' ), 20, 2 );

		// add_action( 'admin_notices', array( $this, 'settings_notices' ) );
	} // End __construct()

	/**
	 * Conditionally load the admin styles if we're viewing the "feature" post type.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function maybe_load_styles () {
		if ( 'feature' == get_post_type() ) {
			wp_enqueue_style( $this->token . '-icons-admin' );
		}
	} // End maybe_load_styles()

	/**
	 * Conditionally load the admin scripts if we're viewing the "feature" post type.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function maybe_load_scripts () {
		if ( 'feature' == get_post_type() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( $this->token . '-icons-admin', esc_url( Icons_For_Features()->plugin_url . 'assets/js/admin-icon-toggle' . $suffix . '.js' ), array( 'jquery' ), Icons_For_Features()->version, true );
		}
	} // End maybe_load_scripts()

	/**
	 * Setup the meta box.
	 *
	 * @access public
	 * @since  1.1.0
	 * @return void
	 */
	public function meta_box_setup () {
		add_meta_box( 'feature-icon', __( 'Feature Icon', 'icons-for-features' ), array( $this, 'meta_box_content' ), 'feature', 'side' );
	} // End meta_box_setup()

	/**
	 * The contents of our meta box.
	 *
	 * @access public
	 * @since  1.1.0
	 * @return void
	 */
	public function meta_box_content () {
		global $post_id;
		$fields = get_post_custom( $post_id );
		$icons = Icons_For_Features()->get_supported_icon_list();

		if ( 0 >= count( $icons ) ) {
			_e( 'No icons are currently supported.', 'icons-for-features' );
			return;
		}

		$icon = 'fa-no-feature-icon';
		if ( isset( $fields['_icon'][0] ) ) {
			$icon = esc_attr( $fields['_icon'][0] );
		}

		$html = '<input type="hidden" name="woo_' . $this->token . '_noonce" id="woo_' . $this->token . '_noonce" value="' . wp_create_nonce( $this->token ) . '" />';

		$html .= '<div class="icon-preview fa ' . esc_attr( $icon ) . '"></div>';

		$html .= '<select name="icon" class="feature-icon-selector">' . "\n";
			$html .= '<option value="">' . __( 'No Icon', 'icons-for-features' ) . '</option>' . "\n";
		foreach ( $icons as $k => $v ) {
			$html .= '<option value="' . esc_attr( $v ) . '"' . selected( $icon, $v, false ) . '>' . esc_html( Icons_For_Features()->get_icon_label( $v ) ) . '</option>' . "\n";
		}
		$html .= '</select>' . "\n";

		// Make sure this variable is empty, to ensure we have an empty hidden field.
		if ( 'fa-no-featured-icon' == $icon ) $icon = '';

		$html .= '<input type="hidden" name="currently-selected-icon" class="currently-selected-icon" value="' . esc_attr( $icon ) . '" />' . "\n";

		echo $html;
	} // End meta_box_content()

	/**
	 * Save meta box fields.
	 *
	 * @access public
	 * @since  1.1.0
	 * @param int $post_id
	 * @return void
	 */
	public function meta_box_save ( $post_id ) {
		global $post, $messages;

		// Verify
		if ( ( get_post_type() != 'feature' ) || ! wp_verify_nonce( $_POST['woo_' . $this->token . '_noonce'], $this->token ) ) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		$fields = array( 'icon' );

		foreach ( $fields as $f ) {

			${$f} = strip_tags(trim($_POST[$f]));

			if ( get_post_meta( $post_id, '_' . $f ) == '' ) {
				add_post_meta( $post_id, '_' . $f, ${$f}, true );
			} elseif( ${$f} != get_post_meta( $post_id, '_' . $f, true ) ) {
				update_post_meta( $post_id, '_' . $f, ${$f} );
			} elseif ( ${$f} == '' ) {
				delete_post_meta( $post_id, '_' . $f, get_post_meta( $post_id, '_' . $f, true ) );
			}
		}
	} // End meta_box_save()

	/**
	 * Add custom columns for the "manage" screen of this post type.
	 *
	 * @access public
	 * @param string $column_name
	 * @param int $id
	 * @since  1.0.0
	 * @return void
	 */
	public function register_custom_columns ( $column_name, $id ) {
		if ( 'feature' != get_post_type() ) return;
		global $post;

		switch ( $column_name ) {

			case 'icon':
				$value = '';

				$value = Icons_For_Features()->get_the_icon_html( $id );

				echo $value;
			break;

			default:
			break;
		}
	} // End register_custom_columns()

	/**
	 * Add custom column headings for the "manage" screen of this post type.
	 *
	 * @access public
	 * @param array $defaults
	 * @since  1.0.0
	 * @return void
	 */
	public function register_custom_column_headings ( $defaults ) {
		if ( 'feature' != get_post_type() ) return;
		$new_columns = array( 'icon' => __( 'Icon', 'icons-for-features' ) );

		$last_item = '';

		if ( count( $defaults ) > 2 ) {
			$last_item = array_slice( $defaults, -1 );

			array_pop( $defaults );
		}
		$defaults = array_merge( $defaults, $new_columns );

		if ( $last_item != '' ) {
			foreach ( $last_item as $k => $v ) {
				$defaults[$k] = $v;
				break;
			}
		}

		return $defaults;
	} // End register_custom_column_headings()
} // End Class
?>