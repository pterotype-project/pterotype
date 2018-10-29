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
        $site_host = parse_url( get_site_url() )['host'];
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

function get_log_dir() {
    return plugin_dir_path( __FILE__ ) . '../log';
}

function log( $log_file, $str, $append = true ) {
    if ( ! WP_DEBUG ) {
        return;
    }
    $log_dir = get_log_dir();
    $log_file = '/' . $log_file;
    if ( ! file_exists( $log_dir ) ) {
        mkdir( $log_dir, 0777, true );
    }
    if ( $append ) {
        file_put_contents( $log_dir . $log_file, $str, FILE_APPEND );
    } else {
        file_put_contents( $log_dir . $log_file, $str );
    }
}

function log_var( $log_file, $var, $append = true ) {
    if ( ! WP_DEBUG ) {
        return;
    }
    ob_start();
    var_dump( $var );
    $dump = ob_get_clean();
    log( $log_file, $dump, $append );
}
?>
