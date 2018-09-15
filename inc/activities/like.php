<?php
namespace activities\like;

require_once plugin_dir_path( __FILE__ ) . '/../likes.php';
require_once plugin_dir_path( __FILE__ ) . '/../actors.php';

function handle_outbox( $actor, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Expected an object id', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    $object_id = $object['id'];
    $actor_id = \actors\get_actor_id( $actor );
    $res = \likes\create_like( $actor_id, $object_id );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $activity;
}
?>
