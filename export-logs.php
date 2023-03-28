<?php
/*
Plugin Name: 	Export Logs
Plugin URI:		https://goiteens.com/
Description: 	Export Logs
Version: 		1.0.0
Author: 		Goiteens
Author URI: 	https://goiteens.com/
Text Domain: 	goiteens
Domain Path:	/languages
License: 		GPLv2 or later
License URI:	http://www.gnu.org/licenses/gpl-2.0.html

	Copyright 2022 and beyond | Alex Cherniy (email : remstroyod@gmail.com)

*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Global variables
 */
define( 'EXPORT_LOGS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );	// define the absolute plugin path for includes
define( 'EXPORT_LOGS_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); // define the plugin url for use in enqueue

if( is_admin() )
{
    include( EXPORT_LOGS_PLUGIN_PATH . 'inc/ExportLogs.php' );

    $popupVoucher = new ExportLogs();
}
