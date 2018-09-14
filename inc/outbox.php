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
require_once plugin_dir_path( __FILE__ ) . '/deliver.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/create.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/update.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/delete.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/like.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/follow.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/block.php';

function handle_activity( $actor, $activity ) {
    // TODO handle authentication/authorization
    if ( !array_key_exists( "type", $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Invalid activity', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    switch ( $activity['type'] ) {
    case 'Create':
        $activity = \activities\create\handle( $actor, $activity );
        break;
    case 'Update':
        $activity = \activities\update\handle( $actor, $activity );
        break;
    case 'Delete':
        $activity = \activities\delete\handle( $actor, $activity );
        break;
    case 'Follow':
        $activity = \activities\follow\handle( $actor, $activity );
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
        $activity = \activities\like\handle( $actor, $activity );
        break;
    case 'Block':
        $activity = \activities\block\handle( $actor, $activity );
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
        $activity = \activities\create\handle( $actor, $create_activity );
        break;
    }
    if ( is_wp_error( $activity ) ) {
        return $activity;
    } else {
        $activity = deliver_activity( $activity );
        return persist_activity( $actor, $activity );
    }
}

function deliver_activity( $activity ) {
    \deliver\deliver_activity( $activity );
    $activity = \activities\strip_private_fields( $activity );
    return $activity;
}

function persist_activity( $actor, $activity ) {
    global $wpdb;
    $activity = \activities\persist_activity( $activity );
    $activity_id = $wpdb->insert_id;
    $wpdb->insert( 'activitypub_outbox',
                   array(
                       'actor' => $actor,
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
            actor VARCHAR(128) NOT NULL,
            activity_id INT UNSIGNED NOT NULL,
            FOREIGN KEY activity_fk(activity_id)
            REFERENCES activitypub_activities(id)
        );
        "
    );
}
?>
