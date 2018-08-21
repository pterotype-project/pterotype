<?php
include 'actors';

function get_user( $data ) {
    $handle = $data["handle"];
    $id = get_user_by( 'slug', $handle );
    return actors\author_to_user( $id );
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'activitypub/v1', '/user/(?P<handle>[A-Za-z0-9]+)', array(
        'methods' => 'GET',
        'callback' => 'get_user',
    ) );
} );
?>
