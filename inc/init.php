<?php
namespace init;

require_once plugin_dir_path( __FILE__ ) . '/outbox.php';
require_once plugin_dir_path( __FILE__ ) . '/api.php';
require_once plugin_dir_path( __FILE__ ) . '/objects.php';

add_action( 'rest_api_init', function() {
    \api\register_routes();
} );

add_action( 'activitypub_init', function() {
    \outbox\create_outbox_table();
    \objects\create_object_table();
} );
?>
