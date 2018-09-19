<?php
namespace likes;

function create_like( $actor_id, $object_id ) {
    global $wpdb;
    return $wpdb->insert(
        'pterotype_activitypub_likes', array( 'actor_id' => $actor_id, 'object_id' => $object_id )
    );
}
?>
