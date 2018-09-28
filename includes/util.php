<?php
namespace util;

// TODO audit places throughout the repo where I need to dereference objects
// (this is anywhere I access a field on an object, basically)

function dereference_object( $object ) {
    return dereference_object_helper( $object, 0 );
}

function dereference_object_helper( $object, $depth ) {
    if ( $depth === 30 ) {
        return $object;
    }
    if ( is_array( $object ) ) {
        if ( array_key_exists( 'type', $object ) &&
             $object['type'] === 'Link' &&
             array_key_exists( 'href', $object ) &&
             filter_var( $object['href'], FILTER_VALIDATE_URL ) ) {
            return get_object_from_url( $object['href'], $depth );
        }
        return $object;
    } else if ( filter_var( $object, FILTER_VALIDATE_URL ) ) {
        return get_object_from_url( $object, $depth );
    } else {
        return new \WP_Error(
            'invalid_object',
            __( 'Not a valid ActivityPub object or reference', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
}

function get_object_from_url( $url, $depth ) {
    $response = wp_remote_get( $url );
    if ( is_wp_error( $response ) ) {
        return $response;
    }
    $body = wp_remote_retrieve_body( $response );
    if ( empty( $body ) ) {
        return new \WP_Error(
            'not_found',
            __( 'The object did not dereference to a valid object', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    $body_array = json_decode( $body, true );
    return dereference_object_helper( $body_array, $depth + 1 );
}

function is_same_object( $object1, $object2 ) {
    return get_id( $object1 ) === get_id( $object2 );
}

function get_id( $object ) {
    if ( is_array( $object ) ) {
        return array_key_exists( 'id', $object ) ?
            $object['id'] :
            null;
    } else {
        return $object;
    }
}
?>
