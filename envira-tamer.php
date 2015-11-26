<?php
/*
Plugin Name: Envira Tamer
Plugin URI: http://wordpress.org/plugins/envira-tamer/
Description: Control which post types Envira Gallery meta box field shows up on
Author: Mike Selander
Version: 1.0.1
Author URI: http://www.mikeselander.com/
License: GPLv2 or later
*/

/*
 * Load the settings page if we're in the admin section
 */
if ( is_admin() ){
	require_once( 'admin/settings-page.php' );
	$settings = new EnviraTamerSettingsPage( __FILE__ );
}

/*
 * Apply our settings to Envira to restrict the post types.
 *
 * @see envira_gallery_skipped_posttypes, get_option
 */
add_filter( 'envira_gallery_skipped_posttypes', 'restrict_envira_post_types' );
function restrict_envira_post_types( $rejects ){

	$we_want_you = get_option( 'et_envira_post_types' );

	$post_types = get_post_types( array( 'public' => true ), 'names' );

	foreach ( $post_types as $post_type ) {

		if ( !in_array( $post_type, $we_want_you ) && $post_type != 'attachment' ){
			$rejects[] = $post_type;
		}

	}

	return $rejects;

}

?>