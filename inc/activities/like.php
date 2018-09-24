<?php
namespace activities\like;

require_once plugin_dir_path( __FILE__ ) . '/../likes.php';
require_once plugin_dir_path( __FILE__ ) . '/../actors.php';
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
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Expected an id', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object_row = \objects\upsert_object( $object );
    $actor_id = \actors\get_actor_id( $actor );
    $res = \likes\create_local_actor_like( $actor_id, $object_row->id );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    if ( \objects\is_local_object( $object ) ) {
        $activity_id = \activities\get_activity_id( $activity['id'] );
        if ( !$activity_id ) {
            return new \WP_Error(
                'not_found',
                __( 'Activity not found', 'pterotype' ),
                array( 'status' => 404 )
            );
        }
        $object_id = $object_row->id;
        \likes\record_like( $object_id, $activity_id );
    }
    return $activity;
}

function handle_inbox( $actor, $activity ) {
    if ( !array_key_exists( 'id', $activity ) ) {
         return new \WP_Error(
            'invalid_activity',
            __( 'Expected an id', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'object', $activity ) ) {
         return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Expected an id', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( \objects\is_local_object( $object ) ) {
        $activity_id = \activities\get_activity_id( $activity['id'] );
        if ( !$activity_id ) {
            return new \WP_Error(
                'not_found',
                __( 'Activity not found', 'pterotype' ),
                array( 'status' => 404 )
            );
        }
        $object_id = \objects\get_object_id( $object['id'] );
        if ( !$object_id ) {
            return new \WP_Error(
                'not_found',
                __( 'Object not found', 'pterotype' ),
                array( 'status' => 404 )
            );
        }
        \likes\record_like( $object_id, $activity_id );
    }
    return $activity;
}
?>
