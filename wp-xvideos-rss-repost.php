<?php 

/*
Plugin Name: WP XVideos RSS Repost
Plugin URI:
Description: This plugin allows the user to automatically post the videos from XVideos RSS Feed Channel to the wordpress blog. Works for any themes.
Version: 1.0
Author: BackRndSource 
Author URI: https://github.com/BackrndSource
License: GPL3

WP XVideos RSS Repost is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
WP XVideos RSS Repost is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with WP XVideos RSS Repost. If not, see <https://www.gnu.org/licenses/gpl.txt>
*/

set_time_limit(120);

if ( ! defined( 'GMLU_FILE' ) ) {
	define( 'GMLU_FILE', __FILE__ );
}

if ( ! defined( 'GMLU_PATH' ) ) {
	define( 'GMLU_PATH', plugin_dir_path( GMLU_FILE ) );
}

if ( ! defined( 'GMLU_BASENAME' ) ) {
    define( 'GMLU_BASENAME', plugin_basename( GMLU_FILE ) );
}

require_once( GMLU_PATH . 'inc/class-rssvideo-list-table.php' );
require_once( GMLU_PATH . 'inc/functions.php' );

function add_submenu(){
    add_submenu_page( 
        'edit.php', 
        'Xvideos RSS Posts', 
        'Xvideos RSS Posts', 
        'edit_posts', //Capability: Admin, Editor, Author and Contributor
        'rss-xvideos-posts', 
        'print_rss_search_page' 
    );
}

add_action( 'admin_menu', 'add_submenu' );

?>