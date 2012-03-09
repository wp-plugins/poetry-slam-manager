<?php
/*
Plugin Name: Poetry Slam Manager
Version: 0.1
Plugin URI: http://soyrabbit.com
Description: Designed specifically for poetry slams. This plugin provides easy data entry, scorekeeping and publishing of slam results to blog posts and pages, as well as optional Twitter updates of slam results in real time.  Easy to use - scores can be entered quickly and easily from the front end of the website by authors who are logged in.  Automatically calculates scores and time penalties - can choose from multiple slam formats, as well as allowing the users to create custom slam formats.
Author: soyrabbit
Author URI: http://soyrabbit.com	

Copyright 2011  (email: soyrabbit@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if(!class_exists('slam_manager'))
{
	include("slam-manager-class.php");
}
if(class_exists('slam_manager'))
{
	$slam_manager = new slam_manager();
	if(isset($slam_manager)){
		register_activation_hook(__FILE__,array(&$slam_manager,'install'));
		add_shortcode('slam-display-results',array(&$slam_manager,'display_results'));
		add_action( 'wp_print_styles', array(&$slam_manager, 'include_css') );
		add_action( 'wp_enqueue_scripts', array(&$slam_manager, 'register_js') );
		add_action('wp_ajax_editResults',array(&$slam_manager,'handle_ajax_edit') );
		add_action('wp_ajax_requestTotal',array(&$slam_manager,'spit_total_score') );
		add_action('wp_ajax_postNewEntry',array(&$slam_manager,'post_new_entry') );
		add_action('wp_ajax_deleteResult',array(&$slam_manager,'delete_result') );
		add_action('wp_ajax_deleteSlam',array(&$slam_manager,'delete_slam') );
		add_action('wp_ajax_makeNewSlam',array(&$slam_manager,'make_new_slam') );
add_action('wp_ajax_editSlamTitle',array(&$slam_manager,'edit_slam_title') );
register_uninstall_hook(__FILE__,'cleanup');
function cleanup()
{
	global $wpdb;
	$score_table = $wpdb->prefix . "slam_scores";
	$slam_table = $wpdb->prefix . "slams";
	$drop_scores = "DROP TABLE $score_table;";
	$drop_slams= "DROP TABLE $slam_table;";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($drop_slams);
	dbDelta($drop_scores);
}

	}
}



?>