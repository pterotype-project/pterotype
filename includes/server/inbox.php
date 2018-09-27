<?php
/*
When an Activity is received (i.e. POSTed) to an Actor's inbox, the server must:

  1. Forward the Activity as necessary
       See (https://www.w3.org/TR/activitypub/#x7-1-2-forwarding-from-inbox).
  2. Perform the side effects of receiving the Activity
  3. Persist the activity in the actor's inbox (and the attached object, if necessary)
*/
namespace inbox;

require_once plugin_dir_path( __FILE__ ) . 'activities.php';
require_once plugin_dir_path( __FILE__ ) . 'objects.php';
require_once plugin_dir_path( __FILE__ ) . 'deliver.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/create.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/update.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/delete.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/follow.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/accept.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/reject.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/announce.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/undo.php';

function handle_activity( $actor_slug, $activity ) {
    if ( !array_key_exists( 'type', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have a type', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    forward_activity( $activity );
    $res = persist_activity( $actor_slug, $activity );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    switch ( $activity['type'] ) {
    case 'Create':
        $activity = \activities\create\handle_inbox( $actor_slug, $activity );
        break;
    case 'Update':
        $activity = \activities\update\handle_inbox( $actor_slug, $activity );
        break;
    case 'Delete':
        $activity = \activities\delete\handle_inbox( $actor_slug, $activity );
        break;
    case 'Follow':
        $activity = \activities\follow\handle_inbox( $actor_slug, $activity );
        break;
    case 'Accept':
        $activity = \activities\accept\handle_inbox( $actor_slug, $activity );
        break;
    case 'Reject':
        $activity = \activities\reject\handle_inbox( $actor_slug, $activity );
        break;
    case 'Announce':
        $activity = \activities\announce\handle_inbox( $actor_slug, $activity );
        break;
    case 'Undo':
        $activity = \activities\undo\handle_inbox( $actor_slug, $activity );
        break;
    }
    if ( is_wp_error( $activity ) ) {
        return $activity;
    }
    return $res;
}

function forward_activity( $activity ) {
    if ( !array_key_exists( 'id', $activity ) ) {
        return;
    }
    $seen_before = \activities\get_activity_id( $activity['id'] );
    if ( $seen_before ) {
        return;
    }
    if ( !references_local_object( $activity, 0 ) ) {
        return;
    }
    $collections = array_intersect_key(
        $activity, 
        array_flip( array( 'to', 'cc', 'audience' ) )
    );
    if ( count( $collections ) === 0 ) {
        return;
    }
    \deliver\deliver_activity( $activity );
}

function references_local_object( $object, $depth ) {
    if ( $depth === 12 ) {
        return false;
    }
    if ( \objects\is_local_object( $object ) ) {
        return true;
    }
    $fields = array_intersect_key(
        $object,
        array_flip( array( 'inReplyTo', 'object', 'target', 'tag' ) )
    );
    if ( count( $fields ) === 0 ) {
        return false;
    }
    $result = false;
    foreach ( $fields as $field_value ) {
        if ( $result ) {
            return $result;
        }
        // $field_value is either a url, a Link, or an object
        if ( is_array( $field_value ) ) {
            if ( array_key_exists( 'id', $field_value ) ) {
                return \objects\is_local_object( $field_value );
            } else if ( array_key_exists( 'href', $field_value ) ) {
                $response = wp_remote_get( $field_value['href'] );
                if ( is_wp_error( $response ) ) {
                    return false;
                }
                $body = wp_remote_retrieve_body( $response );
                if ( empty( $body ) ) {
                    return false;
                }
                $body_array = json_decode( $body, true );
                return $body_array && references_local_object( $body_array, $depth + 1 );
            } else {
                return false;
            }
        } else {
            $response = wp_remote_get( $field_value );
            if ( is_wp_error( $response ) ) {
                continue;
            }
            $body = wp_remote_retrieve_body( $response );
            if ( empty( $body ) ) {
                continue;
            }
            $body_array = json_decode( $body, true );
            $result = $body_array && references_local_object( $body_array, $depth + 1 );
        }
    }
    return false;
}

function persist_activity( $actory_slug, $activity ) {
    global $wpdb;
    $activity = \activities\persist_activity( $activity );
    if ( is_wp_error( $activity ) ) {
        return $activity;
    }
    $activity_id = $wpdb->insert_id;
    $actor_id = \actors\get_actor_id( $actor_slug );
    $wpdb->insert( 'pterotype_inbox', array(
        'actor_id' => $actor_id,
        'activity_id' => $activity_id,
    ) );
    $response = new \WP_Rest_Response();
    return $response;
}
?>
