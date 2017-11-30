<?php
/**
 * Plugin Name: Authenticator Example Plugin to exclude posts
 * Plugin URI:  https://github.com/bueltge/Authenticator
 * Description: This plugin allows you to publish posts or pages exclude from inaccessible site for non logged in users. This is only a example to read and use the source.
 * Author:      Inpsyde GmbH
 * Version:     2014-09-01
 * Author URI:  http://inpsyde.com/
 * License:     GPLv3+
 * License URI: ./assets/license.txt
 * Textdomain:  authenticator
 */

// check for uses in WP
if ( ! function_exists( 'add_filter' ) ) {
	echo "Hi there! I'm just a part of plugin, not much I can do when called directly.";
	exit;
}

add_action( 'plugins_loaded', 'authenticator_example_add_exclude' );
function authenticator_example_add_exclude() {

	add_filter( 'authenticator_exclude_posts', 'authenticator_example_exclude_posts' );
}

function authenticator_example_exclude_posts( $titles ) {

	// here goes the post-title of the post/page you want to exclude
	$titles[] = 'Sample Page';
	
	// For more as one title use the follow php function
	array_push( $titles, 'Sample Page', 'My Title' );

	return $titles;
}