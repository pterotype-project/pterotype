<?php
namespace pterotype\activities\accept;

require_once plugin_dir_path( __FILE__ ) . '../following.php';
require_once plugin_dir_path( __FILE__ ) . '../followers.php';
require_once plugin_dir_path( __FILE__ ) . '../objects.php';
require_once plugin_dir_path( __FILE__ ) . '../actors.php';
require_once plugin_dir_path( __FILE__ ) . '../../util.php';

function handle_inbox( $actor_slug, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have an "object" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = \pterotype\util\dereference_object( $activity['object'] );
    if ( array_key_exists( 'type', $object ) ) {
        switch ( $object['type'] ) {
        case 'Follow':
            if ( !array_key_exists( 'object', $object ) ) {
                break;
            }
            $follow_object = \pterotype\util\dereference_object( $object['object'] );
            if ( !array_key_exists( 'id', $follow_object ) ) {
                break;
            }
            $object_id = \pterotype\objects\get_object_by_activitypub_id( $follow_object['id'] );
            if ( is_wp_error( $object_id ) ) {
                break;
            }
            $actor_id = \pterotype\actors\get_actor_id( $actor_slug );
            \pterotype\following\accept_follow( $actor_id, $object_id );
            break;
        default:
            break;
        }
    }
    return $activity;
}

function handle_outbox( $actor_slug, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have an "object" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    if ( array_key_exists( 'type', $object ) ) {
        switch ( $object['type'] ) {
        case 'Follow':
            if ( !array_key_exists( 'actor', $object ) ) {
                break;
            }
            $follower = \pterotype\util\dereference_object( $object['actor'] );
            \pterotype\followers\add_follower( $actor_slug, $follower );
            break;
        }
    }
    return $activity;
}
?>
