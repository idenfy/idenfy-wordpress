<?php
/*
Plugin Name: iDenfy
Description: Enables iDenfy for Wordpress.
Version:     1.0.5
Author:      Torricelli
Author URI:  https://www.fiverr.com/torricelli
Text Domain: wp-idenfy
*/

defined( 'ABSPATH' ) or die;

define( 'WP_IDENFY_VER', '1.0.5' );
define( 'WP_IDENFY_FILE', __FILE__ );
define( 'WP_IDENFY_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_IDENFY_NONCE_BN', basename(__FILE__) );
define( 'WP_IDENFY_NONCE_KEY', 'wp_idenfy_nonce' );
define( 'WP_IDENFY_REGISTER_URL', 'https://www.idenfy.com/get-started/?source=wordpress' );
define( 'WP_IDENFY_ENDPOINT_URL', 'https://ivs.idenfy.com/api/v2/token' );
define( 'WP_IDENFY_REDIRECT_URL', 'https://ivs.idenfy.com/api/v2/redirect?authToken=%token%' );

if ( ! class_exists( 'WP_Idenfy' ) ) {
	class WP_Idenfy {
		public static function get_instance() {
			if ( self::$instance == null ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private $optsgroup_name = 'wp_idenfy_optsgroup';
		private $options_name = 'wp_idenfy_options';
		private static $instance = null;

		private function __clone() { }

		private function __wakeup() { }

		private function __construct() {
			// WP Hooks
			add_action( 'activated_plugin', array( $this, 'activation_redirect' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'admin_post_wp_idenfy_sapis', array( $this, 'save_api_settings' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_ajax_wp_idenfy_get_link', array( $this, 'ajax_get_link' ) );
			add_action( 'wp_ajax_nopriv_wp_idenfy_get_link', array( $this, 'ajax_get_link' ) );

			// Shortcode
			add_shortcode( 'IDENFY', array( $this, 'output_shortcode' ) );
		}

		public function activation_redirect( $plugin ) {
			if ( $plugin == plugin_basename( WP_IDENFY_FILE ) && $this->get_option( 'api_key' ) == '' ) {
				wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=activate' ), 302 );
				die;
			}
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
				array( $this, 'render_dashboard' )
			);
			add_submenu_page(
				'wp-idenfy',
				__( 'Settings', 'wp-idenfy' ),
				__( 'Settings', 'wp-idenfy' ),
				'manage_options',
				'wp-idenfy-settings',
				array( $this, 'render_options_page' )
			);
		}

		public function render_options_page() {
			require( __DIR__ . '/inc/admin/activate-api.php' );
		}

		public function render_dashboard() {
			if ( $this->get_option( 'api_key' ) == '' ) {
				$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
				if( $tab == 'activate' ) {
					require( __DIR__ . '/inc/admin/activate.php' );
				} elseif( $tab == 'error' ) {
					require( __DIR__ . '/inc/admin/activate-error.php' );
				} else {
					wp_redirect( admin_url( 'admin.php?page=wp-idenfy-settings' ) );
					die;
				}
			} else {
				require( __DIR__ . '/inc/admin/dashboard.php' );
			}
		}

		public function enqueue_admin_assets( $hn ) {
			if ( strpos( $hn, 'wp-idenfy' ) === false ) return;
			wp_enqueue_style( 'wp-idenfy-admin', plugins_url( 'css/admin.css', WP_IDENFY_FILE ), null, WP_IDENFY_VER, 'all' );
			wp_enqueue_script( 'wp-idenfy-admin', plugins_url( 'js/admin.js', WP_IDENFY_FILE ), array( 'jquery' ) , WP_IDENFY_VER, true );
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
				wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=error' ) );
				die;
			}

			if ( ! property_exists( $result, 'authToken' ) ) {
				wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=error' ) );
				die;
			}

			update_option( $this->options_name, array(
				'api_key' => $api_key,
				'api_secret' => $api_secret,
				'uuid' => $uuid,
				'token' => $result->authToken
			) );

			wp_redirect( admin_url( 'admin.php?page=wp-idenfy' ) );
			die;
		}

		public function enqueue_assets() {
			wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', null, WP_IDENFY_VER, 'all' );
			wp_enqueue_style( 'wp-idenfy', plugins_url( 'css/style.css', WP_IDENFY_FILE ), null, WP_IDENFY_VER, 'all' );
			wp_enqueue_script( 'wp-idenfy', plugins_url( 'js/script.js', WP_IDENFY_FILE ), array( 'jquery' ) , WP_IDENFY_VER, true );
			wp_localize_script( 'wp-idenfy', 'WPIdenfyData', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n' => array(
					'error' => __( 'Error', 'wp-idenfy' ),
					'NSError' => __( 'Network/Server error', 'wp-idenfy' )
				)
			) );
		}

		public function output_shortcode() {
			return '<a href="#" class="idenfy-button">' . __( 'Verify me', 'wp-idenfy' ) . '<i class="fa fa-circle-notch fa-spin ajax-loader"></i></a>';
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
	}
}
WP_Idenfy::get_instance();
