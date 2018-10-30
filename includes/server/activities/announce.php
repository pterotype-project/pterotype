<?php
namespace pterotype\activities\announce;

require_once plugin_dir_path( __FILE__ ) . '../objects.php';
require_once plugin_dir_path( __FILE__ ) . '../shares.php';
require_once plugin_dir_path( __FILE__ ) . '../../util.php';

function handle_inbox( $actor_slug, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an "object" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = \pterotype\util\dereference_object( $activity['object'] );
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !\pterotype\objects\is_local_object( $object ) ) {
        return $activity;
    }
    $object_id = \pterotype\objects\get_object_id( $object['id'] );
    if ( !$object_id ) {
        return new \WP_Error(
            'not_found',
            __( 'Object not found', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    $activity_id = \activities\get_activity_id( $activity['id'] );
    if ( !$activity_id ) {
        return new \WP_Error(
            'not_found',
            __( 'Activity not found', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    \pterotype\shares\add_share( $object_id, $activity_id );
    return $activity;
}
?>
