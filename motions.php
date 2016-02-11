<?php
/*
 Plugin Name: Motions
 Plugin URI: http://wordpress.org/extend/plugins/motions/
 Description: Antragsgrün
 Author: Tobias HÖßl
 Version: 0.1.0
 Author URI: https://www.hoessl.eu/
 */

defined( 'YII_DEBUG' ) or define( 'YII_DEBUG', true );
defined( 'YII_ENV' ) or define( 'YII_ENV', 'wordpress' );

require( __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' );
require( __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR .
         'yiisoft' . DIRECTORY_SEPARATOR . 'yii2' . DIRECTORY_SEPARATOR . 'Yii.php' );

require( __DIR__ . '/components/wordpress/Application.php' );

$config = require( __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'web.php' );

$app = new app\components\wordpress\Application( $config );

add_action( 'widgets_init', function () {
	register_widget( \app\components\wordpress\Sidebar::class );
} );

add_action( 'template_redirect', function () {
	global $wp_query;

	// Reset the post
	antragsgruen_theme_compat_reset_post( array(
		'ID'          => 0,
		'is_404'      => true,
		'post_status' => 'publish',
	) );

	status_header( 200 );
	$wp_query->is_page     = true;
	$wp_query->is_singular = true;
	$wp_query->is_404      = false;
} , 10);

if ( ! is_admin() ) {
	$app->run();


	add_filter( 'the_content', function ( $content ) {
		$wpdata = \app\components\wordpress\WordpressLayoutData::getInstance();

		return $wpdata->content;
	}, 10, 1 );

	add_action( 'init', function () {
		//add_rewrite_rule( 'motions/(.*)?', 'index.php?motions=$matches[1]', 'top' );
		//flush_rewrite_rules( true );

	} );

}




/**
 * Populate various WordPress globals with dummy data to prevent errors.
 *
 * This dummy data is necessary because theme compatibility essentially fakes
 * WordPress into thinking that there is content where, in fact, there is none
 * (at least, no WordPress post content). By providing dummy data, we ensure
 * that template functions - things like is_page() - don't throw errors.
 *
 * @since 1.7.0
 *
 * @global WP_Query $wp_query WordPress database access object.
 * @global object $post Current post object.
 *
 * @param array $args Array of optional arguments. Arguments parallel the properties
 *                    of {@link WP_Post}; see that class for more details.
 */
function antragsgruen_theme_compat_reset_post( $args = array() ) {
	global $wp_query, $post;

	// Switch defaults if post is set
	if ( isset( $wp_query->post ) ) {
		$dummy = wp_parse_args( $args, array(
			'ID'                    => $wp_query->post->ID,
			'post_status'           => $wp_query->post->post_status,
			'post_author'           => $wp_query->post->post_author,
			'post_parent'           => $wp_query->post->post_parent,
			'post_type'             => $wp_query->post->post_type,
			'post_date'             => $wp_query->post->post_date,
			'post_date_gmt'         => $wp_query->post->post_date_gmt,
			'post_modified'         => $wp_query->post->post_modified,
			'post_modified_gmt'     => $wp_query->post->post_modified_gmt,
			'post_content'          => $wp_query->post->post_content,
			'post_title'            => $wp_query->post->post_title,
			'post_excerpt'          => $wp_query->post->post_excerpt,
			'post_content_filtered' => $wp_query->post->post_content_filtered,
			'post_mime_type'        => $wp_query->post->post_mime_type,
			'post_password'         => $wp_query->post->post_password,
			'post_name'             => $wp_query->post->post_name,
			'guid'                  => $wp_query->post->guid,
			'menu_order'            => $wp_query->post->menu_order,
			'pinged'                => $wp_query->post->pinged,
			'to_ping'               => $wp_query->post->to_ping,
			'ping_status'           => $wp_query->post->ping_status,
			'comment_status'        => $wp_query->post->comment_status,
			'comment_count'         => $wp_query->post->comment_count,
			'filter'                => $wp_query->post->filter,

			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false,
		) );
	} else {
		$dummy = wp_parse_args( $args, array(
			'ID'                    => -9999,
			'post_status'           => 'public',
			'post_author'           => 0,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => 0,
			'post_date_gmt'         => 0,
			'post_modified'         => 0,
			'post_modified_gmt'     => 0,
			'post_content'          => '',
			'post_title'            => '',
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => '',
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'filter'                => 'raw',

			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false,
		) );
	}

	// Bail if dummy post is empty
	if ( empty( $dummy ) ) {
		return;
	}

	// Set the $post global
	$post = new WP_Post( (object) $dummy );

	// Copy the new post global into the main $wp_query
	$wp_query->post       = $post;
	$wp_query->posts      = array( $post );

	// Prevent comments form from appearing
	$wp_query->post_count = 1;
	$wp_query->is_404     = $dummy['is_404'];
	$wp_query->is_page    = $dummy['is_page'];
	$wp_query->is_single  = $dummy['is_single'];
	$wp_query->is_archive = $dummy['is_archive'];
	$wp_query->is_tax     = $dummy['is_tax'];

	// Clean up the dummy post
	unset( $dummy );

	/**
	 * Force the header back to 200 status if not a deliberate 404
	 *
	 * @see https://bbpress.trac.wordpress.org/ticket/1973
	 */
	if ( ! $wp_query->is_404() ) {
		status_header( 200 );
	}

	// If we are resetting a post, we are in theme compat
	//bp_set_theme_compat_active( true );
}