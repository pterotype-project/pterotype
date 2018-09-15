<?php
namespace api;

require_once plugin_dir_path( __FILE__ ) . '/actors.php';
require_once plugin_dir_path( __FILE__ ) . '/outbox.php';
require_once plugin_dir_path( __FILE__ ) . '/objects.php';
require_once plugin_dir_path( __FILE__ ) . '/activities.php';

function get_actor( $request ) {
    $actor = $request['actor'];
    return \actors\get_actor_by_slug( $actor );
}

function handle_outbox( $request ) {
    $actor = $request['actor'];
    $activity = json_decode( $request->get_body(), true );
    return \outbox\handle_activity( $actor, $activity );
}

function get_object( $request ) {
    $id = $request['id'];
    return \objects\get_object( $id );
}

function get_activity( $request ) {
    $id = $request['id'];
    return \activities\get_activity( $id );
}

function register_routes() {
    register_rest_route( 'activitypub/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)/outbox', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\handle_outbox',
    ) );
    register_rest_route( 'activitypub/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_actor',
    ) );
    register_rest_route( 'activitypub/v1', '/object/(?P<id>[0-9]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_object',
    ) );
    register_rest_route( 'activitypub/v1', '/activity/(?P<id>[0-9]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_activity',
    ) );
}
?>
