<?php

if (
	! defined( 'WP_UNINSTALL_PLUGIN' ) ||
	! WP_UNINSTALL_PLUGIN ||
	dirname( WP_UNINSTALL_PLUGIN ) != dirname( plugin_basename( __FILE__ ) )
) {
	status_header( 404 );
	exit;
}

/**
 * Delete all transients, from posts and from Featured images.
 * Props Scott Fennell -- https://css-tricks.com/the-deal-with-wordpress-transients/
 *
 * @since 1.2.0
 */
global $wpdb;

$prefix = esc_sql( 'jeherve_post_embed_' );
$options = $wpdb->options;
$t  = esc_sql( "_transient_timeout_$prefix%" );

// Find all our transients in the database.
$sql = $wpdb->prepare (
	"
	SELECT option_name
	FROM $options
	WHERE option_name LIKE '%s'
	",
	$t
);

$transients = $wpdb->get_col( $sql );

// For each transient...
foreach( $transients as $transient ) {
	// Strip away the WordPress prefix in order to arrive at the transient key.
	$key = str_replace( '_transient_timeout_', '', $transient );

	// Now that we have the key, use WordPress core to the delete the transient.
	delete_transient( $key );
}

// But guess what?  Sometimes transients are not in the DB, so we have to do this too:
wp_cache_flush();
