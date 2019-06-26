<?php

/**
 * Plugin Name: BuddyForms Pods
 * Plugin URI: https://themekraft.com/products/buddyforms-pods/
 * Description: Use BuddyForms with Pods
 * Version: 1.0.0
 * Author: ThemeKraft
 * Author URI: https://themekraft.com/
 * License: GPLv2 or later
 * Network: false
 * Text Domain: buddyforms
 *
 *****************************************************************************
 *
 * This script is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 ****************************************************************************
 */


function buddyforms_pods() {



	$pods = pods_api()->load_pods( array( 'fields' => false ) );

	$form_fields = array();
	foreach ( $pods as $key => $pod ) {
		echo $pod['id'];
		echo ' ';
		echo $pod['name'];
		echo '<br>';
		foreach ( $pod['fields'] as $fields_key => $field ) {
			$form_fields[$pod['name']][$fields_key] = $field['name'];
			echo $field['name'];
			echo ' ';
		}
		echo '<br> ---- <br>';
	}


	print_r($form_fields['devs']);


	$mypod = pods('devs');
	$params = array( 'fields_only' => true, 'fields' => $form_fields['devs'] );
	echo $mypod->form( $params, 'Submit' );


}



class BuddyFormsPODS
{
	/**
	 * @var string
	 */
	public  $version = '1.0.0' ;
	/**
	 * Initiate the class
	 *
	 * @package buddyforms pods
	 * @since 0.1
	 */
	public function __construct()
	{
		add_action(
			'init',
			array( $this, 'includes' ),
			4,
			1
		);
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'buddyforms_admin_js_css_enqueue', array( $this, 'buddyforms_pods_admin_js' ) );
		add_action(
			'init',
			array( $this, 'buddyforms_pods_front_js_css_enqueue' ),
			2,
			1
		);
		$this->load_constants();
	}

	/**
	 * Defines constants needed throughout the plugin.
	 *
	 * These constants can be overridden in bp-custom.php or wp-config.php.
	 *
	 * @package buddyforms_pods
	 * @since 0.1
	 */
	public function load_constants()
	{
		if ( !defined( 'BUDDYFORMS_PODS_PLUGIN_URL' ) ) {
			define( 'BUDDYFORMS_PODS_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
		}
		if ( !defined( 'BUDDYFORMS_PODS_INSTALL_PATH' ) ) {
			define( 'BUDDYFORMS_PODS_INSTALL_PATH', dirname( __FILE__ ) . '/' );
		}
		if ( !defined( 'BUDDYFORMS_PODS_INCLUDES_PATH' ) ) {
			define( 'BUDDYFORMS_PODS_INCLUDES_PATH', BUDDYFORMS_PODS_INSTALL_PATH . 'includes/' );
		}
		if ( !defined( 'BUDDYFORMS_PODS_TEMPLATE_PATH' ) ) {
			define( 'BUDDYFORMS_PODS_TEMPLATE_PATH', BUDDYFORMS_PODS_INSTALL_PATH . 'templates/' );
		}
	}

	/**
	 * Include files needed by BuddyForms
	 *
	 * @package buddyforms_pods
	 * @since 0.1
	 */
	public function includes()
	{
		require_once BUDDYFORMS_PODS_INCLUDES_PATH . 'form-elements.php';
	}

	/**
	 * Load the textdomain for the plugin
	 *
	 * @package buddyforms_pods
	 * @since 0.1
	 */
	public function load_plugin_textdomain()
	{
		load_plugin_textdomain( 'buddyforms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Enqueue the needed CSS for the admin screen
	 *
	 * @package buddyforms_pods
	 * @since 0.1
	 */
	function buddyforms_pods_admin_style( $hook_suffix )
	{
	}

	/**
	 * Enqueue the needed JS for the admin screen
	 *
	 * @package buddyforms_pods
	 * @since 0.1
	 */
	function buddyforms_pods_admin_js( $hook_suffix )
	{
		global  $post ;
		if ( isset( $post ) && $post->post_type == 'buddyforms' && isset( $_GET['action'] ) && $_GET['action'] == 'edit' || isset( $post ) && $post->post_type == 'buddyforms' && $hook_suffix == 'post-new.php' || $hook_suffix == 'buddyforms_page_bf_add_ons' || $hook_suffix == 'buddyforms_page_bf_settings' ) {
			wp_enqueue_script( 'buddyforms-pods-form-builder-js', plugins_url( 'assets/admin/js/form-builder.js', __FILE__ ), array( 'jquery' ) );
		}
	}

	/**
	 * Enqueue the needed JS for the frontend
	 *
	 * @package buddyforms_pods
	 * @since 0.1
	 */
	function buddyforms_pods_front_js_css_enqueue()
	{
		if ( is_admin() ) {
			return;
		}

		if ( !post_type_exists( 'pods-field-group' ) ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script(
				'iris',
				admin_url( 'js/iris.min.js' ),
				array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ),
				false,
				1
			);
			wp_enqueue_script(
				'wp-color-picker',
				admin_url( 'js/color-picker.min.js' ),
				array( 'iris' ),
				false,
				1
			);
			$colorpicker_l10n = array(
				'clear'         => __( 'Clear' ),
				'defaultString' => __( 'Default' ),
				'pick'          => __( 'Select Color' ),
			);
			wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );
			// dequeue wp styling
			wp_dequeue_style( array( 'colors-fresh' ) );
		}


		if ( function_exists( 'pods_form_head' ) ) {
			pods_form_head();
			global  $pods ;
			if ( isset( $pods ) ) {

				if ( function_exists( 'pods_get_url' ) ) {
					$version = pods_get_setting( 'version' );
					$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );
					wp_enqueue_script(
						'pods-pro-field-group',
						pods_get_url( "pro/assets/js/pods-pro-field-group{$min}.js" ),
						array( 'pods-field-group' ),
						$version
					);
				}

			}
		}

	}

}
$GLOBALS['BuddyFormsPODS'] = new BuddyFormsPODS();
