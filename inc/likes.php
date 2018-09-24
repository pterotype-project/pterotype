<?php
namespace likes;

// TODO implement a 'likes' collection for objects -
// implemented similar to/same as 'shares' collection

function create_like( $actor_id, $object_id ) {
    global $wpdb;
    return $wpdb->insert(
        'pterotype_likes', array( 'actor_id' => $actor_id, 'object_id' => $object_id )
    );
}
?>
