<?php
/*
Plugin Name: Pterotype
*/
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/init.php';

define( 'PTEROTYPE_VERSION', '0.0.1' );
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

function pterotype_load() {
    do_action( 'pterotype_load' );
}

add_action( 'plugins_loaded', 'pterotype_load' );
register_activation_hook( __FILE__, 'pterotype_init' );
register_deactivation_hook( __FILE__, 'pterotype_deactivate' );
?>
