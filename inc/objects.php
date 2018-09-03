<?php
namespace objects;

// TODO for 'post' objects, store a post id instead of the full post text,
// then hydrate the text on read

function create_object( $object ) {
    global $wpdb;
    $res = $wpdb->insert(
        'activitypub_objects', array( 'object' => wp_json_encode( $object ) )
    );
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to insert object row', 'activitypub' )
        );
    }
    $object['id'] = get_object_url( $wpdb->insert_id );
    return $object;
}

function update_object( $object ) {
    global $wpdb;
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" parameter', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    $id = get_id_from_url( $object['id'] );
    $object_json = wp_json_encode( $object );
    $res = $wpdb->update(
        'activitypub_object',
        array( 'object' => $object_json ),
        array( 'id' => $id ),
        '%s', '%d' );
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to update object row', 'activitypub' )
        );
    }
    return $object;
}

function get_object( $id ) {
    global $wpdb;
    $object_json = $wpdb->get_var( $wpdb->prepare(
        'SELECT object FROM activitypub_objects WHERE id = %d', $id
    ) ); 
    if ( is_null( $object_json ) ) {
        return new \WP_Error(
            'not_found', __( 'Object not found', 'activitypub' ), array( 'status' => 404 )
        );
    }
    $object = json_decode( $object_json, true );
    $object['id'] = get_object_url( $id );
    return $object;
}

function delete_object( $object ) {
    global $wpdb;
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" parameter', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    $id = get_id_from_url( $object['id'] );
    $res = $wpdb->delete( 'activitypub_objects', array( 'id' => $id ), '%d' );
    if ( !$res ) {
        return new \WP_Error( 'db_error', __( 'Error deleting object', 'activitypub' ) );
    }
    return $res;
}

function get_id_from_url( $url ) {
    global $wpdb;
    $matches = array();
    $found = preg_match(
        get_rest_url( null, '/activitypub/v1/object/(.+)' ), $url, $matches );
    if ( $found === 0 || count( $matches ) != 2 ) {
        return new \WP_Error(
            'invalid_url',
            sprintf( '%s %s', $url, __( 'is not a valid object url', 'activitypub' ) ),
            array( 'status' => 400 )
        );
    }
    $id = $matches[1];
    return $id;
}

function get_object_from_url( $url ) {
    return get_object( get_id_from_url( $url ) );
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
