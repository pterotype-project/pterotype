<?php
namespace activities\delete;

require_once plugin_dir_path( __FILE__ ) . '../objects.php';
require_once plugin_dir_path( __FILE__ ) . '../actors.php';

function handle_outbox( $actor, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    $tombstone = \objects\delete_object( $object );
    if ( is_wp_error( $tombstone ) ) {
        return $tombstone;
    }
    $activity['object'] = $tombstone;
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

function make_delete( $actor_slug, $object ) {
    $actor = \actors\get_actor_by_slug( $actor_slug );
    if ( is_wp_error( $actor ) ) {
        return $actor;
    }
    return array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Delete',
        'actor' => $actor,
        'object' => $object
    );
}
?>
