<?php
namespace activities\update;

require_once plugin_dir_path( __FILE__ ) . '/../objects.php';

function handle( $actor, $activity ) {
    if ( !(array_key_exists( 'type', $activity ) && $activity['type'] === 'Update') ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expecting an Update activity', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expecting an object', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    $update_object = $activity['object'];
    if ( !array_key_exists( 'id', $update_object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" parameter', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    $existing_object = \objects\get_object_from_url( $update_object['id'] );
    if ( is_wp_error( $existing_object ) ) {
        return $existing_object;
    }
    $updated_object = array_merge( $existing_object, $update_object );
    $updated_object = \objects\update_object( $updated_object );
    if ( is_wp_error( $updated_object ) ) {
        return $updated_object;
    }
    return $activity;
}
?>
