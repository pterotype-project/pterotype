<?php
namespace activities\block;

require_once plugin_dir_path( __FILE__ ) . '/../blocks.php';
require_once plugin_dir_path( __FILE__ ) . '/../actors.php';

function handle( $actor, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'activitypub' ),
            array( 'status' => 400 )
        );
    }
    $actor_id = \actors\get_actor_id( $actor );
    $res = \blocks\create_block( $actor_id, $activity['object'] );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $activity;
}
?>
