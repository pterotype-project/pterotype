<?php
/*
When an Activity is received (i.e. POSTed) to an Actor's outbox, the server must:

  0. Make sure the request is authenticated
  1. Add the Activity to the Actor's outbox collection in the DB
  2. Deliver the Activity to the appropriate inboxes based on the received Activity
       This involves discovering all the inboxes, including nested ones if the target
       is a collection, deduplicating inboxes, and the POSTing the Activity to each
       target inbox.
  3. Perform side effects as necessary
*/
namespace outbox;

require_once plugin_dir_path( __FILE__ ) . 'activities.php';
require_once plugin_dir_path( __FILE__ ) . 'actors.php';
require_once plugin_dir_path( __FILE__ ) . 'deliver.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/create.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/update.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/delete.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/like.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/follow.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/block.php';
require_once plugin_dir_path( __FILE__ ) . 'activities/undo.php';
require_once plugin_dir_path( __FILE__ ) . '../util.php';

function handle_activity( $actor_slug, $activity ) {
    xdebug_break();
    // TODO handle authentication/authorization
    $activity = \util\dereference_object( $activity );
    if ( is_wp_error( $activity ) ) {
        return $activity;
    }
    if ( !array_key_exists( 'type', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Invalid activity', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $activity = persist_activity( $actor_slug, $activity );
    if ( is_wp_error( $activity ) ) {
        return $activity;
    }
    switch ( $activity['type'] ) {
    case 'Create':
        $activity = \activities\create\handle_outbox( $actor_slug, $activity );
        break;
    case 'Update':
        $activity = \activities\update\handle_outbox( $actor_slug, $activity );
        break;
    case 'Delete':
        $activity = \activities\delete\handle_outbox( $actor_slug, $activity );
        break;
    case 'Follow':
        $activity = \activities\follow\handle_outbox( $actor_slug, $activity );
        break;
    case 'Add':
        return new \WP_Error(
            'not_implemented',
            __( 'The Add activity has not been implemented', 'pterotype' ),
            array( 'status' => 501 )
        );
        break;
    case 'Remove':
        return new \WP_Error(
            'not_implemented',
            __( 'The Remove activity has not been implemented', 'pterotype' ),
            array( 'status' => 501 )
        );
        break;
    case 'Like':
        $activity = \activities\like\handle_outbox( $actor_slug, $activity );
        break;
    case 'Block':
        $activity = \activities\block\handle_outbox( $actor_slug, $activity );
        break;
    case 'Undo':
        $activity = \activities\undo\handle_outbox( $actor_slug, $activity );
        break;
    case 'Accept':
        $activity = \activities\accept\handle_inbox( $actor_slug, $activity );
        break;
    // For the other activities, just persist and deliver
    case 'Reject':
    case 'Announce':
    case 'Arrive':
    case 'Dislike':
    case 'Flag':
    case 'Ignore':
    case 'Invite':
    case 'Join':
    case 'Leave':
    case 'Listen':
    case 'Move':
    case 'Offer':
    case 'Question':
    case 'Read':
    case 'TentativeReject':
    case 'TentativeAccept':
    case 'Travel':
    case 'View':
        break;
    // For all other objects, wrap in a Create activity
    default:
        $create_activity = wrap_object_in_create( $activity );
        if ( is_wp_error( $create_activity ) ) {
            return $create_activity;
        }
        $activity = \activities\create\handle_outbox( $actor_slug, $create_activity );
        break;
    }
    if ( is_wp_error( $activity ) ) {
        return $activity;
    }
    // the activity may have changed while processing side effects, so persist the new version
    $activity = \activities\persist_activity( $activity );
    if ( is_wp_error( $activity ) ) {
        return $activity;
    }
    deliver_activity( $activity );
    $res = new \WP_REST_Response();
    $res->set_status(201);
    $res->header( 'Location', $activity['id'] );
    $res->set_data( $activity );
    return $res;
}

function get_outbox( $actor_slug ) {
    global $wpdb;
    // TODO what sort of joins should these be?
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
            SELECT pterotype_activities.activity FROM pterotype_outbox
            JOIN pterotype_actors
                ON pterotype_actors.id = pterotype_outbox.actor_id
            JOIN pterotype_activities
                ON pterotype_activities.id = pterotype_outbox.activity_id
            WHERE pterotype_outbox.actor_id = %d
            ',
            $actor_id
    ), ARRAY_A );
    // TODO return PagedCollection if $activites is too big
    return \collections\make_ordered_collection( array_map(
        function ( $result) {
            return json_decode( $result['activity'], true);
        },
        $results
    ) );
}

function deliver_activity( $activity ) {
    \deliver\deliver_activity( $activity );
    $activity = \activities\strip_private_fields( $activity );
    return $activity;
}

function persist_activity( $actor_slug, $activity ) {
    global $wpdb;
    $activity = \activities\strip_private_fields( $activity );
    $activity = \activities\create_local_activity( $activity );
    $activity_id = $wpdb->insert_id;
    $actor_id = \actors\get_actor_id( $actor_slug );
    $res = $wpdb->insert( 'pterotype_outbox', array(
        'actor_id' => $actor_id,
        'activity_id' => $activity_id,
    ) );
    if ( !$res ) {
        return new \WP_Error(
            'db_error',
            __( 'Error inserting outbox row', 'pterotype' )
        );
    }
    return $activity;
}

function wrap_object_in_create( $actor_slug, $object ) {
    $actor = \actors\get_actor_by_slug( $actor_slug );
    if ( is_wp_error( $actor ) ) {
        return $actor;
    }
    $activity = array(
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'type' => 'Create',
        'actor' => $actor,
        'object' => $object
    );
    return $activity;
}
?>
