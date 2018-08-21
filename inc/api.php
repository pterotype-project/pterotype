<?php
namespace api;

require_once plugin_dir_path( __FILE__ ) . '/actors.php';

function get_user( $data ) {
    $handle = $data["handle"];
    $id = get_user_by( 'slug', $handle );
    return \actors\author_to_user( $id );
}

function register_routes() {
    register_rest_route( 'activitypub/v1', '/user/(?P<handle>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_user',
    ) );
}
?>
