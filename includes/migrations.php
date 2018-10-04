<?php
/*
Poor man's migration system
*/
namespace migrations;

function get_previous_version() {
    $previous_version = get_option( 'pterotype_previously_migrated_version' );
    if ( !$previous_version ) {
        $previous_version = '0.0.0';
    }
    return $previous_version;
}

/*
It's okay to add new queries to this function, but don't ever delete queries.
*/
function run_migrations() {
    $previous_version = get_previous_version();
    if ( version_compare( $previous_version, PTEROTYPE_VERSION, '>=' ) ) {
        return;
    }
    apply_migration( '0.0.1', 'migration_0_0_1' );
    apply_migration( '0.0.2', 'migration_0_0_2' );
    apply_migration( '0.0.3', 'migration_0_0_3' );
    apply_migration( '0.0.4', 'migration_0_0_4' );
    apply_migration( '0.0.5', 'migration_0_0_5' );
    update_option( 'pterotype_previously_migrated_version', PTEROTYPE_VERSION );
}

function apply_migration( $version, $migration_func ) {
    $previous_version = get_previous_version();
    if ( version_compare( $previous_version, $version, '<' ) ) {
        call_user_func( __NAMESPACE__ . '\\' . $migration_func );
    }
}

function migration_0_0_1() {
    global $wpdb;
    $wpdb->query(
        "
       CREATE TABLE pterotype_activities (
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
       ON pterotype_activities (activitypub_id);
       "
    );
    $wpdb->query(
        "
       CREATE TABLE pterotype_objects (
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
       ON pterotype_objects (activitypub_id);
       "
    );
    $wpdb->query(
        "
       CREATE TABLE pterotype_outbox (
           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
           actor_id INT UNSIGNED NOT NULL,
           activity_id INT UNSIGNED NOT NULL,
           FOREIGN KEY outbox_activity_fk(activity_id)
               REFERENCES pterotype_activities(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE pterotype_actors(
           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
           slug VARCHAR(64) UNIQUE NOT NULL,
           type VARCHAR(64) NOT NULL
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE pterotype_likes (
           actor_id INT UNSIGNED NOT NULL,
           object_id INT UNSIGNED NOT NULL,
           PRIMARY KEY (actor_id, object_id),
           FOREIGN KEY likes_actor_fk(actor_id)
               REFERENCES pterotype_actors(id),
           FOREIGN KEY likes_object_fk(object_id)
               REFERENCES pterotype_objects(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE pterotype_following(
           actor_id INT UNSIGNED NOT NULL,
           object_id INT UNSIGNED NOT NULL,
           state VARCHAR(64) NOT NULL,
           PRIMARY KEY (actor_id, object_id),
           FOREIGN KEY following_actor_fk(actor_id)
               REFERENCES pterotype_actors(id),
           FOREIGN KEY following_object_fk(object_id)
               REFERENCES pterotype_objects(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE pterotype_blocks(
           actor_id INT UNSIGNED NOT NULL,
           blocked_actor_url TEXT NOT NULL,
           FOREIGN KEY blocks_actor_fk(actor_id)
               REFERENCES pterotype_actors(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE pterotype_inbox (
           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
           actor_id INT UNSIGNED NOT NULL,
           activity_id INT UNSIGNED NOT NULL,
           FOREIGN KEY inbox_activity_fk(activity_id)
               REFERENCES pterotype_activities(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
}

function migration_0_0_2() {
    global $wpdb;
    $wpdb->query(
        "
       ALTER TABLE pterotype_objects 
           MODIFY object JSON NOT NULL,
           ADD type VARCHAR(50) NOT NULL;
       "
    );
    $wpdb->query(
        "
       ALTER TABLE pterotype_activities
           MODIFY activity JSON NOT NULL,
           ADD type VARCHAR(50) NOT NULL;
       "
    );
}

function migration_0_0_3() {
    global $wpdb;
    $wpdb->query(
        "
       CREATE TABLE pterotype_followers(
           actor_id INT UNSIGNED NOT NULL,
           object_id INT UNSIGNED NOT NULL,
           PRIMARY KEY (actor_id, object_id),
           FOREIGN KEY followers_actor_fk(actor_id)
               REFERENCES pterotype_actors(id),
           FOREIGN KEY followers_object_fk(object_id)
               REFERENCES pterotype_objects(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE pterotype_shares(
           object_id INT UNSIGNED NOT NULL,
           announce_id INT UNSIGNED NOT NULL,
           PRIMARY KEY (object_id, announce_id),
           FOREIGN KEY shares_object_fk(object_id)
               REFERENCES pterotype_objects(id),
           FOREIGN KEY shares_activity_fk(announce_id)
               REFERENCES pterotype_activities(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
}

function migration_0_0_4() {
    global $wpdb;
    /*
    pterotype_actor_likes stores things that local actors have liked
    */
    $wpdb->query(
        "
       ALTER TABLE pterotype_likes RENAME pterotype_actor_likes;
       "
    );
    /*
    pterotype_object_likes stores likes about local objects
    */
    $wpdb->query(
        "
       CREATE TABLE pterotype_object_likes(
           object_id INT UNSIGNED NOT NULL,
           like_id INT UNSIGNED NOT NULL,
           PRIMARY KEY (object_id, like_id),
           FOREIGN KEY o_likes_object_fk(object_id)
               REFERENCES pterotype_objects(id),
           FOREIGN KEY o_likes_activity_fk(like_id)
               REFERENCES pterotype_activities(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
}

function migration_0_0_5() {
    global $wpdb;
    $wpdb->query(
        '
       CREATE TABLE pterotype_keys(
           actor_id INT UNSIGNED PRIMARY KEY,
           public_key TEXT NOT NULL,
           private_key TEXT NOT NULL,
           FOREIGN KEY keys_actor_fk(actor_id)
               REFERENCES pterotype_actors(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       '
    );
}
?>
