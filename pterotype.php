<?php
/*
Plugin Name: Pterotype
*/
require_once plugin_dir_path( __FILE__ ) . 'inc/init.php';

function pterotype_init() {
    do_action( 'pterotype_init' );
}

register_activation_hook( __FILE__, 'pterotype_init');
?>
