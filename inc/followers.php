<?php
namespace followers;

require_once plugin_dir_path( __FILE__ ) . '/actors.php';
require_once plugin_dir_path( __FILE__ ) . '/objects.php';

function add_follower( $actor_slug, $follower ) {
    global $wpdb;
    $actor_id = \actors\get_actor_id( $actor_slug ); 
    if ( !$actor_id ) {
        return new \WP_Error(
            'not_found',
            __( 'Actor not found', 'pterotype' ),
            array( 'status' => 404 )
        );
    }
    if ( !array_key_exists( 'id', $follower ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object_id = \objects\get_object_id( $follower['id'] );
    if ( !$object_id ) {
        $row = \objects\upsert_object( $follower );
        $object_id = $row->id;
    }
    $wpdb->insert(
        'pterotype_followers',
        array(
            'actor_id' => $actor_id,
            'object_id' = $object_id,
        );
    );
}
?>
