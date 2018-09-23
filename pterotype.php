<?php
/*
Plugin Name: Pterotype
*/
require_once plugin_dir_path( __FILE__ ) . 'inc/init.php';

define( 'PTEROTYPE_VERSION', '0.0.3' );

function pterotype_init() {
    do_action( 'pterotype_init' );
}

do_action( 'pterotype_load' );

register_activation_hook( __FILE__, 'pterotype_init');
?>
