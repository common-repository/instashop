<?php
/*
 * Plugin Name: SocialShop
 * Version: 2.1.0
 * Plugin URI: https://instashopapp.com/wordpress/
 * Description: Easily embed and manage your SocialShop galleries.
 * Author: Zipline
 * Author URI: https://wearezipline.com/
 * Requires at least: 4.0
 * Tested up to: 5.2.2
 *
 * Text Domain: socialshop
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Zipline
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-socialshop.php' );

// Load plugin libraries
require_once( 'includes/class-socialshop-admin-api.php' );
require_once( 'includes/class-instashop-widget.php' );
require_once( 'includes/class-socialshop-widget.php' );
require_once( 'includes/class-socialshop-settings.php' );

/**
 * Returns the main instance of SocialShop to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object SocialShop
 */
function SocialShop () {
	$instance = SocialShop::instance( __FILE__, '2.1.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->set_settings( SocialShop_Settings::instance( $instance ) );
	}

	return $instance;
}

SocialShop();
