<?php

/**
 * Upgrade functions
 *
 * @package   GCE
 * @author    Phil Derksen <pderksen@gmail.com>, Nick Young <mycorpweb@gmail.com>
 * @license   GPL-2.0+
 * @copyright 2014 Phil Derksen
 */

// I put the priority to 20 here so it runs after the gce_feed CPT code and we don't get errors
add_action( 'init', 'gce_upgrade', 20 );

/**
 * Main GCE Upgrade function. Call this and branch of from here depending on what we need to do
 * 
 * @since 2.0.0
 */
function gce_upgrade() {
	
	//delete_option( 'gce_upgrade_has_run' );
	
	$version = get_option( 'gce_version' );
	
	// Check if under version 2 and run the v2 upgrade if we are
	if( version_compare( $version, '2.0.0', '<' ) && false === get_option( 'gce_upgrade_has_run' ) ) {
		gce_v2_upgrade();
	}
}

/*
 * Run the upgrade to version 2.0.0
 * 
 * @since 2.0.0
 */
function gce_v2_upgrade() {
	$old_options = get_option( 'gce_options' );
	
	foreach( $old_options as $key => $value ) {
		convert_to_cpt_posts( $value );
	}
	
	add_option( 'gce_upgrade_has_run', 1 );
}

/**
 * Converts the old database options to the new CPT layout for 2.0.0+
 * 
 * @since 2.0.0
 */
function convert_to_cpt_posts( $args ) {
	// Setup our new post
	$post = array(
			'post_name'      => $args['title'],
			'post_title'     => $args['title'],
			'post_status'    => 'publish',
			'post_type'      => 'gce_feed'
		);
	
	$post_id = wp_insert_post( $post );
	
	create_cpt_meta( $post_id, $args );
}

/**
 * Add the CPT post meta based on options set for the old feeds prior to v2
 * 
 * @since 2.0.0
 */
function create_cpt_meta( $id, $args ) {
	
	// Convert the dropdown values to the new values for "Retrieve Events From"
	switch( $args['retrieve_from'] ) {
		case 'now':
		case 'today':
			$from = 'today';
			break;
		case 'week':
			$from = 'start_week';
			break;
		case 'month-start':
			$from = 'start_month';
			break;
		case 'month-end':
			$from = 'end_month';
			break;
		case 'date':
			$from = 'custom_date';
			break;
		default: 
			$from = 'start_time';
			break;
	}
	
	// Convert the dropdown values to the new values for "Retrieve Events Until"
	switch( $args['retrieve_until'] ) {
		case 'now':
		case 'today':
			$until = 'today';
			break;
		case 'week':
			$until = 'start_week';
			break;
		case 'month-start':
			$until = 'start_month';
			break;
		case 'month-end':
			$until = 'end_month';
			break;
		case 'date':
			$until = 'custom_date';
			break;
		default: 
			$until = 'end_time';
			break;
	}
	
	// An array to hold all of our post meta ids and values so that we can loop through and add as post meta easily
	$post_meta_fields = array(
		'gce_feed_url'         => $args['url'],
		'gce_retrieve_from'    => $from,
		'gce_retrieve_until'   => $until,
		'gce_retrieve_max'     => $args['max_events'],
		'gce_date_format'      => $args['date_format'],
		'gce_time_format'      => $args['time_format'],
		'gce_timezone_offset'  => $args['timezone'],
		'gce_cache'            => $args['cache_duration'],
		'gce_multi_day_events' => ( $args['multiple_day'] == true ? 1 : 0 ),
		'gce_display_mode'     => 'grid',
		'gce_custom_from'      => $args['retrieve_from_value'],
		'gce_custom_until'     => $args['retrieve_until_value'],
		'old_gce_id'           => $args['id']
	);
	
	// Loop through each $post_meta_field and add as an entry
	foreach( $post_meta_fields as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
}
