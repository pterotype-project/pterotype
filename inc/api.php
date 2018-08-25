<?php
namespace api;

require_once plugin_dir_path( __FILE__ ) . '/actors.php';
require_once plugin_dir_path( __FILE__ ) . '/outbox.php';

function get_user_actor( $request ) {
    $handle = $request['handle'];
    $id = get_user_by( 'slug', $handle );
    return \actors\user_to_actor( $id );
}

function post_to_outbox( $request ) {
    $handle = $request['handle'] ;
    $activity = json_decode( $request->get_body() );
    return \outbox\persist_activity( $handle, $activity );
}

function register_routes() {
    register_rest_route( 'activitypub/v1', '/actor/(?P<handle>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_user_actor',
    ) );
    register_rest_route( 'activitypub/v1', '/actor/(?P<handle>[a-zA-Z0-9-]+)/outbox', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\post_to_outbox',
    ) );
}
?>
