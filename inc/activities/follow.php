<?php
namespace activities\follow;

require_once plugin_dir_path( __FILE__ ) . '/../following.php';
require_once plugin_dir_path( __FILE__ ) . '/../actors.php';
require_once plugin_dir_path( __FILE__ ) . '/../objects.php';
require_once plugin_dir_path( __FILE__ ) . '/../outbox.php';

function handle_outbox( $actor_slug, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    $object_row = \objects\upsert_object( $object );
    $actor_id = \actors\get_actor_id( $actor_slug );
    $res = \following\request_follow( $actor_id, $object_row->id );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $activity;
}

function handle_inbox( $actor_slug, $activity ) {
    // For now, always Accept follow requests
    // in the future, implement a UI to either accept or reject
    // (or automatically accept if the user chooses to enable that setting)
    $accept = make_accept( $actor_slug, $activity );
    if ( is_wp_error( $accept ) ) {
        return $accept;
    }
    $res = \outbox\handle_activity( $actor_slug, $accept );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $activity;
}

function make_accept( $actor_slug, $follow ) {
    if ( !array_key_exists( 'actor', $follow ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have an actor', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $accept = array(
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'type' => 'Accept',
        'actor' => \actors\get_actor_by_slug( $actor_slug ),
        'object' => $follow,
        'to' => $follow['actor'],
    );
    return $accept;
}
?>
