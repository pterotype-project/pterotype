<?php
/*
Plugin Name: Pterotype
Plugin URI: https://getpterotype.com
Description: Pterotype expands your audience by giving your blog an ActivityPub stream, making it a part of the Fediverse.
Version: 1.4.3
Author: Jeremy Dormitzer
Author URI: https://jeremydormitzer.com
License: MIT
License URI: https://github.com/jdormit/blob/master/LICENSE
*/
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/init.php';

define( 'PTEROTYPE_VERSION', '1.4.3' );
define( 'PTEROTYPE_BLOG_ACTOR_SLUG', '-blog' );
define( 'PTEROTYPE_BLOG_ACTOR_USERNAME', 'blog' );

function pterotype_init() {
    do_action( 'pterotype_init' );
    flush_rewrite_rules();
}

function pterotype_deactivate() {
    do_action( 'pterotype_deactivate' );
    flush_rewrite_rules();
}

function pterotype_uninstall() {
    do_action( 'pterotype_uninstall' );
    flush_rewrite_rules();
}

function pterotype_load() {
    do_action( 'pterotype_load' );
}

add_action( 'setup_theme', 'pterotype_load' );
register_activation_hook( __FILE__, 'pterotype_init' );
register_deactivation_hook( __FILE__, 'pterotype_deactivate' );
register_uninstall_hook( __FILE__, 'pterotype_uninstall' );
?>
