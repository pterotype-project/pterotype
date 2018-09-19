<?php
/*
Plugin Name: Pterotype
*/
require_once plugin_dir_path( __FILE__ ) . 'inc/init.php';

define( 'PTEROTYPE_VERSION', '0.0.1' );

function pterotype_init() {
    update_option( 'pterotype_version', PTEROTYPE_VERSION );
    do_action( 'pterotype_init' );
}

register_activation_hook( __FILE__, 'pterotype_init');
?>
