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

require_once plugin_dir_path( __FILE__ ) . '/activities.php';
require_once plugin_dir_path( __FILE__ ) . '/actors.php';
require_once plugin_dir_path( __FILE__ ) . '/deliver.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/create.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/update.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/delete.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/like.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/follow.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/block.php';

function handle_activity( $actor_slug, $activity ) {
    // TODO handle authentication/authorization
    if ( !array_key_exists( 'type', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Invalid activity', 'activitypub' ),
            array( 'status' => 400 )
        );
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
            __( 'The Add activity has not been implemented', 'activitypub' ),
            array( 'status' => 501 )
        );
        break;
    case 'Remove':
        return new \WP_Error(
            'not_implemented',
            __( 'The Remove activity has not been implemented', 'activitypub' ),
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
        return new \WP_Error(
            'not_implemented',
            __( 'The Undo activity has not been implemented', 'activitypub' ),
            array( 'status' => 501 )
        );
        break;
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
    $activity = deliver_activity( $activity );
    return persist_activity( $actor_slug, $activity );
}

function get_outbox( $actor_slug ) {
    global $wpdb;
    // TODO what sort of joins should these be?
    $results = $wpdb->get_results( $wpdb->prepare(
            "
            SELECT activitypub_activities.activity FROM activitypub_outbox 
            JOIN activitypub_actors 
                ON activitypub_actors.id = activitypub_outbox.actor_id
            JOIN activitypub_activities
                ON activitypub_activities.id = activitypub_outbox.activity_id
            WHERE activitypub_outbox.actor_id = %d
            ",
            $actor_id
    ) );
    // TODO return PagedCollection if $activites is too big
    return \collections\make_ordered_collection( array_map(
        function ( $result) {
            return json_decode( $result->activity, true);
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
    $activity = \activities\create_local_activity( $activity );
    $activity_id = $wpdb->insert_id;
    $actor_id = \actors\get_actor_id( $actor_slug );
    $wpdb->insert( 'activitypub_outbox', array(
        'actor_id' => $actor_id,
        'activity_id' => $activity_id,
    ) );
    $response = new \WP_REST_Response();
    $response->set_status( 201 );
    $response->header( 'Location', $activity['id'] );
    return $response;
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

function create_outbox_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_outbox (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            actor_id UNSIGNED INT NOT NULL,
            activity_id INT UNSIGNED NOT NULL,
            FOREIGN KEY activity_fk(activity_id)
            REFERENCES activitypub_activities(id),
        );
        "
    );
}
?>
