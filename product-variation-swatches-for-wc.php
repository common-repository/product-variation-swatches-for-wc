<?php
/**
 * Plugin Name: Product Variation Swatches for WC
 * Plugin URI: https://wordpress.org/plugins/product-variation-swatches-woocommerce/
 * Description: Beautiful colors, images and buttons variation swatches for woocommerce product attributes. Requires WooCommerce 3.2+
 * Author: Evincedev
 * Version: 1.0
 * Requires PHP: 5.6
 * Requires at least: 4.8
 * WC requires at least: 4.5
 * Tested up to: 5.9
 * WC tested up to: 5.2
 * Text Domain: woocommerce-variation-swatches
 * Author URI: https://evincedev.com/
 */


defined( 'ABSPATH' ) or die( 'Keep Silent' );

if ( ! class_exists( 'Woocommerce_Variation_Swatches' ) ):

	final class Woocommerce_Variation_Swatches {

		protected $_version = '1.0';

		protected static $_instance = null;
		private $_settings_api;

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public function __construct() {

			$this->constants();
			$this->language();
			$this->includes();
			$this->hooks();
			do_action( 'woocommerce_variation_swatches_loaded', $this );
		}

		public function constants() {
			$this->define( 'EVDPL_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
			$this->define( 'EVDPL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

			$this->define( 'EVDPL_VERSION', $this->version() );

			$this->define( 'EVDPL_PLUGIN_INCLUDE_PATH', trailingslashit( plugin_dir_path( __FILE__ ) . 'includes' ) );
			$this->define( 'EVDPL_PLUGIN_TEMPLATES_PATH', trailingslashit( plugin_dir_path( __FILE__ ) . 'templates' ) );
			$this->define( 'EVDPL_PLUGIN_TEMPLATES_URI', trailingslashit( plugin_dir_url( __FILE__ ) . 'templates' ) );

			$this->define( 'EVDPL_PLUGIN_DIRNAME', dirname( plugin_basename( __FILE__ ) ) );
			$this->define( 'EVDPL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			$this->define( 'EVDPL_PLUGIN_FILE', __FILE__ );
			$this->define( 'EVDPL_IMAGES_URI', trailingslashit( plugin_dir_url( __FILE__ ) . 'images' ) );
			$this->define( 'EVDPL_ASSETS_URI', trailingslashit( plugin_dir_url( __FILE__ ) . 'assets' ) );
		}

		public function includes() {
			if ( $this->is_required_php_version() && $this->is_wc_active() ) {
				require_once $this->include_path( 'class-woocommerce-variation-swatches-cache.php' );
				require_once $this->include_path( 'class-evdpl-customizer.php' );
				require_once $this->include_path( 'class-evdpl-settings-api.php' );
				require_once $this->include_path( 'class-evdpl-term-meta.php' );
				require_once $this->include_path( 'functions.php' );
				require_once $this->include_path( 'hooks.php' );
				require_once $this->include_path( 'themes-support.php' );
				require_once $this->include_path( 'class-woocommerce-variation-swatches-export-import.php' );
			}
		}

		public function define( $name, $value, $case_insensitive = false ) {
			if ( ! defined( $name ) ) {
				define( $name, $value, $case_insensitive );
			}
		}

		public function include_path( $file ) {
			$file = ltrim( $file, '/' );

			return EVDPL_PLUGIN_INCLUDE_PATH . $file;
		}

		public function hooks() {

			add_action( 'admin_notices', array( $this, 'php_requirement_notice' ) );
			add_action( 'admin_notices', array( $this, 'wc_requirement_notice' ) );
			add_action( 'admin_notices', array( $this, 'wc_version_requirement_notice' ) );

			if ( $this->is_required_php_version() && $this->is_wc_active() ) {

				add_action( 'admin_init', array( $this, 'after_plugin_active' ) );
				// add_action( 'admin_notices', array( $this, 'feed' ) );
				add_action( 'admin_notices', array( $this, 'internal_feed' ), 30 );
				// add_action( 'init', array( $this, 'settings_api' ), 5 );
				add_action( 'init', array( $this, 'settings_api' ), 5 );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 15 );
				add_filter( 'body_class', array( $this, 'body_class' ) );
				add_filter( 'wp_ajax_evince_live_feed_close', array( $this, 'feed_close' ) );
				add_filter( 'wp_ajax_evince_deactivate_feedback', array( $this, 'deactivate_feedback' ) );

				// @TODO: Removed because pro save error. Don't uncomment
				// add_action( 'after_evdpl_product_option_terms_button', array( $this, 'add_product_attribute_dialog' ), 10, 2 );
			}
		}

		// dialog_title
		public function add_product_attribute_dialog( $tax, $taxonomy ) {

			// from /wp-admin/edit-tags.php
			?>
			<div class="evdpl-attribute-dialog hidden evdpl-attribute-dialog-for-<?php echo esc_attr( $taxonomy ) ?>" style="max-width:500px">
				<div class="form-field form-required term-name-wrap">
					<label for="tag-name-for-<?php echo esc_attr( $taxonomy ) ?>"><?php _ex( 'Name', 'term name' ); ?></label>
					<input name="tag_name" id="tag-name-for-<?php echo esc_attr( $taxonomy ) ?>" type="text" value="" size="40" aria-required="true" />
					<p><?php _e( 'The name is how it appears on your site.' ); ?></p>
				</div>
				<?php
				$fields = evdpl_taxonomy_meta_fields( $tax->attribute_type );
				EVDPL_Term_Meta::generate_form_fields( $fields, false );
				?>
			</div>
			<?php
		}

		private function deactivate_feedback_reasons() {

			$current_user = wp_get_current_user();

			return array(
				'temporary_deactivation' => array(
					'title'             => esc_html__( 'It\'s a temporary deactivation.', 'woocommerce-variation-swatches' ),
					'input_placeholder' => '',
				),

				'dont_know_about' => array(
					'title'             => esc_html__( 'I couldn\'t understand how to make it work.', 'woocommerce-variation-swatches' ),
					'input_placeholder' => '',
					'alert'             => __( 'It converts variation select box to beautiful swatches. <br> <a target="_blank" href="https://bit.ly/deactivate-dialogue">Please check live demo</a>.', 'woocommerce-variation-swatches' ),
				),

				'no_longer_needed' => array(
					'title'             => esc_html__( 'I no longer need the plugin', 'woocommerce-variation-swatches' ),
					'input_placeholder' => '',
				),

				'found_a_better_plugin' => array(
					'title'             => esc_html__( 'I found a better plugin', 'woocommerce-variation-swatches' ),
					'input_placeholder' => esc_html__( 'Please share which plugin', 'woocommerce-variation-swatches' ),
				),

				'plugin_setup_help' => array(
					'title'             => __( 'I need someone to <strong>setup this plugin.</strong>', 'woocommerce-variation-swatches' ),
					'input_placeholder' => esc_html__( 'Your email address.', 'woocommerce-variation-swatches' ),
					'input_value'       => sanitize_email( $current_user->user_email ),
					'alert'             => __( 'Please provide your email address to contact with you <br>and help you to setup and configure this plugin.', 'woocommerce-variation-swatches' ),
				),

				'need_specific_feature' => array(
					'title'             => __( 'I need <strong>specific feature</strong> that you don\'t support.', 'woocommerce-variation-swatches' ),
					'input_placeholder' => esc_html__( 'Please share with us.', 'woocommerce-variation-swatches' ),
				),

				'other' => array(
					'title'             => esc_html__( 'Other', 'woocommerce-variation-swatches' ),
					'input_placeholder' => esc_html__( 'Please share the reason', 'woocommerce-variation-swatches' ),
				)
			);
		}

		public function deactivate_feedback() {

			$deactivate_reasons = $this->deactivate_feedback_reasons();

			$plugin         = sanitize_title( $_POST['plugin'] );
			$reason_id      = sanitize_title( $_POST['reason_type'] );
			$reason_title   = $deactivate_reasons[ $reason_id ]['title'];
			$reason_text    = ( isset( $_POST['reason_text'] ) ? sanitize_text_field( $_POST['reason_text'] ) : '' );
			$plugin_version = sanitize_text_field( $_POST['version'] );

			if ( 'temporary_deactivation' === $reason_id ) {
				wp_send_json_success( true );

				return;
			}

			$theme = array(
				'is_child_theme'   => is_child_theme(),
				'parent_theme'     => $this->get_parent_theme_name(),
				'theme_name'       => $this->get_theme_name(),
				'theme_version'    => $this->get_theme_version(),
				'theme_uri'        => esc_url( wp_get_theme( get_template() )->get( 'ThemeURI' ) ),
				'theme_author'     => esc_html( wp_get_theme( get_template() )->get( 'Author' ) ),
				'theme_author_uri' => esc_url( wp_get_theme( get_template() )->get( 'AuthorURI' ) ),
			);

			$database_version = wc_get_server_database_version();
			$active_plugins   = (array) get_option( 'active_plugins', array() );
			$plugins          = array();

			if ( is_multisite() ) {
				$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
				$active_plugins            = array_merge( $active_plugins, $network_activated_plugins );
			}

			foreach ( $active_plugins as $active_plugin ) {

				if ( $active_plugin === 'product-variation-swatches-woocommerce/product-variation-swatches-woocommerce.php' ) {
					continue;
				}

				$plugins[ $active_plugin ] = get_plugin_data( WP_PLUGIN_DIR . '/' . $active_plugin, false, false );
			}

			$environment = array(
				'is_multisite'         => is_multisite(),
				'site_url'             => esc_url( get_option( 'siteurl' ) ),
				'home_url'             => esc_url( get_option( 'home' ) ),
				'php_version'          => phpversion(),
				'mysql_version'        => $database_version['number'],
				'mysql_version_string' => $database_version['string'],
				'wc_version'           => WC()->version,
				'wp_version'           => get_bloginfo( 'version' ),
				'server_info'          => isset( $_SERVER['SERVER_SOFTWARE'] ) ? wc_clean( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
			);

			$request_body = array(
				'plugin'       => $plugin,
				'version'      => $plugin_version,
				'reason_id'    => $reason_id,
				'reason_title' => $reason_title,
				'reason_text'  => $reason_text,
				'settings'     => $this->get_options(),
				'theme'        => $theme,
				'plugins'      => $plugins,
				'environment'  => $environment
			);

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$logger  = wc_get_logger();
				$context = array( 'source' => 'woocommerce-variation-swatches' );
				$logger->info( sprintf( 'Deactivate log: %s', print_r( $request_body, true ) ), $context );
			}

			wp_send_json_success();
		}

		// Use it under hook. Don't use it on top level file like: hooks.php
		public function is_pro_active() {
			return class_exists( 'Woocommerce_Variation_Swatches_Pro' );
		}

		public function body_class( $classes ) {
			$old_classes = $classes;

			if ( apply_filters( 'disable_evdpl_body_class', false ) ) {
				return $classes;
			}

			array_push( $classes, 'woocommerce-variation-swatches' );
			if ( wp_is_mobile() ) {
				array_push( $classes, 'woocommerce-variation-swatches-on-mobile' );
			}
			if ( evdpl_is_ie11() ) {
				array_push( $classes, 'woocommerce-variation-swatches-ie11' );
			}
			array_push( $classes, sprintf( 'evdpl-theme-%s', $this->get_parent_theme_dir() ) );
			array_push( $classes, sprintf( 'evdpl-theme-child-%s', $this->get_theme_dir() ) );
			array_push( $classes, sprintf( 'evdpl-style-%s', $this->get_option( 'style' ) ) );
			array_push( $classes, sprintf( 'evdpl-attr-behavior-%s', $this->get_option( 'attribute_behavior' ) ) );
			// array_push( $classes, sprintf( 'woocommerce-variation-swatches-tooltip-%s', $this->get_option( 'tooltip' ) ? 'enabled' : 'disabled' ) );
			array_push( $classes, sprintf( 'wvs%s-tooltip', $this->get_option( 'tooltip' ) ? '' : '-no' ) );
			// array_push( $classes, sprintf( 'woocommerce-variation-swatches-stylesheet-%s', $this->get_option( 'stylesheet' ) ? 'enabled' : 'disabled' ) );
			array_push( $classes, sprintf( 'wvs%s-css', $this->get_option( 'stylesheet' ) ? '' : '-no' ) );

			if ( wc_string_to_bool( $this->get_option( 'show_variation_label' ) ) ) {
				$classes[] = 'evdpl-show-label';
			}

			if ( $this->is_pro_active() ) {
				array_push( $classes, 'evdpl-pro' );
			}

			return apply_filters( 'evdpl_body_class', array_unique( $classes ), $old_classes );
		}

		public function get_wc_asset_url( $path ) {
			return apply_filters( 'woocommerce_get_asset_url', plugins_url( $path, WC_PLUGIN_FILE ), $path );
		}

		public function enqueue_scripts() {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Filter for disable loading scripts
			if ( apply_filters( 'disable_evdpl_enqueue_scripts', false ) ) {
				return;
			}

			if ( evdpl_is_ie11() ) {
				wp_enqueue_script( 'bluebird', $this->assets_uri( "/js/bluebird{$suffix}.js" ), array(), '3.5.3' );
			}

			$is_defer                  = wc_string_to_bool( $this->get_option( 'defer_load_js' ) );
			$show_variation_label      = wc_string_to_bool( $this->get_option( 'show_variation_label' ) );
			$variation_label_separator = esc_html( $this->get_option( 'variation_label_separator' ) );
			//$hide_disabled_variation = wc_string_to_bool( woocommerce_variation_swatches()->get_option( 'hide_disabled_variation' ));

			// If defer enable we want to load this script to top
			if ( $this->is_pro_active() ) {
				// Register
				wp_register_script( 'woocommerce-variation-swatches', $this->assets_uri( "/js/frontend{$suffix}.js" ), array(
					'jquery',
					'wp-util',
					'underscore',
					'wc-add-to-cart-variation'
				), $this->version(), ! $is_defer );
			} else {
				// Enqueue
				wp_enqueue_script( 'woocommerce-variation-swatches', $this->assets_uri( "/js/frontend{$suffix}.js" ), array(
					'jquery',
					'wp-util',
					'underscore',
					'wc-add-to-cart-variation'
				), $this->version(), ! $is_defer );
			}

			wp_localize_script(
				'woocommerce-variation-swatches', 'woocommerce_variation_swatches_options', apply_filters(
					'woocommerce_variation_swatches_js_options', array(
						'is_product_page'           => is_product(),
						'show_variation_label'      => $show_variation_label,
						'variation_label_separator' => $variation_label_separator,
						'evdpl_nonce'                 => wp_create_nonce( 'woocommerce_variation_swatches' ),
						//'hide_disabled_variation' => $hide_disabled_variation
					)
				)
			);

			if ( wc_string_to_bool( $this->get_option( 'stylesheet' ) ) ) {
				wp_enqueue_style( 'woocommerce-variation-swatches', $this->assets_uri( "/css/frontend{$suffix}.css" ), array(), $this->version() );
				wp_enqueue_style( 'woocommerce-variation-swatches-theme-override', $this->assets_uri( "/css/evdpl-theme-override{$suffix}.css" ), array( 'woocommerce-variation-swatches' ), $this->version() );
			}

			if ( wc_string_to_bool( $this->get_option( 'tooltip' ) ) ) {
				wp_enqueue_style( 'woocommerce-variation-swatches-tooltip', $this->assets_uri( "/css/frontend-tooltip{$suffix}.css" ), array(), $this->version() );
			}

			$this->add_inline_style();
		}

		public function add_inline_style() {

			if ( apply_filters( 'disable_evdpl_inline_style', false ) ) {
				return;
			}

			$width     = $this->get_option( 'width' );
			$height    = $this->get_option( 'height' );
			$font_size = $this->get_option( 'single_font_size' );

			ob_start();
			include_once $this->include_path( 'stylesheet.php' );
			$css = ob_get_clean();
			$css = $this->clean_css( $css );
			$css = apply_filters( 'evdpl_inline_style', $css );
			wp_add_inline_style( 'woocommerce-variation-swatches', $css );
		}

		public function clean_css( $inline_css ) {
			$inline_css = str_ireplace( array( '<style type="text/css">', '</style>' ), '', $inline_css );
			$inline_css = str_ireplace( array( "\r\n", "\r", "\n", "\t" ), '', $inline_css );
			$inline_css = preg_replace( "/\s+/", ' ', $inline_css );

			return trim( $inline_css );
		}

		public function admin_enqueue_scripts() {
			global $wp_version;
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			/*wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_style( 'wp-jquery-ui-dialog' );*/

			// Filter for disable loading scripts
			if ( apply_filters( 'disable_evdpl_admin_enqueue_scripts', false ) ) {
				return false;
			}

			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker-alpha', $this->assets_uri( "/js/wp-color-picker-alpha{$suffix}.js" ), array( 'wp-color-picker' ), '2.1.3', true );

			wp_enqueue_script( 'form-field-dependency', $this->assets_uri( "/js/form-field-dependency{$suffix}.js" ), array( 'jquery' ), $this->version(), true );
			wp_enqueue_script( 'woocommerce-variation-swatches-admin', $this->assets_uri( "/js/admin{$suffix}.js" ), array( 'jquery' ), $this->version(), true );

			if ( ! apply_filters( 'stop_evince_live_feed', false ) ) {
				wp_enqueue_style( 'evince-feed', esc_url( $this->feed_css_uri() ), array( 'dashicons' ) );
			}


			wp_enqueue_style( 'woocommerce-variation-swatches-admin', $this->assets_uri( "/css/admin{$suffix}.css" ), array(), $this->version() );

			// wp_enqueue_script( 'selectWoo' );
			// wp_enqueue_style( 'select2' );

			wp_localize_script(
				'woocommerce-variation-swatches-admin', 'EVDPLPluginObject', array(
					'media_title'   => esc_html__( 'Choose an Image', 'woocommerce-variation-swatches' ),
					'dialog_title'  => esc_html__( 'Add Attribute', 'woocommerce-variation-swatches' ),
					'dialog_save'   => esc_html__( 'Add', 'woocommerce-variation-swatches' ),
					'dialog_cancel' => esc_html__( 'Cancel', 'woocommerce-variation-swatches' ),
					'button_title'  => esc_html__( 'Use Image', 'woocommerce-variation-swatches' ),
					'add_media'     => esc_html__( 'Add Media', 'woocommerce-variation-swatches' ),
					'ajaxurl'       => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
					'nonce'         => wp_create_nonce( 'evdpl_plugin_nonce' ),
				)
			);

			// EVINCE Admin Helper
			/*wp_enqueue_script( 'evince-backbone-modal', $this->assets_uri( "/js/evince-backbone-modal{$suffix}.js" ), array(
				'jquery',
				'underscore',
				'backbone',
				'wp-util'
			), $this->version(), true );*/
			wp_enqueue_script( 'evince-admin', $this->assets_uri( "/js/evince-admin{$suffix}.js" ), array( 'evince-backbone-modal' ), $this->version(), true );

			wp_enqueue_style( 'evince-admin', $this->assets_uri( "/css/evince-admin{$suffix}.css" ), array( 'dashicons' ), $this->version() );

		}

		public function settings_api() {

			if ( ! $this->_settings_api ) {
				$this->_settings_api = new EVDPL_Settings_API();
			}

			// $this->_settings_api->delete_settings();

			return $this->_settings_api;
		}

		function is_gallery_active() {
			return class_exists( 'Woocommerce_Variation_Gallery' );
		}

		public function add_setting( $tab_id, $tab_title, $tab_sections, $active = false, $is_pro_tab = false, $is_new = false ) {
			// Example:

			// fn(tab_id, tab_title, [
			//    [
			//     'id'=>'',
			//     'title'=>'',
			//     'desc'=>'',
			//     'fields'=>[
			//        [
			//         'id'=>'',
			//         'type'=>'',
			//         'title'=>'',
			//         'desc'=>'',
			//         'default'=>'',
			//         'is_new'=>true|false,
			//         'require' => array( 'trigger_catalog_mode' => array( 'type' => '==', 'value' => 'hover' ) )
			//      ]
			//    ] // fields end
			//  ]
			//], active ? true | false)

			add_filter(
				'evdpl_settings', function ( $fields ) use ( $tab_id, $tab_title, $tab_sections, $active, $is_pro_tab, $is_new ) {
				array_push(
					$fields, array(
						'id'       => $tab_id,
						'title'    => esc_html( $tab_title ),
						'active'   => $active,
						'sections' => $tab_sections,
						'is_pro'   => $is_pro_tab,
						'is_new'   => $is_new
					)
				);

				return $fields;
			}
			);
		}

		public function get_option( $id ) {

			if ( ! $this->_settings_api ) {
				$this->settings_api();
			}

			return $this->_settings_api->get_option( $id );
		}

		public function get_options() {
			return get_option( 'woocommerce_variation_swatches' );
		}

		public function add_term_meta( $taxonomy, $post_type, $fields ) {
			return new EVDPL_Term_Meta( $taxonomy, $post_type, $fields );
		}


		public function get_pro_link( $medium = 'go-pro' ) {

			$affiliate_id = apply_filters( 'evince_affiliate_id', 0 );

			$link_args = array();

			if ( ! empty( $affiliate_id ) ) {
				$link_args['ref'] = esc_html( $affiliate_id );
			}

			$link_args = apply_filters( 'evdpl_get_pro_link_args', $link_args );

			return add_query_arg( $link_args, '' );
		}

		public function get_theme_name() {
			return wp_get_theme()->get( 'Name' );
		}

		public function get_theme_dir() {
			return strtolower( basename( get_template_directory() ) );
		}

		public function get_parent_theme_name() {
			return wp_get_theme( get_template() )->get( 'Name' );
		}

		public function get_parent_theme_dir() {
			return strtolower( basename( get_stylesheet_directory() ) );
		}

		public function get_theme_version() {
			return wp_get_theme()->get( 'Version' );
		}

		public function is_required_php_version() {
			return version_compare( PHP_VERSION, '5.6.0', '>=' );
		}

		public function php_requirement_notice() {
			if ( ! $this->is_required_php_version() ) {
				$class   = 'notice notice-error';
				$text    = esc_html__( 'Please check PHP version requirement.', 'woocommerce-variation-swatches' );
				$link    = esc_url( 'https://docs.woocommerce.com/document/server-requirements/' );
				$message = wp_kses( __( "It's required to use latest version of PHP to use <strong>Product Variation Swatches for WC</strong>.", 'woocommerce-variation-swatches' ), array( 'strong' => array() ) );

				printf( '<div class="%1$s"><p>%2$s <a target="_blank" href="%3$s">%4$s</a></p></div>', $class, $message, $link, $text );
			}
		}

		public function wc_requirement_notice() {

			if ( ! $this->is_wc_active() ) {

				$class = 'notice notice-error';

				$text    = esc_html__( 'WooCommerce', 'woocommerce-variation-swatches' );
				$link    = esc_url(
					add_query_arg(
						array(
							'tab'       => 'plugin-information',
							'plugin'    => 'woocommerce',
							'TB_iframe' => 'true',
							'width'     => '640',
							'height'    => '500',
						), admin_url( 'plugin-install.php' )
					)
				);
				$message = wp_kses( __( "<strong>Product Variation Swatches for WC</strong> is an add-on of ", 'woocommerce-variation-swatches' ), array( 'strong' => array() ) );

				printf( '<div class="%1$s"><p>%2$s <a class="thickbox open-plugin-details-modal" href="%3$s"><strong>%4$s</strong></a></p></div>', $class, $message, $link, $text );
			}
		}

		public function is_required_wc_version() {
			return version_compare( WC_VERSION, '3.2', '>' );
		}

		public function wc_version_requirement_notice() {
			if ( $this->is_wc_active() && ! $this->is_required_wc_version() ) {
				$class   = 'notice notice-error';
				$message = sprintf( esc_html__( "Currently, you are using older version of WooCommerce. It's recommended to use latest version of WooCommerce to work with %s.", 'woocommerce-variation-swatches' ), esc_html__( 'Product Variation Swatches for WC', 'woocommerce-variation-swatches' ) );
				printf( '<div class="%1$s"><p><strong>%2$s</strong></p></div>', $class, $message );
			}
		}

		public function language() {
			load_plugin_textdomain( 'woocommerce-variation-swatches', false, trailingslashit( EVDPL_PLUGIN_DIRNAME ) . 'languages' );
		}

		public function is_wc_active() {
			return class_exists( 'WooCommerce' );
		}

		public function basename() {
			return EVDPL_PLUGIN_BASENAME;
		}

		public function dirname() {
			return EVDPL_PLUGIN_DIRNAME;
		}

		public function version() {
			return esc_attr( $this->_version );
		}

		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		public function plugin_uri() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		public function images_uri( $file ) {
			$file = ltrim( $file, '/' );

			return EVDPL_IMAGES_URI . $file;
		}

		public function wp_images_uri( $file ) {
			$file = ltrim( $file, '/' );

			return esc_url( sprintf( 'https://ps.w.org/woocommerce-variation-swatches/assets/%s', $file ) );
		}

		public function assets_uri( $file ) {
			$file = ltrim( $file, '/' );

			return EVDPL_ASSETS_URI . $file;
		}

		public function template_override_dir() {
			return apply_filters( 'evdpl_override_dir', 'woocommerce-variation-swatches' );
		}

		public function template_path() {
			return apply_filters( 'evdpl_template_path', untrailingslashit( $this->plugin_path() ) . '/templates' );
		}

		public function template_uri() {
			return apply_filters( 'evdpl_template_uri', untrailingslashit( $this->plugin_uri() ) . '/templates' );
		}

		public function locate_template( $template_name, $third_party_path = false ) {

			$template_name = ltrim( $template_name, '/' );
			$template_path = $this->template_override_dir();
			$default_path  = $this->template_path();

			if ( $third_party_path && is_string( $third_party_path ) ) {
				$default_path = untrailingslashit( $third_party_path );
			}

			// Look within passed path within the theme - this is priority.
			$template = locate_template(
				array(
					trailingslashit( $template_path ) . trim( $template_name ),
					'evdpl-template-' . trim( $template_name )
				)
			);

			// Get default template/
			if ( empty( $template ) ) {
				$template = trailingslashit( $default_path ) . trim( $template_name );
			}

			// Return what we found.
			return apply_filters( 'evdpl_locate_template', $template, $template_name, $template_path );
		}

		public function get_template( $template_name, $template_args = array(), $third_party_path = false ) {

			$template_name = ltrim( $template_name, '/' );

			$located = apply_filters( 'evdpl_get_template', $this->locate_template( $template_name, $third_party_path ) );

			do_action( 'evdpl_before_get_template', $template_name, $template_args );

			extract( $template_args );

			if ( file_exists( $located ) ) {
				include $located;
			} else {
				trigger_error( sprintf( esc_html__( 'Product Variation Swatches for WC Plugin try to load "%s" but template "%s" was not found.', 'woocommerce-variation-swatches' ), $located, $template_name ), E_USER_WARNING );
			}

			do_action( 'evdpl_after_get_template', $template_name, $template_args );
		}

		public function get_theme_file_path( $file, $third_party_path = false ) {

			$file         = ltrim( $file, '/' );
			$template_dir = $this->template_override_dir();
			$default_path = $this->template_path();

			if ( $third_party_path && is_string( $third_party_path ) ) {
				$default_path = untrailingslashit( $third_party_path );
			}

			// @TODO: Use get_theme_file_path
			if ( file_exists( get_stylesheet_directory() . '/' . $template_dir . '/' . $file ) ) {
				$path = get_stylesheet_directory() . '/' . $template_dir . '/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $template_dir . '/' . $file ) ) {
				$path = get_template_directory() . '/' . $template_dir . '/' . $file;
			} else {
				$path = trailingslashit( $default_path ) . $file;
			}

			return apply_filters( 'evdpl_get_theme_file_path', $path, $file );
		}

		public function get_theme_file_uri( $file, $third_party_uri = false ) {

			$file         = ltrim( $file, '/' );
			$template_dir = $this->template_override_dir();
			$default_uri  = $this->template_uri();

			if ( $third_party_uri && is_string( $third_party_uri ) ) {
				$default_uri = untrailingslashit( $third_party_uri );
			}

			// @TODO: Use get_theme_file_uri
			if ( file_exists( get_stylesheet_directory() . '/' . $template_dir . '/' . $file ) ) {
				$uri = get_stylesheet_directory_uri() . '/' . $template_dir . '/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $template_dir . '/' . $file ) ) {
				$uri = get_template_directory_uri() . '/' . $template_dir . '/' . $file;
			} else {
				$uri = trailingslashit( $default_uri ) . $file;
			}

			return apply_filters( 'evdpl_get_theme_file_uri', $uri, $file );
		}

		public function after_plugin_active() {

			if ( isset( $_GET['evince-hide-notice'] ) && isset( $_GET['_evince_nonce'] ) && sanitize_text_field($_GET['evince-hide-notice']) == 'gallery-plugin' && wp_verify_nonce( $_GET['_evince_nonce'], 'gallery-plugin' ) ) {
				set_transient( 'evince_gallery_plugin_notice', 'yes', 2 * MONTH_IN_SECONDS );
				update_option( 'evince_gallery_plugin_notice', 'yes' );
			}


			if ( get_option( 'activate-woocommerce-variation-swatches' ) === 'yes' ) {
				delete_option( 'activate-woocommerce-variation-swatches' );
				wp_safe_redirect(
					add_query_arg(
						array(
							'page' => 'product-variation-swatches-woocommerce-settings',
						), admin_url( 'admin.php' )
					)
				);
			}
		}

		public static function plugin_activated() {
			update_option( 'activate-woocommerce-variation-swatches', 'yes' );
			update_option( 'woocommerce_show_marketplace_suggestions', 'no' );
		}

		public static function plugin_deactivated() {
			delete_option( 'activate-woocommerce-variation-swatches' );
		}

		// Feed API
		public function feed() {

			$feed_transient_id = "evince_live_feed_wvs";

			$api_url = '';

			// For Dev Mode
			if ( $feed_api_uri = apply_filters( 'evince_feed_api_uri', false ) ) {
				$api_url = $feed_api_uri;
			}

			if ( apply_filters( 'stop_evince_live_feed', false ) ) {
				return;
			}

			if ( isset( $_GET['raw_evince_live_feed'] ) ) {
				delete_transient( $feed_transient_id );
			}

			if ( false === ( $body = get_transient( $feed_transient_id ) ) ) {
				$response = wp_remote_get(
					$api_url, $args = array(
					'sslverify' => false,
					'timeout'   => 60,
					'body'      => array(
						'item' => 'woocommerce-variation-swatches',
					)
				)
				);

				if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
					$body = json_decode( wp_remote_retrieve_body( $response ), true );
					set_transient( $feed_transient_id, $body, 6 * HOUR_IN_SECONDS );

					if ( isset( $_GET['raw_evince_live_feed'] ) && isset( $body['id'] ) ) {
						delete_transient( "evince_live_feed_seen_{$body[ 'id' ]}" );
					}
				}
			}

			if ( isset( $body['id'] ) && false !== get_transient( "evince_live_feed_seen_{$body[ 'id' ]}" ) ) {
				return;
			}

			if ( isset( $body['version'] ) && ! empty( $body['version'] ) && $body['version'] != $this->version() ) {
				return;
			}

			if ( isset( $body['skip_pro'] ) && ! empty( $body['skip_pro'] ) && $this->is_pro_active() ) {
				return;
			}

			if ( isset( $body['only_pro'] ) && ! empty( $body['only_pro'] ) && ! $this->is_pro_active() ) {
				return;
			}

			if ( isset( $body['theme'] ) && ! empty( $body['theme'] ) && $body['theme'] != $this->get_parent_theme_dir() ) {
				return;
			}

			// Skip If Some Plugin Activated
			if ( isset( $body['skip_plugins'] ) && ! empty( $body['skip_plugins'] ) ) {

				$active_plugins = (array) get_option( 'active_plugins', array() );

				if ( is_multisite() ) {
					$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
					$active_plugins            = array_unique( array_merge( $active_plugins, $network_activated_plugins ) );
				}

				$skip_plugins = (array) array_unique( explode( ',', trim( $body['skip_plugins'] ) ) );

				$intersected_plugins = array_intersect( $active_plugins, $skip_plugins );
				if ( is_array( $intersected_plugins ) && ! empty( $intersected_plugins ) ) {
					return;
				}
			}

			// Must Active Some Plugins
			if ( isset( $body['only_plugins'] ) && ! empty( $body['only_plugins'] ) ) {

				$active_plugins = (array) get_option( 'active_plugins', array() );

				if ( is_multisite() ) {
					$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
					$active_plugins            = array_unique( array_merge( $active_plugins, $network_activated_plugins ) );
				}

				$only_plugins = (array) array_unique( explode( ',', trim( $body['only_plugins'] ) ) );

				$intersected_plugins = array_intersect( $active_plugins, $only_plugins );

				if ( is_array( $intersected_plugins ) && empty( $intersected_plugins ) ) {
					return;
				}
			}

			if ( isset( $body['message'] ) && ! empty( $body['message'] ) ) {
				$user    = wp_get_current_user();
				$search  = array(
					'{pro_link}',
					'{user_login}',
					'{user_email}',
					'{user_firstname}',
					'{user_lastname}',
					'{display_name}',
					'{nickname}'
				);
				$replace = array(
					esc_url( woocommerce_variation_swatches()->get_pro_link( 'product-feed' ) ),
					$user->user_login ? esc_attr($user->user_login) : 'there',
					esc_attr($user->user_email),
					$user->user_firstname ? esc_attr($user->user_firstname) : 'there',
					$user->user_lastname ? esc_attr($user->user_lastname) : 'there',
					$user->display_name ? esc_attr($user->display_name) : 'there',
					$user->nickname ? esc_attr($user->nickname) : 'there',
				);

				$message = str_ireplace( $search, $replace, $body['message'] );

				echo wp_kses_post( $message );
			}
		}

		public function internal_feed() {

			// $visible_pages = array( 'dashboard', 'edit-product', 'product', 'plugin-install', 'plugins', 'toplevel_page_product-variation-swatches-woocommerce-settings', 'themes' );
			$visible_pages = array( 'toplevel_page_product-variation-swatches-woocommerce-settings' );
			$screen        = get_current_screen();

			if ( current_user_can( 'install_plugins' ) && $screen && in_array( $screen->id, $visible_pages ) ) {

				if ( apply_filters( 'stop_evince_live_feed', false ) ) {
					return;
				}

				if ( is_plugin_active( 'woocommerce-variation-gallery/woocommerce-variation-gallery.php' ) ) {
					return;
				}

				// delete_transient( 'evince_gallery_plugin_notice');

				if ( get_option( 'evince_gallery_plugin_notice' ) == 'yes' ) {
					return;
				}

				if ( get_transient( 'evince_gallery_plugin_notice' ) == 'yes' ) {
					return;
				}
				
				$plugins     = array_keys( get_plugins() );
				$slug        = 'woocommerce-variation-gallery';
				$button_text = esc_html__( 'Install Now', 'woocommerce-variation-swatches' );
				$install_url = esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . esc_attr($slug) ), 'install-plugin_' . $slug ) );

				if ( in_array( 'woocommerce-variation-gallery/woocommerce-variation-gallery.php', $plugins ) ) {
					$button_text = esc_html( 'Activate Plugin', 'woocommerce-variation-swatches' );
					$install_url = esc_url( self_admin_url( 'plugins.php?action=activate&plugin=' . urlencode( 'woocommerce-variation-gallery/woocommerce-variation-gallery.php' ) . '&plugin_status=all&paged=1&s&_wpnonce=' . urlencode( wp_create_nonce( 'activate-plugin_woocommerce-variation-gallery/woocommerce-variation-gallery.php' ) ) ) );
				}


				$popup_url = esc_url(
					add_query_arg(
						array(
							'tab'       => 'plugin-information',
							'section'   => 'description',
							'plugin'    => esc_attr($slug),
							'TB_iframe' => 'true',
							'width'     => '950',
							'height'    => '600',
						), self_admin_url( 'plugin-install.php' )
					)
				);


				$cancel_url = esc_url(
					add_query_arg(
						array(
							'evince-hide-notice' => 'gallery-plugin',
							'_evince_nonce'      => wp_create_nonce( 'gallery-plugin' ),
						)
					)
				);


				//echo sprintf( '<div class="evince-live-feed-contents notice notice-info"><div class="feed-message-wrapper">20000+ woocommerce stores increase their sales using <a target="_blank" class="thickbox open-plugin-details-modal" href="%s"><strong>Additional Variation Images Gallery</strong></a>. Why not yours? <a class="button-primary" href="%s" rel="noopener">%s</a></div><a class="evince-live-feed-close-plain notice-dismiss" href="%s"></a></div>', $popup_url, $install_url, $button_text, $cancel_url );

			}
		}

		public function feed_css_uri() {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// For Dev Mode
			if ( $feed_css_uri = apply_filters( 'evince_feed_css_uri', false ) ) {
				return $feed_css_uri;
			}

			return $this->assets_uri( "/css/evince-admin-notice{$suffix}.css" );

		}

		public function feed_close() {
			$id = absint( $_POST['id'] );
			set_transient( "evince_live_feed_seen_{$id}", true, 1 * WEEK_IN_SECONDS );
		}
	}

	function woocommerce_variation_swatches() {
		return Woocommerce_Variation_Swatches::instance();
	}

	add_action( 'plugins_loaded', 'woocommerce_variation_swatches', 25 );
	register_activation_hook( __FILE__, array( 'Woocommerce_Variation_Swatches', 'plugin_activated' ) );
	register_deactivation_hook( __FILE__, array( 'Woocommerce_Variation_Swatches', 'plugin_deactivated' ) );
endif;