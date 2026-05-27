<?php
/*
Plugin Name: iDenfy
Description: Enables iDenfy identity verification for Wordpress.
Version:     1.0.7
Author:      www.idenfy.com
Text Domain: wp-idenfy
*/

defined( 'ABSPATH' ) or die;

define( 'WP_IDENFY_VER', '1.0.7' );
define( 'WP_IDENFY_FILE', __FILE__ );
define( 'WP_IDENFY_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_IDENFY_NONCE_BN', basename(__FILE__) );
define( 'WP_IDENFY_NONCE_KEY', 'wp_idenfy_nonce' );
define( 'WP_IDENFY_REGISTER_URL', 'https://www.idenfy.com/get-started/?source=wordpress' );
define( 'WP_IDENFY_ENDPOINT_URL', 'https://ivs.idenfy.com/api/v2/token' );
define( 'WP_IDENFY_REDIRECT_URL', 'https://ivs.idenfy.com/api/v2/redirect?authToken=%token%' );
define( 'WP_IDENFY_KYB_ENDPOINT_URL', 'https://ivs.idenfy.com/kyb/tokens/' );
define( 'WP_IDENFY_KYB_UI_URL', 'https://kyb.ui.idenfy.com/welcome?authToken=%token%' );

if ( ! class_exists( 'WP_Idenfy' ) ) {
	class WP_Idenfy {
		public static function get_instance() {
			if ( self::$instance == null ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private $optsgroup_name = 'wp_idenfy_optsgroup';
        private $options = null;
		private $options_name = 'wp_idenfy_options';
		private $customization_option_name = 'wp_idenfy_customization';
		private $customization = null;
		private $kyb_option_name = 'wp_idenfy_kyb';
		private $kyb_options = null;
		private static $instance = null;

		private function __clone() { }

		private function __wakeup() { }

		private function __construct() {
			// WP Hooks
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'admin_post_wp_idenfy_sapis', array( $this, 'save_api_settings' ) );
			add_action( 'admin_post_wp_idenfy_save_customization', array( $this, 'save_customization' ) );
			add_action( 'admin_post_wp_idenfy_save_kyb', array( $this, 'save_kyb_settings' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_ajax_wp_idenfy_get_link', array( $this, 'ajax_get_link' ) );
			add_action( 'wp_ajax_nopriv_wp_idenfy_get_link', array( $this, 'ajax_get_link' ) );
			add_action( 'wp_ajax_wp_idenfy_get_kyb_token', array( $this, 'ajax_get_kyb_token' ) );
			add_action( 'wp_ajax_nopriv_wp_idenfy_get_kyb_token', array( $this, 'ajax_get_kyb_token' ) );
			add_filter( 'submenu_file', array( $this, 'highlight_submenu' ) );

			// Shortcodes
			add_shortcode( 'IDENFY', array( $this, 'output_shortcode' ) );
			add_shortcode( 'IDENFY_KYB', array( $this, 'output_kyb_shortcode' ) );
		}

		public function register_settings() {
			register_setting( $this->optsgroup_name, $this->options_name );
		}

		public function add_admin_menu() {
			add_menu_page(
				__( 'iDenfy', 'wp-idenfy' ),
				__( 'iDenfy', 'wp-idenfy' ),
				'manage_options',
				'wp-idenfy',
				array( $this, 'render_settings' )
			);
			add_submenu_page(
				'wp-idenfy',
				__( 'Settings', 'wp-idenfy' ),
				__( 'Settings', 'wp-idenfy' ),
				'manage_options',
				'wp-idenfy',
				array( $this, 'render_settings' )
			);

			global $submenu;
			$submenu['wp-idenfy'][] = array(
				__( 'KYC', 'wp-idenfy' ),
				'manage_options',
				'admin.php?page=wp-idenfy&tab=kyc',
			);
			$submenu['wp-idenfy'][] = array(
				__( 'KYB', 'wp-idenfy' ),
				'manage_options',
				'admin.php?page=wp-idenfy&tab=kyb',
			);
			$submenu['wp-idenfy'][] = array(
				__( 'Customization', 'wp-idenfy' ),
				'manage_options',
				'admin.php?page=wp-idenfy&tab=customization',
			);
		}

		public function highlight_submenu( $submenu_file ) {
			if ( isset( $_GET['page'] ) && $_GET['page'] === 'wp-idenfy' && isset( $_GET['tab'] ) ) {
				$tab = sanitize_key( $_GET['tab'] );
				if ( in_array( $tab, array( 'kyc', 'kyb', 'customization' ), true ) ) {
					return 'admin.php?page=wp-idenfy&tab=' . $tab;
				}
			}
			return $submenu_file;
		}

		public function get_customization() {
			if ( is_null( $this->customization ) ) {
				$style_path  = WP_IDENFY_DIR_PATH . 'css/style.css';
				$default_css = file_exists( $style_path ) ? file_get_contents( $style_path ) : '';
				$defaults = array(
					'button_text'   => __( 'Verify me', 'wp-idenfy' ),
					'bg_color'      => '#445deb',
					'text_color'    => '#ffffff',
					'border_radius' => 10,
					'padding_y'     => 15,
					'padding_x'     => 20,
					'font_size'     => 14,
					'advanced_css'  => $default_css,
				);
				$saved = (array) get_option( $this->customization_option_name, array() );
				$this->customization = array_merge( $defaults, $saved );
			}
			return $this->customization;
		}

		public function save_customization() {
			if ( empty( $_POST[ WP_IDENFY_NONCE_KEY ] ) || ! wp_verify_nonce( $_POST[ WP_IDENFY_NONCE_KEY ], WP_IDENFY_NONCE_BN ) ) {
				wp_die( __( 'Invalid request', 'wp-idenfy' ) );
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Insufficient permissions', 'wp-idenfy' ) );
			}

			$saved = $this->get_customization();

			$button_text = isset( $_POST['button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['button_text'] ) ) : 'Verify me';
			if ( $button_text === '' ) $button_text = 'Verify me';

			$bg_color   = isset( $_POST['bg_color'] ) ? sanitize_hex_color( $_POST['bg_color'] ) : '';
			$bg_color   = $bg_color ? $bg_color : '#445deb';
			$text_color = isset( $_POST['text_color'] ) ? sanitize_hex_color( $_POST['text_color'] ) : '';
			$text_color = $text_color ? $text_color : '#ffffff';
			$radius     = isset( $_POST['border_radius'] ) ? min( 100, absint( $_POST['border_radius'] ) ) : 10;
			$padding_y  = isset( $_POST['padding_y'] ) ? min( 100, absint( $_POST['padding_y'] ) ) : 15;
			$padding_x  = isset( $_POST['padding_x'] ) ? min( 200, absint( $_POST['padding_x'] ) ) : 20;
			$font_size  = isset( $_POST['font_size'] ) ? min( 64, max( 8, absint( $_POST['font_size'] ) ) ) : 14;
			$advanced   = isset( $_POST['advanced_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['advanced_css'] ) ) : '';

			// Per-property reconciliation: CSS-edit wins; otherwise field-edit writes into CSS.

			// background-color (also keeps border-color in sync with bg)
			list( $bg_color, $advanced ) = $this->reconcile_color( $saved['advanced_css'], $advanced, $saved['bg_color'], $bg_color, 'background-color', array( 'border-color' ) );

			// color
			list( $text_color, $advanced ) = $this->reconcile_color( $saved['advanced_css'], $advanced, $saved['text_color'], $text_color, 'color' );

			// border-radius
			list( $radius, $advanced ) = $this->reconcile_int( $saved['advanced_css'], $advanced, $saved['border_radius'], $radius, 'border-radius', 0, 100 );

			// padding (one CSS property, two fields)
			$css_pad_saved  = $this->parse_padding( $this->extract_css_property( $saved['advanced_css'], 'padding' ) );
			$css_pad_posted = $this->parse_padding( $this->extract_css_property( $advanced, 'padding' ) );
			$css_pad_changed = ( $css_pad_saved !== $css_pad_posted );
			$field_pad_changed = ( $padding_y !== (int) $saved['padding_y'] || $padding_x !== (int) $saved['padding_x'] );
			if ( $css_pad_changed && $css_pad_posted !== null ) {
				$padding_y = min( 100, max( 0, $css_pad_posted[0] ) );
				$padding_x = min( 200, max( 0, $css_pad_posted[1] ) );
			} elseif ( $field_pad_changed ) {
				$advanced = $this->set_css_property( $advanced, 'padding', $padding_y . 'px ' . $padding_x . 'px' );
			}

			// font-size
			list( $font_size, $advanced ) = $this->reconcile_int( $saved['advanced_css'], $advanced, $saved['font_size'], $font_size, 'font-size', 8, 64 );

			update_option( $this->customization_option_name, array(
				'button_text'   => $button_text,
				'bg_color'      => $bg_color,
				'text_color'    => $text_color,
				'border_radius' => $radius,
				'padding_y'     => $padding_y,
				'padding_x'     => $padding_x,
				'font_size'     => $font_size,
				'advanced_css'  => $advanced,
			) );
			$this->customization = null;

			wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=customization&saved=1' ) );
			die;
		}

		private function reconcile_color( $saved_css, $posted_css, $saved_field, $posted_field, $css_prop, $also_sync = array() ) {
			$css_saved  = $this->normalize_hex_color( $this->extract_css_property( $saved_css, $css_prop ) );
			$css_posted = $this->normalize_hex_color( $this->extract_css_property( $posted_css, $css_prop ) );
			$css_changed   = ( $css_saved !== $css_posted );
			$field_changed = ( $posted_field !== $saved_field );

			if ( $css_changed && $css_posted ) {
				$posted_field = $css_posted;
			} elseif ( $field_changed ) {
				$posted_css = $this->set_css_property( $posted_css, $css_prop, $posted_field );
				foreach ( $also_sync as $extra_prop ) {
					$posted_css = $this->set_css_property( $posted_css, $extra_prop, $posted_field );
				}
			}
			return array( $posted_field, $posted_css );
		}

		private function reconcile_int( $saved_css, $posted_css, $saved_field, $posted_field, $css_prop, $min, $max ) {
			$css_saved  = $this->parse_px( $this->extract_css_property( $saved_css, $css_prop ) );
			$css_posted = $this->parse_px( $this->extract_css_property( $posted_css, $css_prop ) );
			$css_changed   = ( $css_saved !== $css_posted );
			$field_changed = ( $posted_field !== (int) $saved_field );

			if ( $css_changed && $css_posted !== null ) {
				$posted_field = min( $max, max( $min, $css_posted ) );
			} elseif ( $field_changed ) {
				$posted_css = $this->set_css_property( $posted_css, $css_prop, $posted_field . 'px' );
			}
			return array( $posted_field, $posted_css );
		}

		private function normalize_hex_color( $value ) {
			if ( $value === null ) return null;
			$value = trim( (string) $value );
			if ( preg_match( '/^#[0-9a-f]{6}$/i', $value ) ) return strtolower( $value );
			if ( preg_match( '/^#([0-9a-f])([0-9a-f])([0-9a-f])$/i', $value, $m ) ) {
				return strtolower( '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3] );
			}
			if ( preg_match( '/^rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i', $value, $m ) ) {
				return sprintf( '#%02x%02x%02x', intval( $m[1] ), intval( $m[2] ), intval( $m[3] ) );
			}
			return null;
		}

		private function parse_px( $value ) {
			if ( $value === null || $value === '' ) return null;
			if ( preg_match( '/^(\d+(?:\.\d+)?)/', trim( $value ), $m ) ) return intval( $m[1] );
			return null;
		}

		private function parse_padding( $value ) {
			if ( $value === null || $value === '' ) return null;
			$parts = preg_split( '/\s+/', trim( $value ) );
			if ( ! $parts ) return null;
			$y = $this->parse_px( $parts[0] );
			if ( $y === null ) return null;
			$x = ( count( $parts ) > 1 ) ? $this->parse_px( $parts[1] ) : $y;
			return array( $y, $x !== null ? $x : $y );
		}

		private function extract_css_property( $css, $prop ) {
			if ( ! preg_match( '/a\.idenfy-button[^{]*\{([\s\S]*?)\}/i', (string) $css, $rule ) ) return null;
			$pattern = '/(?:^|;)\s*' . preg_quote( $prop, '/' ) . '\s*:\s*([^;}!]+?)(?:\s*!important)?\s*(?:;|$)/i';
			if ( preg_match( $pattern, $rule[1], $m ) ) return trim( $m[1] );
			return null;
		}

		private function set_css_property( $css, $prop, $new_value ) {
			if ( ! preg_match( '/(a\.idenfy-button[^{]*\{)([\s\S]*?)(\})/i', $css, $rule, PREG_OFFSET_CAPTURE ) ) {
				return $css;
			}
			$prefix_start = $rule[1][1];
			$prefix_end   = $prefix_start + strlen( $rule[1][0] );
			$declarations = $rule[2][0];
			$suffix_start = $rule[3][1];

			$pattern = '/((?:^|;)\s*' . preg_quote( $prop, '/' ) . '\s*:\s*)([^;}]+)(\s*;?)/i';
			if ( preg_match( $pattern, $declarations ) ) {
				$declarations = preg_replace_callback( $pattern, function( $m ) use ( $new_value ) {
					$has_imp = preg_match( '/!\s*important\s*$/i', rtrim( $m[2] ) );
					return $m[1] . $new_value . ( $has_imp ? ' !important' : '' ) . $m[3];
				}, $declarations, 1 );
			} else {
				$trimmed = rtrim( $declarations );
				if ( $trimmed !== '' && substr( $trimmed, -1 ) !== ';' && substr( $trimmed, -1 ) !== '{' ) {
					$trimmed .= ';';
				}
				$declarations = $trimmed . "\n\t" . $prop . ': ' . $new_value . " !important;\n";
			}
			return substr( $css, 0, $prefix_end ) . $declarations . substr( $css, $suffix_start );
		}

		public function render_settings() {
			require( __DIR__ . '/inc/admin/activate-api.php' );
		}

		public function enqueue_admin_assets( $hn ) {
			if ( strpos( $hn, 'wp-idenfy' ) === false ) return;
			$css_path    = WP_IDENFY_DIR_PATH . 'css/admin.css';
			$js_path     = WP_IDENFY_DIR_PATH . 'js/admin.js';
			$style_path  = WP_IDENFY_DIR_PATH . 'css/style.css';

			$cm_settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );

			wp_enqueue_style( 'wp-idenfy-admin', plugins_url( 'css/admin.css', WP_IDENFY_FILE ), null, file_exists( $css_path ) ? filemtime( $css_path ) : WP_IDENFY_VER, 'all' );
			wp_enqueue_script( 'wp-idenfy-admin', plugins_url( 'js/admin.js', WP_IDENFY_FILE ), array( 'jquery' ), file_exists( $js_path ) ? filemtime( $js_path ) : WP_IDENFY_VER, true );
			wp_enqueue_style( 'wp-idenfy', plugins_url( 'css/style.css', WP_IDENFY_FILE ), null, file_exists( $style_path ) ? filemtime( $style_path ) : WP_IDENFY_VER, 'all' );

			wp_localize_script( 'wp-idenfy-admin', 'WPIdenfyAdminData', array(
				'codeEditor' => $cm_settings,
			) );
		}

		public function save_api_settings() {
			if ( empty($_POST[WP_IDENFY_NONCE_KEY]) || ! wp_verify_nonce( $_POST[WP_IDENFY_NONCE_KEY], WP_IDENFY_NONCE_BN ) ) {
				wp_die( __( 'Invalid request', 'wp-idenfy' ) );
			}

			$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';
			$api_secret = isset( $_POST['api_secret'] ) ? sanitize_text_field( $_POST['api_secret'] ) : '';

			if ( $api_key == '' || $api_secret == '' ) wp_die( __( 'Invalid request', 'wp-idenfy' ) );

			$uuid = 'wordpress-user-1';
			$result = $this->api_request( $api_key, $api_secret, $uuid );

			if ( is_wp_error( $result ) ) wp_die( $result->get_error_message() );

			if ( property_exists( $result, 'identifier' ) && $result->identifier == 'UNAUTHORIZED' ) {
				wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=settings&error=invalid_credentials' ) );
				die;
			}

			if ( ! property_exists( $result, 'authToken' ) ) {
				wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=settings&error=invalid_credentials' ) );
				die;
			}

			update_option( $this->options_name, array(
				'api_key' => $api_key,
				'api_secret' => $api_secret,
				'uuid' => $uuid,
				'token' => $result->authToken
			) );

			wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=kyc&saved=1' ) );
			die;
		}

		public function enqueue_assets() {
			wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', null, WP_IDENFY_VER, 'all' );
			wp_enqueue_style( 'wp-idenfy', plugins_url( 'css/style.css', WP_IDENFY_FILE ), null, WP_IDENFY_VER, 'all' );
			wp_add_inline_style( 'wp-idenfy', $this->build_button_css() );
			wp_enqueue_script( 'wp-idenfy', plugins_url( 'js/script.js', WP_IDENFY_FILE ), array( 'jquery' ) , WP_IDENFY_VER, true );
			wp_localize_script( 'wp-idenfy', 'WPIdenfyData', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n' => array(
					'error'   => __( 'Error', 'wp-idenfy' ),
					'NSError' => __( 'Network/Server error', 'wp-idenfy' ),
					'close'   => __( 'Close', 'wp-idenfy' ),
				)
			) );
		}

		public function build_button_css() {
			$c   = $this->get_customization();
			$css = '';
			if ( ! empty( $c['advanced_css'] ) ) {
				$css .= $c['advanced_css'] . "\n";
			}
			$css .= "a.idenfy-button, a.idenfy-button:link, a.idenfy-button:visited, a.idenfy-button:hover, a.idenfy-button:focus {";
			$css .= "background-color: " . $c['bg_color'] . " !important;";
			$css .= "border-color: " . $c['bg_color'] . " !important;";
			$css .= "color: " . $c['text_color'] . " !important;";
			$css .= "border-radius: " . absint( $c['border_radius'] ) . "px !important;";
			$css .= "padding: " . absint( $c['padding_y'] ) . "px " . absint( $c['padding_x'] ) . "px !important;";
			$css .= "font-size: " . absint( $c['font_size'] ) . "px !important;";
			$css .= "}";
			return $css;
		}

		public function output_shortcode() {
			$c = $this->get_customization();
			return '<a href="#" class="idenfy-button">' . esc_html( $c['button_text'] ) . '<i class="fa fa-circle-notch fa-spin ajax-loader"></i></a>';
		}

		public function get_kyb_options() {
			if ( is_null( $this->kyb_options ) ) {
				$defaults = array(
					'flow'                   => '',
					'theme'                  => '',
					'lifetime'               => 3600,
					'locale'                 => '',
					'questionnaire'          => '',
					'questionnaire_required' => true,
				);
				$saved = (array) get_option( $this->kyb_option_name, array() );
				$this->kyb_options = array_merge( $defaults, $saved );
			}
			return $this->kyb_options;
		}

		public function save_kyb_settings() {
			if ( empty( $_POST[ WP_IDENFY_NONCE_KEY ] ) || ! wp_verify_nonce( $_POST[ WP_IDENFY_NONCE_KEY ], WP_IDENFY_NONCE_BN ) ) {
				wp_die( __( 'Invalid request', 'wp-idenfy' ) );
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Insufficient permissions', 'wp-idenfy' ) );
			}

			$flow          = isset( $_POST['flow'] ) ? $this->sanitize_kyb_identifier( wp_unslash( $_POST['flow'] ) ) : '';
			$theme         = isset( $_POST['theme'] ) ? $this->sanitize_kyb_identifier( wp_unslash( $_POST['theme'] ) ) : '';
			$lifetime      = isset( $_POST['lifetime'] ) ? absint( $_POST['lifetime'] ) : 3600;
			$lifetime      = max( 60, min( 2592000, $lifetime ) );
			$locale        = isset( $_POST['locale'] ) ? sanitize_key( wp_unslash( $_POST['locale'] ) ) : '';
			$allowed_locs  = array( '', 'en', 'es', 'fr', 'ru', 'de', 'it', 'pl', 'lt', 'lv', 'et', 'cs', 'ro', 'hu', 'ja', 'bg', 'nl', 'pt' );
			if ( ! in_array( $locale, $allowed_locs, true ) ) {
				$locale = '';
			}
			$questionnaire          = isset( $_POST['questionnaire'] ) ? sanitize_text_field( wp_unslash( $_POST['questionnaire'] ) ) : '';
			$questionnaire_required = ! empty( $_POST['questionnaire_required'] );

			update_option( $this->kyb_option_name, array(
				'flow'                   => $flow,
				'theme'                  => $theme,
				'lifetime'               => $lifetime,
				'locale'                 => $locale,
				'questionnaire'          => $questionnaire,
				'questionnaire_required' => $questionnaire_required,
			) );
			$this->kyb_options = null;

			wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=kyb&saved=1' ) );
			die;
		}

		private function sanitize_kyb_identifier( $value ) {
			// Trust the iDenfy API to validate the actual format; here we just
			// strip whitespace and dangerous characters so any reasonable ID survives.
			return substr( trim( sanitize_text_field( (string) $value ) ), 0, 64 );
		}

		public function output_kyb_shortcode( $atts ) {
			$atts = shortcode_atts( array(
				'client_id'              => '',
				'external_ref'           => '',
				'flow'                   => '',
				'theme'                  => '',
				'locale'                 => '',
				'lifetime'               => '',
				'questionnaire'          => '',
				'questionnaire_required' => '',
				'tags'                   => '',
				'on_complete_enable'     => '',
				'sync_field'             => '',
				'hide_on_complete'       => '',
				'close_button_text'      => '',
			), $atts, 'IDENFY_KYB' );

			$data_attrs = '';
			foreach ( $atts as $k => $v ) {
				if ( $v !== '' ) {
					$data_attrs .= ' data-' . esc_attr( str_replace( '_', '-', $k ) ) . '="' . esc_attr( $v ) . '"';
				}
			}

			return '<div class="idenfy-kyb"' . $data_attrs . '>'
				. '<p class="idenfy-kyb-loading">' . esc_html__( 'Loading business verification…', 'wp-idenfy' ) . '</p>'
				. '</div>';
		}

		public function ajax_get_link() {
			$token = $this->get_token();
			if ( ! $token ) wp_send_json_error();

			wp_send_json_success( str_replace( '%token%', $token, WP_IDENFY_REDIRECT_URL ) );
		}

		private function get_option( $option_name, $default = '' ) {
			if ( is_null( $this->options ) ) $this->options = ( array ) get_option( $this->options_name, array() );
			if ( isset( $this->options[$option_name] ) ) return $this->options[$option_name];
			return $default;
		}

		private function api_request( $api_key, $api_secret, $uuid ) {
			$result = wp_remote_post( WP_IDENFY_ENDPOINT_URL, array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
					'Content-Type' => 'application/json; charset=utf-8'
				),
				'body' => json_encode( array( 'clientId' => $uuid ) )
			) );

			if ( is_wp_error( $result ) ) return $result;

			//if ( wp_remote_retrieve_response_code( $result ) != 201 ) return new WP_Error( 'error', __( 'Invalid credentials', 'wp-idenfy' ) );

			$object = @json_decode( $result['body'] );
			
			if ( ! $object ) return new WP_Error( 'error', __( 'Invalid server response', 'wp-idenfy' ) );
			//if ( ! property_exists( $object, 'authToken' ) ) return new WP_Error( 'error', __( 'Invalid server response', 'wp-idenfy' ) );

			return $object;
		}

		private function get_token() {
			$api_key = $this->get_option( 'api_key' );
			$api_secret = $this->get_option( 'api_secret' );
			if ( $api_key == '' || $api_secret == '' ) return false;

			$uuid = 'wordpress-user-' . microtime( true ) * 1000;
			$result = $this->api_request( $api_key, $api_secret, $uuid );
			if ( is_wp_error( $result ) ) return false;

			if ( property_exists( $result, 'identifier' ) && $result->identifier == 'UNAUTHORIZED' ) return false;
			if ( ! property_exists( $result, 'authToken' ) ) return false;

			return $result->authToken;
		}

		public function ajax_get_kyb_token() {
			$overrides = array(
				'client_id'              => isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '',
				'external_ref'           => isset( $_POST['external_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['external_ref'] ) ) : '',
				'flow'                   => isset( $_POST['flow'] ) ? sanitize_text_field( wp_unslash( $_POST['flow'] ) ) : '',
				'theme'                  => isset( $_POST['theme'] ) ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : '',
				'locale'                 => isset( $_POST['locale'] ) ? sanitize_text_field( wp_unslash( $_POST['locale'] ) ) : '',
				'lifetime'               => isset( $_POST['lifetime'] ) ? absint( $_POST['lifetime'] ) : 0,
				'questionnaire'          => isset( $_POST['questionnaire'] ) ? sanitize_text_field( wp_unslash( $_POST['questionnaire'] ) ) : '',
				'questionnaire_required' => isset( $_POST['questionnaire_required'] ) ? sanitize_text_field( wp_unslash( $_POST['questionnaire_required'] ) ) : '',
				'tags'                   => isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '',
			);

			$result = $this->create_kyb_token( $overrides );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}
			wp_send_json_success( array( 'token' => $result ) );
		}

		public function create_kyb_token( $overrides = array() ) {
			$api_key    = $this->get_option( 'api_key' );
			$api_secret = $this->get_option( 'api_secret' );
			if ( $api_key === '' || $api_secret === '' ) {
				return new WP_Error( 'no_credentials', __( 'iDenfy API credentials are not configured.', 'wp-idenfy' ) );
			}

			$defaults = $this->get_kyb_options();
			$lifetime = ! empty( $overrides['lifetime'] )
				? max( 60, min( 2592000, (int) $overrides['lifetime'] ) )
				: (int) $defaults['lifetime'];
			$body     = array(
				'tokenType' => 'FORM',
				'lifetime'  => $lifetime,
			);

			$client_id = ( isset( $overrides['client_id'] ) && $overrides['client_id'] !== '' )
				? $overrides['client_id']
				: 'wordpress-kyb-' . round( microtime( true ) * 1000 );
			$body['clientId'] = substr( $client_id, 0, 100 );

			if ( ! empty( $overrides['external_ref'] ) ) {
				$body['externalRef'] = substr( $overrides['external_ref'], 0, 40 );
			}

			$flow = ! empty( $overrides['flow'] )
				? $this->sanitize_kyb_identifier( $overrides['flow'] )
				: $defaults['flow'];
			if ( $flow !== '' ) {
				$body['flow'] = $flow;
			}

			$theme = ! empty( $overrides['theme'] )
				? $this->sanitize_kyb_identifier( $overrides['theme'] )
				: $defaults['theme'];
			if ( $theme !== '' ) {
				$body['theme'] = $theme;
			}

			$allowed_locs = array( 'en', 'es', 'fr', 'ru', 'de', 'it', 'pl', 'lt', 'lv', 'et', 'cs', 'ro', 'hu', 'ja', 'bg', 'nl', 'pt' );
			$locale = ! empty( $overrides['locale'] ) ? $overrides['locale'] : $defaults['locale'];
			if ( in_array( $locale, $allowed_locs, true ) ) {
				$body['locale'] = $locale;
			}

			// Questionnaire is ignored when a flow is set (the flow controls its own questionnaire).
			$questionnaire = ! empty( $overrides['questionnaire'] )
				? $overrides['questionnaire']
				: $defaults['questionnaire'];
			if ( isset( $overrides['questionnaire_required'] ) && $overrides['questionnaire_required'] !== '' ) {
				$questionnaire_required = ( $overrides['questionnaire_required'] === 'true' || $overrides['questionnaire_required'] === '1' );
			} else {
				$questionnaire_required = (bool) $defaults['questionnaire_required'];
			}
			if ( $flow === '' && $questionnaire !== '' ) {
				$body['questionnaire']         = $questionnaire;
				$body['questionnaireRequired'] = $questionnaire_required;
			}

			if ( ! empty( $overrides['tags'] ) ) {
				$tags = array_filter( array_map( 'trim', explode( ',', $overrides['tags'] ) ) );
				$tags = array_slice( $tags, 0, 5 );
				$tags = array_map( function( $t ) { return substr( $t, 0, 32 ); }, $tags );
				if ( ! empty( $tags ) ) {
					$body['tags'] = array_values( $tags );
				}
			}

			$response = wp_remote_post( WP_IDENFY_KYB_ENDPOINT_URL, array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
					'Content-Type'  => 'application/json; charset=utf-8',
					'Accept'        => 'application/json',
				),
				'body'    => json_encode( $body ),
				'timeout' => 20,
			) );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code   = wp_remote_retrieve_response_code( $response );
			$object = json_decode( wp_remote_retrieve_body( $response ) );

			if ( $code === 201 && $object && property_exists( $object, 'tokenString' ) ) {
				return $object->tokenString;
			}

			$message = __( 'Failed to create KYB session.', 'wp-idenfy' );
			if ( $object && property_exists( $object, 'identifier' ) ) {
				if ( $object->identifier === 'UNAUTHORIZED' ) {
					$message = __( 'Invalid iDenfy API credentials.', 'wp-idenfy' );
				} elseif ( $object->identifier === 'PARTNER_CONTRACT_ERROR' ) {
					$message = __( 'KYB session creation is not enabled on your iDenfy account. Contact iDenfy support to enable it.', 'wp-idenfy' );
				} elseif ( property_exists( $object, 'message' ) ) {
					$message = (string) $object->message;
				}
			}
			return new WP_Error( 'kyb_token_failed', $message );
		}
	}
}
WP_Idenfy::get_instance();
