<?php
namespace following;

$PENDING = 'PENDING';
$FOLLOWING = 'FOLLOWING';

function request_follow( $actor_id, $object_id ) {
    global $wpdb;
    return $wpdb->insert(
        'pterotype_activitypub_following',
        array( 'actor_id' => $actor_id,
               'object_id' => wp_json_encode( $object ),
               'state' => $PENDING
        )
    );
}

function create_following_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS pterotype_activitypub_following(
            actor_id INT UNSIGNED NOT NULL,
            object_id INT UNSIGNED NOT NULL,
            state VARCHAR(64) NOT NULL,
            PRIMARY KEY (actor_id, object_id),
            FOREIGN KEY following_actor_fk(actor_id)
                REFERENCES pterotype_activitypub_actors(id),
            FOREIGN KEY following_object_fk(object_id)
                REFERENCES pterotype_activitypub_objects(id)
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8;
        "
    );
}
?>
