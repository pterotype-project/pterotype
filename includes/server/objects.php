<?php
namespace pterotype\objects;

require_once plugin_dir_path( __FILE__ ) . '../util.php';

// TODO for 'post' objects, store a post id instead of the full post text,
// then hydrate the text on read

function create_local_object( $object ) {
    global $wpdb;
    $object = \pterotype\util\dereference_object( $object );
    if ( is_wp_error( $object ) ) {
        return $object;
    }
    if ( !array_key_exists( 'type', $object) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have a "type" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $res = $wpdb->insert( $wpdb->prefix . 'pterotype_objects', array(
        'object' => wp_json_encode( $object )
    ) );
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to insert object row', 'pterotype' )
        );
    }
    $object_id = $wpdb->insert_id;
    $type = $object['type'];
    $object_url = get_rest_url( null, sprintf( '/pterotype/v1/object/%d', $object_id ) );
    $object_likes = get_rest_url( null, sprintf( '/pterotype/v1/object/%d/likes', $object_id ) );
    $object_shares = get_rest_url(
        null, sprintf( '/pterotype/v1/object/%d/shares', $object_id )
    );
    $object['id'] = $object_url;
    $object['likes'] = $object_likes;
    $object['shares'] = $object_shares;
    $res = $wpdb->update(
        $wpdb->prefix . 'pterotype_objects',
        array (
            'activitypub_id' => $object_url,
            'type' => $type,
            'object' => wp_json_encode( $object )
        ),
        array( 'id' => $object_id ),
        '%s',
        '%d'
    );
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to hydrate object id', 'pterotype' )
        );
    }
    return $object;
}

function upsert_object( $object ) {
    global $wpdb;
    $object = \pterotype\util\dereference_object( $object );
    if ( is_wp_error( $object ) ) {
        return $object;
    }
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Objects must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'type', $object) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have a "type" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pterotype_objects WHERE activitypub_id = %s",
        $object['id']
    ) );
    $res = true;
    if ( $row === null ) {
        $res = $wpdb->insert(
            $wpdb->prefix . 'pterotype_objects',
            array(
                'activitypub_id' => $object['id'],
                'type' => $object['type'],
                'object' => wp_json_encode( $object )
            ),
            '%s'
        );
        $row = new \stdClass();
    } else {
        $res = $wpdb->update(
            $wpdb->prefix . 'pterotype_objects',
            array(
                'activitypub_id' => $object['id'],
                'type' => $object['type'],
                'object' => wp_json_encode( $object )
            ),
            array( 'id' => $row->id ),
            '%s',
            '%d'
        );
        $id = $row->id;
        $row = new \stdClass();
        $row->id = $id;
        update_referencing_activities( $object );
    }
    if ( $res === false ) {
        return new \WP_Error(
            'db_error', __( 'Failed to upsert object row', 'pterotype' )
        );
    }
    $row->object = $object;
    return $row;
}

function update_object( $object ) {
    global $wpdb;
    $object = \pterotype\util\dereference_object( $object );
    if ( is_wp_error( $object ) ) {
        return $object;
    }
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'type', $object) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have a "type" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object_json = wp_json_encode( $object );
    $res = $wpdb->update(
        $wpdb->prefix . 'pterotype_objects',
        array( 'object' => $object_json, 'type' => $object['type'] ),
        array( 'activitypub_id' => $object['id'] ),
        '%s', '%s' );
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to update object row', 'pterotype' )
        );
    }
    update_referencing_activities( $object );
    return $object;
}

function update_referencing_activities( $object ) {
    global $wpdb;
    $referencing_activities = $wpdb->get_results( $wpdb->prepare(
        "
       SELECT * FROM {$wpdb->prefix}pterotype_objects WHERE object->\"$.object.id\" = %s
       ",
        $object['id']
    ) );
    if ( $referencing_activities ) {
        foreach ( $referencing_activities as $activity_row ) {
            $activity = json_decode( $activity_row->object, true );
            $activity['object'] = $object;
            update_object( $activity );
        }
    }
}

function get_object( $id ) {
    global $wpdb;
    $object_json = $wpdb->get_var( $wpdb->prepare(
        "SELECT object FROM {$wpdb->prefix}pterotype_objects WHERE id = %d", $id
    ) ); 
    if ( is_null( $object_json ) ) {
        return new \WP_Error(
            'not_found', __( 'Object not found', 'pterotype' ), array( 'status' => 404 )
        );
    }
    return json_decode( $object_json, true );
}

function get_object_by_activitypub_id( $activitypub_id ) {
    global $wpdb;
    $object_json = $wpdb->get_var( $wpdb->prepare(
        "SELECT object FROM {$wpdb->prefix}pterotype_objects WHERE activitypub_id = %s",
        $activitypub_id
    ) );
    if ( is_null( $object_json ) ) {
        return new \WP_Error(
            'not_found', __( 'Object not found', 'pterotype' ), array( 'status' => 404 )
        );
    }
    return json_decode( $object_json, true );
}

function get_object_row_by_activity_id( $activitypub_id ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pterotype_objects WHERE activitypub_id = %s",
        $activitypub_id
    ) );
    if ( is_null( $row ) ) {
        return new \WP_Error(
            'not_found', __( 'Object not found', 'pterotype' ), array( 'status' => 404 )
        );
    }
    $row->object = json_decode( $row->object, true );
    return $row;
}

function get_object_id( $activitypub_id ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}pterotype_objects WHERE activitypub_id = %s",
        $activitypub_id
    ) );
}

function get_objects_by( $field, $value ) {
    global $wpdb;
    $objects = $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT object FROM {$wpdb->prefix}pterotype_objects
            WHERE object->\"$.$field\" = %s
            ",
            $value
        ),
        ARRAY_A
    );
    if ( ! $objects ) {
        $objects = array();
    }
    return array_map(
        function( $result ) {
            return json_decode( $result['object'], true );
        },
        $objects
    );
}

function get_object_by( $field, $value ) {
    $objects = get_objects_by( $field, $value );
    if ( count( $objects ) === 0 ) {
        return null;
    } else {
        return $objects[0];
    }
}

function delete_object( $object ) {
    global $wpdb;
    $object = \pterotype\util\dereference_object( $object );
    if ( is_wp_error( $object ) ) {
        return $object;
    }
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'type', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have a "type" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $activitypub_id = $object['id'];
    $tombstone = make_tombstone( $object );
    $res = $wpdb->update(
        $wpdb->prefix . 'pterotype_objects',
        array(
            'type' => $tombstone['type'],
            'object' => wp_json_encode( $tombstone ),
        ),
        array( 'activitypub_id' => $activitypub_id ),
        '%s',
        '%s'
    );
    if ( !$res ) {
        return new \WP_Error( 'db_error', __( 'Error deleting object', 'pterotype' ) );
    }
    update_referencing_activities( $tombstone );
    return $tombstone;
}

function make_tombstone( $object ) {
    $tombstone = array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Tombstone',
        'formerType' => $object['type'],
        'id' => $object['id'],
        'deleted' => date( \DateTime::ISO8601, time() ),
    );
    return $tombstone;
}

function is_local_object( $object ) {
    $url = \pterotype\util\get_id( $object );
    if ( !$url ) {
        return false;
    }
    return \pterotype\util\is_local_url( $url );
}

function strip_private_fields( $object ) {
    if ( array_key_exists( 'bto', $object ) ) {
        unset( $object['bto'] );
    }
    if ( array_key_exists( 'bcc', $object ) ) {
        unset( $object['bcc'] );
    }
    return $object;
}
?>
