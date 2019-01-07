<?php
namespace pterotype\util;

require_once plugin_dir_path( __FILE__ ) . 'server/objects.php';

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
            return get_object_from_url_helper( $object['href'], $depth );
        }
        return $object;
    } else if ( filter_var( $object, FILTER_VALIDATE_URL ) ) {
        return get_object_from_url_helper( $object, $depth );
    } else {
        return new \WP_Error(
            'invalid_object',
            __( 'Not a valid ActivityPub object or reference', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
}

function get_object_from_url( $url ) {
    return get_object_from_url_helper( $url, 0 );
}

function get_object_from_url_helper( $url, $depth ) {
    $cached_object = \pterotype\objects\get_object_by_activitypub_id( $url );
    if ( $cached_object && ! is_wp_error( $cached_object ) ) {
        return dereference_object_helper( $cached_object, $depth + 1 );
    }
    if ( is_local_url( $url ) ) {
        return retrieve_local_object( $url );
    }
    $response = wp_remote_get( $url, array(
        'headers' => array(
            'Accept' => 'application/ld+json',
        ),
    ) );
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

function retrieve_local_object( $url ) {
    $server = rest_get_server();
    $request = \WP_REST_Request::from_url( $url );
    if ( ! $request ) {
        return new \WP_Error(
            'not_local_url',
            __( 'Expected a local URL', 'pterotype' )
        );
    }
    $response = $server->dispatch( $request );
    if ( $response->is_error() ) {
        return $response->as_error();
    } else {
        return $response->get_data();
    }
}

function is_local_url( $url ) {
    $parsed = parse_url( $url );
    if ( $parsed ) {
        $parsed_site_url = parse_url( get_site_url() );
        $site_host = $parsed_site_url['host'];
        return $parsed['host'] === $site_host;
    }
    return false;
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

function decompact_object( $object, $fields ) {
    return decompact_object_helper( $object, $fields, 0 );
}

function decompact_object_helper( $object, $fields, $depth ) {
    if ( $depth == 3 ) {
        return $object;
    }
    if ( ! is_array( $object ) ) {
        return $object;
    }
    $decompacted = $object;
    foreach ( $object as $field => $value ) {
        if ( ! in_array( $field, $fields ) ) {
            continue;
        }
        if ( is_array( $value ) ) {
            $decompacted[$field] = decompact_object_helper( $value, $fields, $depth + 1 );
        } else if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
            $decompacted[$field] = decompact_object_helper(
                dereference_object( $value ), $fields, $depth + 1
            );
        }
    }
    return $decompacted;
}
?>
