<?php
namespace activities\follow;

require_once plugin_dir_path( __FILE__ ) . '/../following.php';
require_once plugin_dir_path( __FILE__ ) . '/../actors.php';
require_once plugin_dir_path( __FILE__ ) . '/../objects.php';

function handle_outbox( $actor, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    $object_row = \objects\upsert_object( $object );
    $actor_id = \actors\get_actor_id( $actor );
    $res = \following\request_follow( $actor_id, $object_row->id );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $activity;
}
?>
