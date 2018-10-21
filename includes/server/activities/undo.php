<?php
namespace pterotype\activities\undo;

require_once plugin_dir_path( __FILE__ ) . '../../util.php';
require_once plugin_dir_path( __FILE__ ) . '../actors.php';
require_once plugin_dir_path( __FILE__ ) . '../objects.php';
require_once plugin_dir_path( __FILE__ ) . '../likes.php';
require_once plugin_dir_path( __FILE__ ) . '../following.php';
require_once plugin_dir_path( __FILE__ ) . '../followers.php';

function handle_outbox( $actor_slug, $activity ) {
    $object = validate_undo( $activity );
    if ( is_wp_error( $object ) ) {
        return $object;
    }
    $actor_id = \pterotype\actors\get_actor_id( $actor_slug );
    if ( !$actor_id ) {
        return new \WP_Error(
            'not_found',
            __( 'Actor not found', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    switch ( $object['type'] ) {
    case 'Like':
        if ( !array_key_exists( 'object', $object ) ) {
            return new \WP_Error(
                'invalid_activity',
                __( 'Expected an "object" field', 'pterotype' ),
                array( 'status' => 400 )
            );
        }
        $liked_object_url = \pterotype\util\get_id( $object['object'] );
        if ( !$liked_object_url ) {
            break;
        }
        $liked_object_id = \pterotype\objects\get_object_id( $liked_object_url );
        if ( !$liked_object_id ) {
            break;
        }
        \pterotype\likes\delete_local_actor_like( $actor_id, $liked_object_id );
        $like_id = \pterotype\objects\get_object_id( $object['id'] );
        if ( !$like_id ) {
            break;
        }
        \pterotype\likes\delete_object_like( $liked_object_id, $like_id );
        break;
    case 'Block':
        if ( !array_key_exists( 'object', $object ) ) {
            break;
        }
        $blocked_object_url = \pterotype\util\get_id( $object['object'] );
        if ( !$blocked_object_url ) {
            break;
        }
        $res = \pterotype\blocks\delete_block( $actor_id, $blocked_object_url );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        break;
    case 'Follow':
        if ( !array_key_exists( 'object', $object ) ) {
            break;
        }
        $follow_object_url = \pterotype\util\get_id( $object['object'] );
        if ( !$follow_object_url ) {
            break;
        }
        $follow_object_id = \pterotype\objects\get_object_id( $follow_object_url );
        if ( !$follow_object_id ) {
            break;
        }
        \pterotype\following\reject_follow( $actor_id, $follow_object_id );
        break;
    // TODO I should support Undoing these as well
    case 'Add':
    case 'Remove':
    case 'Accept':
        break;
    default:
        break;
    }
    return $activity;
}

function handle_inbox( $actor_slug, $activity ) {
    $object = validate_undo( $activity );
    if ( is_wp_error( $object ) ) {
        return $object;
    }
    $actor_id = \pterotype\actors\get_actor_id( $actor_slug );
    if ( !$actor_id ) {
        return new \WP_Error(
            'not_found',
            __( 'Actor not found', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    switch( $object['type'] ) {
    case 'Like':
        if ( !array_key_exists( 'object', $object ) ) {
            break;
        }
        if ( \pterotype\objects\is_local_object( $object['object'] ) ) {
            $object_url = \pterotype\objects\get_object_id( $object['object'] );
            if ( !$object_url ) {
                break;
            }
            $object_id = \pterotype\objects\get_object_id( $object_url );
            $like_id = \pterotype\objects\get_object_id( $object['id'] );
            if ( !$like_id ) {
                break;
            }
            \pterotype\likes\delete_object_like( $object_id, $like_id );
        }
        break;
    case 'Follow':
        if ( !array_key_exists( 'actor', $object ) ) {
            break;
        }
        $follower = $object['actor'];
        \pterotype\followers\remove_follower( $actor_slug, $follower );
        break;
    case 'Accept':
        if ( !array_key_exists( 'object', $object ) ) {
            break;
        }
        $accept_object = \pterotype\util\dereference_object( $object['object'] );
        if ( is_wp_error( $object ) ) {
            break;
        }
        if ( array_key_exists( 'type', $accept_object ) && $accept_object['type'] === 'Follow' ) {
            if ( !array_key_exists( 'object', $accept_object ) ) {
                break;
            }
            $followed_object_url = \pterotype\util\get_id( $accept_object['object'] );
            $followed_object_id = \pterotype\objects\get_object_id( $followed_object_url );
            if ( !$followed_object_id ) {
                break;
            }
            // Put the follow request back into the PENDING state
            \pterotype\following\request_follow( $actor_id, $followed_object_id );
        }
        break;
    default:
        break;
    }
    return $activity;
}

function validate_undo( $activity ) {
    if ( !array_key_exists( 'actor', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an "actor" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an "object" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = \pterotype\util\dereference_object( $activity['object'] );
    if ( is_wp_error( $object ) ) {
        return $object;
    }
    if ( !array_key_exists( 'actor', $object ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected a "actor" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !\pterotype\util\is_same_object( $activity['actor'], $object['actor'] ) ) {
        return new \WP_Error(
            'unauthorized',
            __( 'Unauthorzed Undo activity', 'pterotype' ),
            array( 'status' => 403 )
        );
    }
    if ( !array_key_exists( 'type', $object ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected a "type" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    return $object;
}
?>
