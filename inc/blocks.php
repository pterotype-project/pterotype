<?php
namespace blocks;

/*
If an actor is in another actor's block list, any activities
from the blocked actor that go into the blocking actors' inbox 
get ignored
*/

function create_block( $actor_id, $blocked_actor_url ) {
    global $wpdb;
    $res = $wpdb->insert(
        'pterotype_activitypub_blocks',
        array( 'actor_id' => $actor_id, 'blocked_actor_url' => $blocked_actor_url )
    );
    if ( !$res ) {
        return new \WP_Error( 'db_error', __( 'Error inserting block row', 'pterotype' ) );
    }
}

function create_blocks_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS pterotype_activitypub_blocks(
            actor_id INT UNSIGNED NOT NULL,
            blocked_actor_url TEXT NOT NULL,
            FOREIGN KEY blocks_actor_fk(actor_id)
                REFERENCES pterotype_activitypub_actors(id)
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8;
        "
    );
}
?>
