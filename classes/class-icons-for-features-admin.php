<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

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
     * Default options
     * 
     * @var array array of default options
     * @access private
     * @since 2.0.0
     */
    private $defaults;

	/**
	 * Constructor function.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct () {
		$this->token = 'icons-for-features';

		add_action( 'add_meta_boxes_feature', array( $this, 'meta_box_setup' ), 20 );
		add_action( 'save_post', array( $this, 'meta_box_save' ) );

		// Register necessary scripts and styles, to enable others to enqueue them at will as well.
		add_action( 'admin_print_styles', array( $this, 'maybe_load_styles' ) );
		add_action( 'admin_print_scripts', array( $this, 'maybe_load_scripts' ) );

		add_filter( 'manage_edit-feature_columns', array( $this, 'register_custom_column_headings' ), 20, 1 );
		add_action( 'manage_posts_custom_column', array( $this, 'register_custom_columns' ), 20, 2 );

		// Display an admin notice, if the Features by WooThemes plugin it's present or is present yet not activated.
		add_action( 'network_admin_notices', array( $this, 'maybe_display_activation_notice' ) );
		add_action( 'admin_notices', array( $this, 'maybe_display_activation_notice' ) );

		// Process the 'Dismiss' link, if valid.
		add_action( 'admin_init', array( $this, 'maybe_process_dismiss_link' ) );
        
        // Display Icons options for embedding styles
        
        add_action( 'admin_menu', array($this, 'admin_menu'));
        
        $this->defaults = call_user_func(array(str_ireplace('_Admin', '', __CLASS__), 'defaults'));
	} // End __construct()

	/**
	 * If the nonce is valid and the action is "icons-for-features-dismiss", process the dismissal.
	 * @access  public
	 * @since   1.2.1
	 * @return  void
	 */
	public function maybe_process_dismiss_link () {
		if ( isset( $_GET['action'] ) && ( 'icons-for-features-dismiss' == $_GET['action'] ) && isset( $_GET['nonce'] ) && check_admin_referer( 'icons-for-features-dismiss', 'nonce' ) ) {
			update_option( 'icons_for_features_dismiss_activation_notice', true );

			$redirect_url = remove_query_arg( 'action', remove_query_arg( 'nonce', $_SERVER['REQUEST_URI'] ) );

			wp_safe_redirect( esc_url( $redirect_url ) );
			exit;
		}
	} // End maybe_process_dismiss_link()

	/**
	 * Display an admin notice, if the Features by WooThemes plugin is present and not activated, or not present.
	 * @access  public
	 * @since   1.2.1
	 * @return  void
	 */
	public function maybe_display_activation_notice () {
		if ( $this->_is_features_plugin_activated()    ||
           ( ! current_user_can( 'manage_options' ) )  || // Don't show the message if the user isn't an administrator.
		   ( is_multisite() && ! is_super_admin() )    || // Don't show the message if on a multisite and the user isn't a super user.
           get_option( 'icons_for_features_dismiss_activation_notice', false ) ) { // Don't show the message if the user dismissed it.
            return;
        }
		$slug = 'features-by-woothemes';
		$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ), 'install-plugin_' . $slug );
		$activate_url = 'plugins.php?action=activate&plugin=' . urlencode( 'features-by-woothemes/woothemes-features.php' ) . '&plugin_status=all&paged=1&s&_wpnonce=' . urlencode( wp_create_nonce( 'activate-plugin_features-by-woothemes/woothemes-features.php' ) );
		if ( true == $this->_is_features_plugin_installed() ) {
			$text = '<a href="' . esc_url( $activate_url ) . '">' . __( 'Activate the Features by WooThemes plugin', 'icons-for-features' ) . '</a>';
		} else {
			$text = '<a href="' . esc_url( $install_url ) . '">' . __( 'Install the Features by WooThemes plugin', 'icons-for-features' ) . '</a>';
		}
		$text = sprintf( __( '%sIcons for Features%s is almost ready. %s to get started.', 'icons-for-features' ), '<strong>', '</strong>', $text );

		$dismiss_url = add_query_arg( 'action', 'icons-for-features-dismiss', add_query_arg( 'nonce', wp_create_nonce( 'icons-for-features-dismiss' ) ) );
				echo '<div class="updated fade"><p class="alignleft">' . $text . '</p><p class="alignright"><a href="' . esc_url( $dismiss_url ) . '">' . __( 'Dismiss', 'icons-for-features' ) . '</a></p><div class="clear"></div></div>' . "\n";
	} // End maybe_display_activation_notice()

	/**
	 * Check if the Features by WooThemes plugin is activated.
	 * @access  protected
	 * @since   6.0.0
	 * @return  boolean
	 */
	protected function _is_features_plugin_activated () {
		$response = false;
		$active_plugins = apply_filters( 'active_plugins', get_option('active_plugins' ) );
		if ( 0 < count( $active_plugins ) && in_array( 'features-by-woothemes/woothemes-features.php', $active_plugins ) ) {
            $response = true;
        }
		return $response;
	} // End _is_features_plugin_activated()

	/**
	 * Check if the Features by WooThemes plugin is installed.
	 * @access  protected
	 * @since   6.0.0
	 * @return  boolean
	 */
	protected function _is_features_plugin_installed () {
		$response = false;
		$plugins = get_plugins();
		if ( 0 < count( $plugins ) && in_array( 'features-by-woothemes/woothemes-features.php', array_keys( $plugins ) ) ) {
            $response = true;
        }
		return $response;
	} // End _is_features_plugin_installed()

	/**
	 * Conditionally load the admin styles if we're viewing the "feature" post type.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function maybe_load_styles () {
		if ( 'feature' == get_post_type() ) {

			wp_enqueue_style( $this->token . '-icons-admin' );

			// Add the color picker css
			wp_enqueue_style( 'wp-color-picker' );

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

			// Add the color picker scripts
			wp_enqueue_script( 'wp-color-picker' );

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
     * @todo Remove deprecated call to get_supported_icon_list
	 */
	public function meta_box_content () {
		global $post_id;
		$fields = get_post_custom( $post_id );

		$icon = '';
		if ( isset( $fields['_icon'][0] ) ) {
			$icon = esc_attr( $fields['_icon'][0] );
		}

		$icon_color = '';
		$icon_color_html = '';

		if ( isset( $fields['_icon_color'][0] ) ) {
			$icon_color = esc_attr( $fields['_icon_color'][0] );
			$icon_color_html = 'style="color: ' . $icon_color . ';"';
		}

		$html = '<input type="hidden" name="woo_' . $this->token . '_noonce" id="woo_' . $this->token . '_noonce" value="' . wp_create_nonce( $this->token ) . '" />';
        $html .= '<p><small>' . __( '(Search for icon in <a href="http://fontawesome.io/icons/">fontawesome.io/icons</a> and enter it\'s name in field below.)', 'icons-for-features' ) . '</small></p>' . "\n";
		$html .= '<input type="text" name="icon" value="'.$icon.'" class="feature-icon-selector">' . "\n"; // $icon _is_ already escaped or empty, no need for double escaping
		$html .= '<input type="hidden" name="currently-selected-icon" class="currently-selected-icon" value="' . $icon . '" />' . "\n";
		// Allow themes/plugins to disable the color picker.
		if ( apply_filters( 'icons_for_features_icon_color', true ) ) {

			$html .= '<input name="icon_color" type="text" value="' . $icon_color . '" class="feature-icon-color" data-default-color="false" />' . "\n"; // _is_ already escaped or empty, no need for double escaping

			$html .= '<input type="hidden" name="currently-selected-icon-color" class="currently-selected-icon-color" value="' .  $icon_color  . '" />' . "\n";

		}

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

		// Allow themes/plugins to disable the color picker.
		if ( apply_filters( 'icons_for_features_icon_color', true ) ) {
			$fields[] = 'icon_color';
		}

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
		if ( 'feature' != get_post_type() ) {
            return;
        }
		global $post;

		switch ( $column_name ) {

			case 'icon':
				echo Icons_For_Features()->get_the_icon_html( $id );
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
		if ( 'feature' != get_post_type() ) {
            return;
        }
		$new_columns = array( 'icon' => __( 'Icon', 'icons-for-features' ) );

		$last_item = '';

		if ( count( $defaults ) > 2 ) {
			$last_item = array_slice( $defaults, -1 );

			array_pop( $defaults );
		}
		$columns = array_merge( $defaults, $new_columns );

		if ( $last_item != '' ) {
			foreach ( $last_item as $k => $v ) {
				$columns[$k] = $v;
				break;
			}
		}

		return $columns;
	} // End register_custom_column_headings()
    
    /**
     * Add option to Features menu
     * 
     * @access public
     * @since 2.0.0
     * @return null
     * 
     */
    public function admin_menu(){
        add_submenu_page( 'edit.php?post_type=feature', 'Features Icons', 'Icons', 'edit_theme_options', 'icons-for-features-options', array( $this, 'admin_menu_cb' ) );
    }

    public function admin_menu_cb(){
        if( $_POST && check_admin_referer( 'woo-' . $this->token . '-options-nonce' ) ){
            $settings = array();
            switch( $_POST[ $this->token . '_location' ] ){
                case 'local':
                case 'maxcdn':
                case 'none':
                    $settings[ 'stylesheet' ] = $_POST[ $this->token . '_location' ];
                    break;
                case 'other':
                    $settings[ 'stylesheet' ] = 'other';
                    $settings[ 'stylesheet_location' ] = sanitize_text_field( $_POST[ $this->token . '_location-other-location' ] );
                    break;
                default :
                    $settings[ 'stylesheet' ] = 'local';
            }
            if( in_array( $_POST[$this->token.'_adminlocation'], array('same','none') ) ){
                $settings['stylesheet-admin'] = $_POST[$this->token.'_adminlocation'];
            } else {
                $settings['stylesheet-admin'] = 'same';
            }
            switch ( $_POST[ $this->token . '_prefix' ] ){
                case '':
                    $settings['prefix'] = '';
                    break;
                case 'other':
                    $settings['prefix'] = sanitize_text_field( $_POST[ $this->token . '_prefix-other' ] );
                    break;
                case 'fa':
                default:
                    $settings['prefix'] = 'fa';
                    break;
            }
            update_option( $this->token . '-options', $settings );
            print '<div class="updated"><p>Your settings have been saved!</p></div>';
        }
        $settings = get_option( $this->token . '-options', $this->defaults );
        print ' <div class="wrap">
                    <h2>'.get_admin_page_title().'</h2>
                    <p>Thank you for using Icons For Features! To view available icons, <a href="http://fortawesome.github.io/Font-Awesome/icons/" target="_blank">click here to visit the Font Awesome website</a>.</p>
                    <form action="'.admin_url( 'edit.php?post_type=feature&page=icons-for-features-options' ).'" method="post">
                        <h3>Stylesheet</h3>
                        <p>Select how you want stylesheet loaded on your site (if at all):</p>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">Load Font Awesome stylesheet everywhere except admin From:</th>
                                    <td>
                                        <fieldset>
                                            <legend class="screen-reader-text"><span>Load Font Awesome 4 From</span></legend>
                                            <label for="' . $this->token . '_location-local"><input type="radio" name="' . $this->token . '_location" id="' . $this->token . '_location-local" value="local"'.( 'local' == $settings[ 'stylesheet' ] ? ' checked' : false ).'> Local plugin folder</label>
                                            <br />
                                            <label for="' . $this->token . '_location-maxcdn"><input type="radio" name="' . $this->token . '_location" id="' . $this->token . '_location-maxcdn" value="maxcdn"'.( 'maxcdn' == $settings[ 'stylesheet' ] ? ' checked' : false ).'> Official Font Awesome CDN <span class="description">(<a href="http://www.bootstrapcdn.com/#fontawesome_tab" target="_blank">Bootstrap CDN powered by MaxCDN</a>)</span></label>
                                            <br />
                                            <label for="' . $this->token . '_location-other"><input type="radio" name="' . $this->token . '_location" id="' . $this->token . '_location-other" value="other"'.( 'other' == $settings[ 'stylesheet' ] ? ' checked' : false ).'> A custom location:</label> <input type="text" name="' . $this->token . '_location-other-location" id="' . $this->token . '_location-other-location" placeholder="Enter full url here" class="regular-text" value="'.( isset( $settings[ 'stylesheet_location' ] ) ? $settings[ 'stylesheet_location' ] : '' ).'">
                                            <br />
                                            <label for="' . $this->token . '_location-none"><input type="radio" name="' . $this->token . '_location" id="' . $this->token . '_location-none" value="none"'.( 'none' == $settings[ 'stylesheet' ] ? ' checked' : false ).'>Don&#8217;t load Font Awesome 4&#8217;s stylesheet <span class="description">(use this if you load Font Awesome 4 elsewhere on your site)</span> (default setting)</label>
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Load Font Awesome stylesheet on admin pages From:</th>
                                    <td>
                                        <fieldset>
                                            <legend class="screen-reader-text"><span>Load Font Awesome 4 From</span></legend>
                                            <label for="' . $this->token . '_adminlocation-same"><input type="radio" name="' . $this->token . '_adminlocation" id="' . $this->token . '_adminlocation-same" value="same"'.( 'same' == $settings[ 'stylesheet-admin' ] ? ' checked' : false ).'> Same setting as non-admin pages<span class="description">(if non-admin pages have "none" selected, this setting acts as "local" only for admin pages.)</span></label>
                                            <br />
                                            <label for="' . $this->token . '_adminlocation-none"><input type="radio" name="' . $this->token . '_adminlocation" id="' . $this->token . '_adminlocation-none" value="none"'.( 'none' == $settings[ 'stylesheet-admin' ] ? ' checked' : false ).'>Don&#8217;t load Font Awesome 4&#8217;s stylesheet on admin pages</label>
                                        </fieldset>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <h3>Icon Prefix</h3>
                        <p>Select how you want icons class css prefixed (if at all):</p>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">Icon css prefix:</th>
                                    <td>
                                        <fieldset>
                                            <legend class="screen-reader-text"><span>Icons css prefix</span></legend>
                                            <label for="' . $this->token . '_prefix-fa"><input type="radio" name="' . $this->token . '_prefix" id="' . $this->token . '_prefix-fa" value="fa"'.( 'fa' == $settings[ 'prefix' ] ? ' checked' : false ).'> Default Font Awesome prefix (fa) <span class="description">(default setting)</span></label>
                                            <br />
                                            <label for="' . $this->token . '_prefix-none"><input type="radio" name="' . $this->token . '_prefix" id="' . $this->token . '_prefix-none" value=""'.( '' == $settings[ 'prefix' ] ? ' checked' : false ).'> No prefix <span class="description">(you will have to enter prefix in icon field or use iconfont that does not require prefixing.)</span></label>
                                            <br />
                                            <label for="' . $this->token . '_prefix-other"><input type="radio" name="' . $this->token . '_prefix" id="' . $this->token . '_prefix-other" value="other"'.( ('' != $settings[ 'prefix' ] && 'fa' != $settings[ 'prefix' ]) ? ' checked' : false ).'> A custom prefix:</label> <input type="text" name="' . $this->token . '_prefix-other" id="' . $this->token . '_prefix-other" placeholder="Enter prefix" class="regular-text" value="'.( isset( $settings[ 'prefix' ] ) ? $settings[ 'prefix' ] : '' ).'">
                                        </fieldset>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p>'.wp_nonce_field( 'woo-' . $this->token . '-options-nonce' ).'<button type="submit" class="button button-primary">Save Settings</button></p>
                    </form>
                </div>';
    }
} // End Class
