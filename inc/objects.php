<?php
namespace objects;

function persist_object( $object ) {
    global $wpdb;
    $wpdb->insert( 'activitypub_objects', array( 'object' => wp_json_encode( $object ) ) );
    $object['id'] = get_object_url( $wpdb->insert_id );
    return $object;
}

function get_object( $id ) {
    global $wpdb;
    $object_json = $wpdb->get_var( sprintf(
        'SELECT object FROM activitypub_objects WHERE id = %d', $id
    ) ); 
    if ( is_null( $object_json ) ) {
        return new \WP_Error(
            404, __( 'Object not found', 'activitypub' ), array ( 'status' => 404 )
        );
    }
    $object = json_decode( $object_json, true );
    $object['id'] = get_object_url( $id );
    return $object;
}

function get_object_url( $id ) {
    return get_rest_url( null, sprintf( '/activitypub/v1/object/%d', $id ) );
}

function create_object_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_objects (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            object TEXT NOT NULL
        );
        "
    );
}
?>
