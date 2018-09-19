<?php
namespace objects;

// TODO for 'post' objects, store a post id instead of the full post text,
// then hydrate the text on read

function create_local_object( $object ) {
    global $wpdb;
    $res = $wpdb->insert( 'activitypub_objects', array(
        'object' => wp_json_encode( $object )
    ) );
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to insert object row', 'activitypub' )
        );
    }
    $object_id = $wpdb->insert_id;
    $object_url = get_rest_url( null, sprintf( '/activitypub/v1/object/%d', $object_id ) );
    $object['id'] = $object_url;
    $res = $wpdb->replace(
        'activitypub_objects',
        array (
            'id' => $object_id,
            'activitypub_id' => $object_url,
            'object' => wp_json_encode( $object )
        ),
        array( '%d', '%s', '%s' )
    );
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to hydrate object id', 'activitypub' )
        );
    }
    return $object;
}

function upsert_object( $object ) {
    global $wpdb;
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Objects must have an "id" field', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    $row = $wpdb->get_row( $wpdb->prepare(
        'SELECT * FROM activitypub_objects WHERE activitypub_url = %s', $object['id']
    ) );
    $res = true;
    if ( $row === null ) {
        $res = $wpdb->insert(
            'activitypub_objects',
            array(
                'activitypub_id' => $object['id'],
                'object' => wp_json_encode( $object )
            )
        );
    } else {
        $res = $wpdb->replace(
            'activitypub_objects',
            array(
                'id' => $row->id,
                'activitypub_id' => $object['id'],
                'object' => wp_json_encode( $object )
            ),
            array( '%d', '%s', '%s' )
        );
        $row = new stdClass();
        $row->id = $wpdb->insert_id;
    }
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to upsert object row', 'activitypub' )
        );
    }
    $row->object = $object;
    return $row;
}

function update_object( $object ) {
    global $wpdb;
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" field', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
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
    return json_decode( $object_json, true );
}

function get_object_by_activitypub_id( $activitypub_id ) {
    global $wpdb;
    $object_json = $wpdb->get_var( $wpdb->prepare(
        'SELECT object FROM activitypub_objects WHERE activitypub_id = %s', $activitypub_id
    ) );
    if ( is_null( $object_json ) ) {
        return new \WP_Error(
            'not_found', __( 'Object not found', 'activitypub' ), array( 'status' => 404 )
        );
    }
    return json_decode( $object_json, true );
}

function delete_object( $object ) {
    global $wpdb;
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" field', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    $activitypub_id = $object['id'];
    $res = $wpdb->delete( 'activitypub_objects', array( 'activitypub_id' => $id ), '%s' );
    if ( !$res ) {
        return new \WP_Error( 'db_error', __( 'Error deleting object', 'activitypub' ) );
    }
    return $res;
}

function create_object_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_objects (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            activitypub_id VARCHAR(255) UNIQUE NOT NULL,
            object TEXT NOT NULL
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8;
        "
    );
    $wpdb->query(
        "
        CREATE UNIQUE INDEX ACTIVITYPUB_ID_INDEX
        ON activitypub_objects (activitypub_id);
        "
    );
}
?>
