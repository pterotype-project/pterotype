<?php
/*
Plugin Name: ActivityPub
*/
require_once plugin_dir_path( __FILE__ ) . 'inc/init.php';

function activitypub_init() {
    do_action( 'activitypub_init' );
}

register_activation_hook( __FILE__, 'activitypub_init');
?>
