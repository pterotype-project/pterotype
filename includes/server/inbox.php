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
    $activity = \util\dereference_object( $activity );
    if ( !array_key_exists( 'type', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have a type', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    forward_activity( $actor_slug, $activity );
    $activity = persist_activity( $actor_slug, $activity );
    if ( is_wp_error( $activity ) ) {
        return $activity;
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
    $res = new \WP_REST_Response();
    return $res;
}

function forward_activity( $actor_slug, $activity ) {
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
    \deliver\deliver_activity( $actor_slug, $activity );
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
        $dereferenced = \util\dereference_object( $field_value );
        if ( is_wp_error( $dereferenced ) ) {
            return false;
        } else {
            return \objects\is_local_object( $dereferenced );
        }
    }
    return false;
}

function persist_activity( $actor_slug, $activity ) {
    global $wpdb;
    $activity = \activities\persist_activity( $activity );
    if ( is_wp_error( $activity ) ) {
        return $activity;
    }
    $activity_id = \activities\get_activity_id( $activity['id'] );
    if ( !$activity_id ) {
        return new \WP_Error(
            'db_error',
            __( 'Error retrieving activity id', 'pterotype' )
        );
    }
    $actor_id = \actors\get_actor_id( $actor_slug );
    $seen_before = $wpdb->get_row( $wpdb->prepare(
        'SELECT * FROM pterotype_inbox WHERE actor_id = %d AND activity_id = %d',
        $actor_id,
        $activity_id
    ) );
    if ( $seen_before ) {
        return $activity;
    }
    $res = $wpdb->insert(
        'pterotype_inbox',
        array(
            'actor_id' => $actor_id,
            'activity_id' => $activity_id,
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
    $actor_id = \actors\get_actor_id( $actor_slug );
    if ( !$actor_id ) {
        return new \WP_Error(
            'not_found',
            __( 'Actor not found', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    $results = $wpdb->get_results( $wpdb->prepare(
        '
        SELECT pterotype_activities.activity FROM pterotype_inbox
        JOIN pterotype_actors
            ON pterotype_actors.id = pterotype_inbox.actor_id
        JOIN pterotype_activities
            ON pterotype_activities.id = pterotype_inbox.activity_id
        WHERE pterotype_inbox.actor_id = %d
        ',
        $actor_id
    ), ARRAY_A );
    return \collections\make_ordered_collection( array_map(
        function ( $result ) {
            return json_decode( $result['activity'], true );
        },
        $results
    ) );
}
?>
