<?php
/*
Plugin Name: ActivityPub
*/
require_once plugin_dir_path( __FILE__ ) . 'inc/api.php';

add_action( 'rest_api_init', function() {
    \api\register_routes();
} );
?>
