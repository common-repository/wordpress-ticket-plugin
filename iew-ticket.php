<?php
/*
Plugin Name: WordPress Ticket Plugin
Plugin URI: http://ifelseweb.com/wordpress-ticket-plugin/
Description: The plugin allows you to support your wordpress users from directly on your dashboard.
Author: Semih Aksu
Version: 1.0.6
Author URI: http://www.semihaksu.com/
*/

include('pagination.class.php');
/**********************
 * defining prefix
 * for admin & enduser
 **********************/
$admpf = "iew_admin_";
$usrpf = "iew_user_";
$admurl = "?page=iew-admin-tickets";
$usrurl = "?page=iew-help-desk";
load_plugin_textdomain( 'iewticket', 'wp-content/plugins/' . plugin_basename(dirname(__FILE__)), plugin_basename(dirname(__FILE__)).'/lang/' );

/*
 * !_Create_Plugin_Tables_!
 * version 1.0
 */
register_activation_hook( __FILE__,  'iew_ticket_plugin_activate' );
function iew_ticket_plugin_activate(){
	global $wpdb;
	$tickets = $wpdb->prefix."iew_tickets";
	$message = $wpdb->prefix."iew_ticket_msgs";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	if($wpdb->get_var("show tables like '{$tickets}'") != $tickets) {
		$sql = "CREATE TABLE " . $tickets . " (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`uid` int(11) NOT NULL,
			`title` varchar(255) NOT NULL,
			`pubdate` DATETIME NOT NULL,
			`uptodate` DATETIME NOT NULL,
			`status` int(2) NOT NULL,
		PRIMARY KEY id (id)
		);";
		dbDelta($sql);
	}
	
	if($wpdb->get_var("show tables like '{$message}'") != $message) {
		$sqls = "CREATE TABLE " . $message . " (
			id int(11) NOT NULL AUTO_INCREMENT,
			tid int(11) NOT NULL,
			uid int(11) NOT NULL,
			answer TEXT NOT NULL,
			pubdate DATETIME,
		PRIMARY KEY id (id)
		);";
		dbDelta($sqls);
	}
	
	if( !get_site_option('iew_msg_table') )
		add_site_option( 'iew_msg_table', $message );
	
	if( !get_site_option('iew_ticket_table') )
		add_site_option( 'iew_ticket_table', $tickets );
		
	if( !get_site_option('iew_ticket_version') )
		add_site_option( 'iew_ticket_version', '1.0' );
}


function array_searchRecursive( $needle, $haystack, $strict=false, $path=array() )
{
    if( !is_array($haystack) ) {
        return false;
    }
 
    foreach( $haystack as $key => $val ) {
        if( is_array($val) && $subPath = array_searchRecursive($needle, $val, $strict, $path) ) {
            $path = array_merge($path, array($key), $subPath);
            return $path;
        } elseif( (!$strict && $val == $needle) || ($strict && $val === $needle) ) {
            $path[] = $key;
            return $path;
        }
    }
    return false;
}

function reverse_escape($str)
{
  $search=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
  $replace=array("\\","\0","<br />","\r","\x1a","'",'"');
  return str_replace($search,$replace,$str);
}

include_once( 'iew-ticket-user.php' );
include_once( 'iew-ticket-admin.php' );
?>