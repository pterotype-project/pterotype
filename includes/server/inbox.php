<?php
/*
When an Activity is received (i.e. POSTed) to an Actor's inbox, the server must:

  1. Forward the Activity as necessary
       See (https://www.w3.org/TR/activitypub/#x7-1-2-forwarding-from-inbox).
  2. Perform the side effects of receiving the Activity
  3. Persist the activity in the actor's inbox (and the attached object, if necessary)
*/
namespace pterotype\inbox;

use function pterotype\util\dereference_object;

require_once plugin_dir_path( __FILE__ ) . 'objects.php';
require_once plugin_dir_path( __FILE__ ) . 'deliver.php';
require_once plugin_dir_path( __FILE__ ) . 'collections.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/create.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/update.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/delete.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/follow.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/accept.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/reject.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/announce.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/undo.php';
require_once plugin_dir_path( __FILE__ ) . '../util.php';

function handle_activity( $actor_slug, $activity ) {
    // TODO how should I handle duplicate activities getting posted here and in the outbox?
    // Is it okay to just drop them if I already have the activity id in the objects table?
    // A good strategy would just be to make sure all activities are idempotent, e.g.
    // don't create multiple Accepts of the same Follow
    // TODO verify the authenticity of the activity
    $actor_id = \pterotype\actors\get_actor_id( $actor_slug );
    if ( ! $actor_id ) {
        return new \WP_Error(
            'not_found',
            __( "Actor $actor_slug not found", 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    $activity = \pterotype\util\dereference_object( $activity );
    if ( !array_key_exists( 'type', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have a type', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    forward_activity( $actor_slug, $activity );
    $persisted = persist_activity( $actor_id, $activity );
    if ( is_wp_error( $persisted ) ) {
        return $persisted;
    }
    $activity['id'] = $persisted['id'];
    switch ( $activity['type'] ) {
    case 'Create':
        $activity = \pterotype\activities\create\handle_inbox( $actor_slug, $activity );
        break;
    case 'Update':
        $activity = \pterotype\activities\update\handle_inbox( $actor_slug, $activity );
        break;
    case 'Delete':
        $activity = \pterotype\activities\delete\handle_inbox( $actor_slug, $activity );
        break;
    case 'Follow':
        $activity = \pterotype\activities\follow\handle_inbox( $actor_slug, $activity );
        break;
    case 'Accept':
        $activity = \pterotype\activities\accept\handle_inbox( $actor_slug, $activity );
        break;
    case 'Reject':
        $activity = \pterotype\activities\reject\handle_inbox( $actor_slug, $activity );
        break;
    case 'Announce':
        $activity = \pterotype\activities\announce\handle_inbox( $actor_slug, $activity );
        break;
    case 'Undo':
        $activity = \pterotype\activities\undo\handle_inbox( $actor_slug, $activity );
        break;
    }
    if ( is_wp_error( $activity ) ) {
        return $activity;
    }
    $res = new \WP_REST_Response();
    return $res;
}

function forward_activity( $actor_slug, $activity ) {
    if ( !array_key_exists( 'id', $activity ) ) {
        return;
    }
    $seen_before = \pterotype\objects\get_object_id( $activity['id'] );
    if ( $seen_before ) {
        return;
    }
    // Don't forward activities whose objects are actors
    if ( array_key_exists( 'object', $activity ) &&
         is_actor( $activity['object'] ) ) {
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
    \pterotype\deliver\deliver_activity( $actor_slug, $activity, false );
}

function is_actor( $object ) {
    $object = dereference_object( $object );
    if ( ! $object || is_wp_error( $object) ) {
        return false;
    }
    return array_key_exists( 'publicKey', $object );
}

function references_local_object( $object, $depth ) {
    if ( $depth === 12 ) {
        return false;
    }
    if ( \pterotype\objects\is_local_object( $object ) ) {
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
        $dereferenced = \pterotype\util\dereference_object( $field_value );
        if ( is_wp_error( $dereferenced ) ) {
            return false;
        } else {
            return \pterotype\objects\is_local_object( $dereferenced );
        }
    }
    return false;
}

function persist_activity( $actor_id, $activity ) {
    global $wpdb;
    $row = \pterotype\objects\upsert_object( $activity );
    if ( is_wp_error( $row ) ) {
        return $row;
    }
    $activity = $row->object;
    $activity_id = \pterotype\objects\get_object_id( $activity['id'] );
    if ( !$activity_id ) {
        return new \WP_Error(
            'db_error',
            __( 'Error retrieving activity id', 'pterotype' )
        );
    }
    $seen_before = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pterotype_inbox 
            WHERE actor_id = %d AND object_id = %d",
        $actor_id,
        $activity_id
    ) );
    if ( $seen_before ) {
        return $activity;
    }
    $res = $wpdb->insert(
        $wpdb->prefix . 'pterotype_inbox',
        array(
            'actor_id' => $actor_id,
            'object_id' => $activity_id,
        ),
        '%d'
    );
    if ( !$res ) {
        return new \WP_Error(
            'db_error',
            __( 'Error persisting inbox record', 'pterotype' )
        );
    }
    return $activity;
}

function get_inbox( $actor_slug ) {
    global $wpdb;
    $actor_id = \pterotype\actors\get_actor_id( $actor_slug );
    if ( !$actor_id ) {
        return new \WP_Error(
            'not_found',
            __( 'Actor not found', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    $results = $wpdb->get_results( $wpdb->prepare(
        "
       SELECT {$wpdb->prefix}pterotype_objects.object
       FROM {$wpdb->prefix}pterotype_inbox
       JOIN {$wpdb->prefix}pterotype_actors
           ON {$wpdb->prefix}pterotype_actors.id = {$wpdb->prefix}pterotype_inbox.actor_id
       JOIN {$wpdb->prefix}pterotype_objects
           ON {$wpdb->prefix}pterotype_objects.id = {$wpdb->prefix}pterotype_inbox.object_id
       WHERE {$wpdb->prefix}pterotype_inbox.actor_id = %d
       ",
        $actor_id
    ), ARRAY_A );
    return \pterotype\collections\make_ordered_collection( array_map(
        function( $result ) {
            return json_decode( $result['object'], true );
        },
        $results
    ) );
}
?>
