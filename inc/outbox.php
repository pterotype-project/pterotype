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
require_once plugin_dir_path( __FILE__ ) . '/activities/create.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/update.php';
require_once plugin_dir_path( __FILE__ ) . '/activities/delete.php';

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
        break;
    case 'Add':
        break;
    case 'Remove':
        break;
    case 'Like':
        break;
    case 'Block':
        break;
    case 'Undo':
        break;
    default:
        // handle wrapping object in Create activity
        break;
    }
    if ( is_wp_error( $activity ) ) {
        return $activity;
    } else {
        deliver_activity( $activity );
        return persist_activity( $actor, $activity );
    }
}

function deliver_activity( $activity ) {
    // TODO
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
