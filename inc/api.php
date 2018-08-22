<?php
namespace api;

require_once plugin_dir_path( __FILE__ ) . '/actors.php';

function get_user_actor( $data ) {
    $handle = $data["handle"];
    $id = get_user_by( 'slug', $handle );
    return \actors\user_to_actor( $id );
}

function register_routes() {
    register_rest_route( 'activitypub/v1', '/actor/(?P<handle>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_user_actor',
    ) );
}
?>
