<?php
namespace following;

$PENDING = 'PENDING';
$FOLLOWING = 'FOLLOWING';

function request_follow( $actor_id, $object ) {
    global $wpdb;
    return $wpdb->insert(
        'activitypub_following',
        array( 'actor_id' => $actor_id, 'object' => wp_json_encode( $object ) )
    );
}

function create_following_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_following(
            actor_id INT UNSIGNED NOT NULL,
            object TEXT NOT NULL,
            state VARCHAR(64) NOT NULL,
            FOREIGN KEY actor_fk(actor_id)
            REFERENCES activitypub_actors(id)
        );
        "
    );
}
?>
