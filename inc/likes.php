<?php
namespace likes;

function create_likes_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_likes (
            actor_id INT NOT NULL,
            object_url TEXT NOT NULL,

        );
        "
    );
}
?>
