<?php
/*
Poor man's migration system
*/
namespace db;

/*
It's okay to add new queries to this function, but don't ever delete queries.
*/
function run_migrations() {
    $previous_version = get_option( 'pterotype_previously_migrated_version' );
    if ( !$previous_version ) {
        $previous_version = '0.0.0';
    }
    if ( version_compare( $previous_version, PTEROTYPE_VERSION, '>=' ) ) {
        return;
    }
    if ( version_compare( $previous_version, '0.0.1', '<' ) ) {
        migration_0_0_1();
    }
    update_option( 'pterotype_previously_migrated_version', PTEROTYPE_VERSION );
}

function migration_0_0_1() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE pterotype_activitypub_activities (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            activitypub_id VARCHAR(255) UNIQUE NOT NULL,
            activity TEXT NOT NULL
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8;
        "
    );
    $wpdb->query(
        "
        CREATE UNIQUE INDEX ACTIVITIES_ACTIVITYPUB_ID_INDEX
        ON pterotype_activitypub_activities (activitypub_id);
        "
    );
    $wpdb->query(
        "
        CREATE TABLE pterotype_activitypub_objects (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            activitypub_id VARCHAR(255) UNIQUE NOT NULL,
            object TEXT NOT NULL
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8;
        "
    );
    $wpdb->query(
        "
        CREATE UNIQUE INDEX OBJECT_ACTIVITYPUB_ID_INDEX
        ON pterotype_activitypub_objects (activitypub_id);
        "
    );
    $wpdb->query(
        "
        CREATE TABLE pterotype_activitypub_outbox (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            actor_id INT UNSIGNED NOT NULL,
            activity_id INT UNSIGNED NOT NULL,
            FOREIGN KEY outbox_activity_fk(activity_id)
                REFERENCES pterotype_activitypub_activities(id)
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8;
        "
    );
    $wpdb->query(
        "
        CREATE TABLE pterotype_activitypub_actors(
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(64) UNIQUE NOT NULL,
            type VARCHAR(64) NOT NULL
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8;
        "
    );
    $wpdb->query(
        "
        CREATE TABLE pterotype_activitypub_likes (
            actor_id INT UNSIGNED NOT NULL,
            object_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (actor_id, object_id),
            FOREIGN KEY likes_actor_fk(actor_id)
                REFERENCES pterotype_activitypub_actors(id),
            FOREIGN KEY likes_object_fk(object_id)
                REFERENCES pterotype_activitypub_objects(id)
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8;
        "
    );
    $wpdb->query(
        "
        CREATE TABLE pterotype_activitypub_following(
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
    $wpdb->query(
        "
        CREATE TABLE pterotype_activitypub_blocks(
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
