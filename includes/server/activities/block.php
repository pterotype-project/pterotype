<?php
namespace pterotype\activities\block;

require_once plugin_dir_path( __FILE__ ) . '../blocks.php';
require_once plugin_dir_path( __FILE__ ) . '../actors.php';
require_once plugin_dir_path( __FILE__ ) . '../../util.php';

function handle_outbox( $actor, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $actor_id = \pterotype\actors\get_actor_id( $actor );
    $object = $activity['object'];
    $res = \pterotype\blocks\create_block( $actor_id, $object );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $activity;
}
?>
