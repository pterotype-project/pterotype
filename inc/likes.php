<?php
namespace likes;

function create_like( $actor_id, $object_id ) {
    global $wpdb;
    return $wpdb->insert(
        'activitypub_likes', array( 'actor_id' => $actor_id, 'object_url' => $object_id )
    );
}

function create_likes_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_likes (
            actor_id INT NOT NULL,
            object_url TEXT NOT NULL,
            FOREIGN KEY actor_fk(actor_id)
            REFERENCES activitypub_actors(id)
        );
        "
    );
}
?>
