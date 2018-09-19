<?php
namespace likes;

function create_like( $actor_id, $object_id ) {
    global $wpdb;
    return $wpdb->insert(
        'activitypub_likes', array( 'actor_id' => $actor_id, 'object_id' => $object_id )
    );
}

function create_likes_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_likes (
            actor_id INT UNSIGNED NOT NULL,
            object_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (actor_id, object_id),
            FOREIGN KEY likes_actor_fk(actor_id)
                REFERENCES activitypub_actors(id),
            FOREIGN KEY likes_object_fk(object_id)
                REFERENCES activitypub_objects(id)
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8;
        "
    );
}
?>
