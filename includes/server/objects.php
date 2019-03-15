<?php
namespace pterotype\objects;

require_once plugin_dir_path( __FILE__ ) . '../util.php';

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
    $object = compact_object( $object );
    $res = $wpdb->insert( $wpdb->prefix . 'pterotype_objects', array(
        'object' => wp_json_encode( $object ),
        'activitypub_id' => "uninitialized_" . rand(),
    ) );
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to insert object row', 'pterotype' )
        );
    }
    $object_id = $wpdb->insert_id;
    $type = $object['type'];
    $object_apid = get_rest_url( null, sprintf( '/pterotype/v1/object/%d', $object_id ) );
    $object_likes = get_rest_url( null, sprintf( '/pterotype/v1/object/%d/likes', $object_id ) );
    $object_shares = get_rest_url(
        null, sprintf( '/pterotype/v1/object/%d/shares', $object_id )
    );
    $object['id'] = $object_apid;
    $object['likes'] = $object_likes;
    $object['shares'] = $object_shares;
    $object_url = '';
    if ( array_key_exists( 'url', $object ) ) {
        $object_url = $object['url'];
    }
    $res = $wpdb->update(
        $wpdb->prefix . 'pterotype_objects',
        array (
            'activitypub_id' => $object_apid,
            'type' => $type,
            'object' => wp_json_encode( $object ),
            'url' => $object_url
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
    $object = compact_object( $object );
    $object_url = '';
    if ( array_key_exists( 'url', $object ) ) {
        $object_url = $object['url'];
    }
    $res = $wpdb->query( $wpdb->prepare(
        "
        INSERT INTO {$wpdb->prefix}pterotype_objects (activitypub_id, type, object, url)
            VALUES (%s, %s, %s, %s) ON DUPLICATE KEY UPDATE
                id=LAST_INSERT_ID(id),
                activitypub_id=VALUES(activitypub_id),
                type=VALUES(type),
                object=VALUES(object),
                url=VALUES(url);
        ",
        $object['id'], $object['type'], wp_json_encode( $object ), $object_url
    ) );
    if ( $res === false ) {
        return new \WP_Error(
            'db_error', __( 'Failed to upsert object row', 'pterotype' )
        );
    }
    $row = new \stdClass();
    $row->object = $object;
    $row->id = $wpdb->insert_id;
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
    $object = compact_object( $object );
    $object_json = wp_json_encode( $object );
    $object_url = '';
    if ( array_key_exists( 'url', $object ) ) {
        $object_url = $object['url'];
    }
    $res = $wpdb->update(
        $wpdb->prefix . 'pterotype_objects',
        array( 'object' => $object_json, 'type' => $object['type'], 'url' => $object_url ),
        array( 'activitypub_id' => $object['id'] ),
        '%s', '%s' );
    if ( !$res ) {
        return new \WP_Error(
            'db_error', __( 'Failed to update object row', 'pterotype' )
        );
    }
    return $object;
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
    $object = json_decode( $object_json, true );
    if ( array_key_exists( 'object', $object ) ) {
        $object = \pterotype\util\decompact_object( $object, array( 'object' ) );
    }
    return $object;
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

function get_object_by_url( $url ) {
    global $wpdb;
    $object_json = $wpdb->get_var( $wpdb->prepare(
        "SELECT object FROM {$wpdb->prefix}pterotype_objects WHERE url = %s",
        $url
    ) );
    if ( is_null( $object_json ) ) {
        return $object_json;
    }
    return json_decode( $object_json, true );
}

function get_object_row_by_url( $url ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pterotype_objects WHERE url = %s",
        $url
    ) );
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
            'url' => ''
        ),
        array( 'activitypub_id' => $activitypub_id ),
        '%s',
        '%s'
    );
    if ( !$res ) {
        return new \WP_Error( 'db_error', __( 'Error deleting object', 'pterotype' ) );
    }
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

function create_object_if_not_exists( $object ) {
    global $wpdb;
    if ( ! array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( ! array_key_exists( 'type', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have a "type" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object_url = '';
    if ( array_key_exists( 'url', $object ) ) {
        $object_url = $object['url'];
    }
    $object = compact_object( $object );
    return $wpdb->query( $wpdb->prepare(
        "
        INSERT INTO {$wpdb->prefix}pterotype_objects (activitypub_id, type, object, url)
        VALUES (%s, %s, %s, %s) ON DUPLICATE KEY UPDATE activitypub_id = activitypub_id;
        ",
        $object['id'], $object['type'], wp_json_encode( $object ), $object_url
    ) );
}

function compact_object( $object ) {
    $object = \pterotype\util\dereference_object( $object );
    $compacted = $object;
    foreach( $object as $field => $value ) {
        if ( $field === 'publicKey' ) {
            continue;
        }
        if ( is_array( $value ) && array_key_exists( 'id', $value ) ) {
            $child_object = compact_object( $value );
            create_object_if_not_exists( $child_object );
            $compacted[$field] = $child_object['id'];
        }
    }
    return $compacted;
}
?>
