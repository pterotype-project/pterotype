<?php
namespace activities\delete;

require_once plugin_dir_path( __FILE__ ) . '/../objects.php';

function handle_outbox( $actor, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    $res = \objects\delete_object( $object );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $activity;
}

function handle_inbox( $actor_slug, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'id', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an id', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an id', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $authorized = check_authorization( $activity );
    if ( is_wp_error( $authorized ) ) {
        return $authorized;
    }
    $res = \objects\delete_object( $object );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $activity;
}

function check_authorization( $activity ) {
    $object = $activity['object'];
    $activity_origin = parse_url( $activity['id'] )['host'];
    $object_origin = parse_url( $object['id'] )['host'];
    if ( ( !$activity_origin || !$object_origin ) || $activity_origin !== $object_origin ) {
        return new \WP_Error(
            'unauthorized',
            __( 'Unauthorized Update activity', 'pterotype' ),
            array( 'status' => 403 )
        );
    }
    return true;
}
?>
