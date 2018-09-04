<?php
namespace activities\delete;

require_once plugin_dir_path( __FILE__ ) . '/../objects.php';

function handle( $actor, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'activitypub' ),
            array( 'status' => 40 )
        );
    }
    $object = $activity['object'];
    $res = \objects\delete_object( $object );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $activity;
}
?>
