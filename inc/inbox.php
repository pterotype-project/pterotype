<?php
/*
When an Activity is received (i.e. POSTed) to an Actor's inbox, the server must:

  1. Forward the Activity as necessary
       See (https://www.w3.org/TR/activitypub/#x7-1-2-forwarding-from-inbox).
  2. Perform the side effects of receiving the Activity
  3. Persist the activity in the actor's inbox (and the attached object, if necessary)

To persist an activity or object:
  1. Check if the activity or object already exists in the DB
  2. If yes, do nothing
  3. If no, add it to the DB
*/
namespace inbox;

function handle_activity( $actor, $activity ) {
    if ( !array_key_exists( 'type', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activity must have a type', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    forward_activity( $activity );
    switch ( $activity['type'] ) {
    case 'Create':
        break;
    case 'Update':
        break;
    case 'Delete':
        break;
    case 'Follow':
        break;
    case 'Accept':
        break;
    case 'Reject':
        break;
    case 'Add':
        break;
    case 'Remove':
        break;
    case 'Announce':
        break;
    case 'Undo':
        break;
    }
    if ( is_wp_error( $activity ) ) {
        return $activity;
    }
    persist_activity( $activity );
    return new \WP_REST_Response();
}

function forward_activity( $activity ) {

}

function persist_activity( $activity ) {
    global $wpdb;

}

function create_inbox_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_inbox(
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
