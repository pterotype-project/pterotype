<?php
namespace api;

require_once plugin_dir_path( __FILE__ ) . 'actors.php';
require_once plugin_dir_path( __FILE__ ) . 'outbox.php';
require_once plugin_dir_path( __FILE__ ) . 'inbox.php';
require_once plugin_dir_path( __FILE__ ) . 'objects.php';
require_once plugin_dir_path( __FILE__ ) . 'activities.php';
require_once plugin_dir_path( __FILE__ ) . 'following.php';
require_once plugin_dir_path( __FILE__ ) . 'likes.php';
require_once plugin_dir_path( __FILE__ ) . 'shares.php';

function get_actor( $request ) {
    $actor = $request->get_url_params()['actor'];
    return \actors\get_actor_by_slug( $actor );
}

function post_to_outbox( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    $activity = json_decode( $request->get_body(), true );
    return \outbox\handle_activity( $actor_slug, $activity );
}

function get_outbox( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    return \outbox\get_outbox( $actor_slug );
}

function post_to_inbox( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    $activity = json_decode( $request->get_body(), true );
    return \inbox\handle_activity( $actor_slug, $activity );
}

function get_inbox( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    return \inbox\get_inbox( $actor_slug );
}

function get_object( $request ) {
    $id = $request->get_url_params()['id'];
    return \objects\get_object( $id );
}

function get_activity( $request ) {
    $id = $request->get_url_params()['id'];
    return \activities\get_activity( $id );
}

function get_following( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    return \following\get_following_collection( $actor_slug );
}

function get_followers( $request ) {
    $actor_slug = $request->get_url_params()['actor'];
    return \followers\get_followers_collection( $actor_slug );
}

function get_likes( $request ) {
    $object_id = $request->get_url_params()['object'];
    return \likes\get_likes_collection( $object_id );
}

function get_shares( $request ) {
    $object_id = $request->get_url_params()['object'];
    return \shares\get_shares_collection( $object_id );
}

function user_can_post_to_outbox() {
    return current_user_can( 'publish_posts' );
}

function register_routes() {
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)/outbox', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\post_to_outbox',
        'permission_callback' => __NAMESPACE__ . '\user_can_post_to_outbox',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)/outbox', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_outbox',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)/inbox', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\post_to_inbox',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)/inbox', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_inbox',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_actor',
    ) );
    register_rest_route( 'pterotype/v1', '/object/(?P<id>[0-9]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_object',
    ) );
    register_rest_route( 'pterotype/v1', '/activity/(?P<id>[0-9]+)', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_activity',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)/following', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_following',
    ) );
    register_rest_route( 'pterotype/v1', '/actor/(?P<actor>[a-zA-Z0-9-]+)/followers', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_followers',
    ) );
    register_rest_route( 'pterotype/v1', '/object/(?P<object>[0-9]+)/likes', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_likes',
    ) );
    register_rest_route( 'pterotype/v1', '/object/(?P<object>[0-9]+)/shares', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\get_shares',
    ) );
}
?>
