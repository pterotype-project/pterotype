<?php
namespace activities;

function get_activity( $id ) {
    global $wpdb;
    $activity_json = $wpdb->get_var( $wpdb->prepare(
        'SELECT activity FROM activitypub_activities WHERE id = %d', $id
    ) );
    if ( is_null( $activity_json ) ) {
        return new \WP_Error(
            'not_found', __( 'Activity not found', 'activitypub' ), array( 'status' => 404 )
        );
    }
    $activity = json_decode( $activity_json, true );
    $activity['id'] = get_activity_url( $id );
    return $activity;
}

function persist_activity( $activity ) {
    global $wpdb;
    $wpdb->insert(
        'activitypub_activities', array( 'activity' => wp_json_encode( $activity ) )
    );
    $activity["id"] = get_activity_url( $wpdb->insert_id );
    return $activity;
}

function get_activity_url( $id ) {
    return get_rest_url( null, sprintf( '/activitypub/v1/activity/%d', $id ) );
}

function create_activities_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_activities (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            activity TEXT NOT NULL
        );
        "
    );
}
?>
