<?php
namespace pterotype\likes;

require_once plugin_dir_path( __FILE__ ) . 'collections.php';

function create_local_actor_like( $actor_id, $object_id ) {
    global $wpdb;
    return $wpdb->insert(
        $wpdb->prefix . 'pterotype_actor_likes',
        array( 'actor_id' => $actor_id, 'object_id' => $object_id ),
        '%d'
    );
}

function delete_local_actor_like( $actor_id, $object_id ) {
    global $wpdb;
    return $wpdb->delete(
        $wpdb->prefix . 'pterotype_actor_likes',
        array( 'actor_id' => $actor_id, 'object_id' => $object_id ),
        '%d'
    );
}

function record_like ( $object_id, $like_id ) {
    global $wpdb;
    return $wpdb->insert(
        $wpdb->prefix . 'pterotype_object_likes',
        array(
            'object_id' => $object_id,
            'like_id' => $like_id
        ),
        '%d'
    );
}

function delete_object_like( $object_id, $like_id ) {
    global $wpdb;
    return $wpdb->delete(
        $wpdb->prefix. 'pterotype_object_likes',
        array(
            'object_id' => $object_id,
            'like_id' => $like_id
        ),
        '%d'
    );
}

function get_likes_collection( $object_id ) {
    global $wpdb;
    $likes = $wpdb->get_results(
        $wpdb->prepare(
            "
           SELECT object FROM {$wpdb->prefix}pterotype_object_likes
           JOIN {$wpdb->prefix}pterotype_objects 
           ON like_id = {$wpdb->prefix}pterotype_objects.id
           WHERE object_id = %d
           ",
            $object_id
        ),
        ARRAY_A
    );
    if ( !$likes ) {
        $likes = array();
    }
    $collection = \pterotype\collections\make_ordered_collection( $likes );
    $collection['id'] = get_rest_url( null, sprintf(
        '/pterotype/v1/object/%d/likes', $object_id
    ) );
    return $collection;
}
?>
