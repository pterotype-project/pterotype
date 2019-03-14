<?php
namespace pterotype\activities\follow;

require_once plugin_dir_path( __FILE__ ) . '../following.php';
require_once plugin_dir_path( __FILE__ ) . '../actors.php';
require_once plugin_dir_path( __FILE__ ) . '../objects.php';
require_once plugin_dir_path( __FILE__ ) . '../outbox.php';
require_once plugin_dir_path( __FILE__ ) . '../../util.php';

function handle_outbox( $actor_slug, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    $object_row = \pterotype\objects\upsert_object( $object );
    $actor_id = \pterotype\actors\get_actor_id( $actor_slug );
    $res = \pterotype\following\request_follow( $actor_id, $object_row->id );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $activity;
}

function handle_inbox( $actor_slug, $activity ) {
    // For now, always Accept follow requests
    // in the future, implement a UI to either accept or reject
    // (or automatically accept if the user chooses to enable that setting)
    if ( actor_is_object( $actor_slug, $activity ) ) {
        if ( !array_key_exists( 'actor', $activity ) ) {
            return new \WP_Error(
                'invalid_activity',
                __( 'Activity must have an "actor" field', 'pterotype' ),
                array( 'status' => 400 )
            );
        }
        $follower = \pterotype\util\dereference_object( $activity['actor'] );
        \pterotype\objects\upsert_object( $follower );
        $accept = make_accept( $actor_slug, $activity );
        if ( is_wp_error( $accept ) ) {
            return $accept;
        }
        do_action( 'pterotype_send_accept', $actor_slug, $accept );
    }
    return $activity;
}

/*
Return true if the actor denoted by $actor_slug is the object of $activity
*/
function actor_is_object( $actor_slug, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return false;
    }
    $actor = \pterotype\actors\get_actor_by_slug( $actor_slug );
    if ( is_wp_error( $actor ) ) {
        return false;
    }
    $object = \pterotype\util\dereference_object( $activity['object'] );
    if ( !array_key_exists( 'type', $object ) ) {
        return false;
    }
    switch ( $object['type'] ) {
    case 'Link':
        return array_key_exists( 'href', $object ) && $object['href'] === $actor['id'];
    default:
        return array_key_exists( 'id', $object ) && $object['id'] === $actor['id'];
    }
}

function make_accept( $actor_slug, $follow ) {
    if ( !array_key_exists( 'actor', $follow ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have an actor', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $actor = \pterotype\actors\get_actor_by_slug( $actor_slug );
    $accept = array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Accept',
        'actor' => $actor['id'],
        'object' => $follow,
        'to' => $follow['actor'],
    );
    return $accept;
}
?>
