<?php
namespace pterotype\schema;

function get_previous_version() {
    $previous_version = get_option( 'pterotype_previously_migrated_version' );
    if ( !$previous_version ) {
        $previous_version = '0.0.0';
    }
    return $previous_version;
}

function run_migrations() {
    $previous_version = get_previous_version();
    if ( version_compare( $previous_version, PTEROTYPE_VERSION, '>=' ) ) {
        return;
    }
    apply_migration( '0.0.1', 'migration_0_0_1' );
    apply_migration( '1.1.0', 'migration_1_1_0' );
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
       CREATE TABLE {$wpdb->prefix}pterotype_objects (
           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
           activitypub_id VARCHAR(255) UNIQUE NOT NULL,
           type VARCHAR(50) NOT NULL,
           object JSON NOT NULL
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE UNIQUE INDEX OBJECTS_ACTIVITYPUB_ID_INDEX
       ON {$wpdb->prefix}pterotype_objects (activitypub_id);
       "
    );
    $wpdb->query(
        "
       CREATE TABLE {$wpdb->prefix}pterotype_actors (
           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
           slug VARCHAR(64) UNIQUE NOT NULL,
           type VARCHAR(64) NOT NULL
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE {$wpdb->prefix}pterotype_outbox (
           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
           actor_id INT UNSIGNED NOT NULL,
           object_id INT UNSIGNED NOT NULL,
           FOREIGN KEY outbox_object_fk(object_id)
               REFERENCES {$wpdb->prefix}pterotype_objects(id),
           FOREIGN KEY outbox_actor_fk(actor_id)
               REFERENCES {$wpdb->prefix}pterotype_actors(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE {$wpdb->prefix}pterotype_inbox (
           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
           actor_id INT UNSIGNED NOT NULL,
           object_id INT UNSIGNED NOT NULL,
           FOREIGN KEY inbox_object_fk(object_id)
               REFERENCES {$wpdb->prefix}pterotype_objects(id),
           FOREIGN KEY inbox_actor_fk(actor_id)
               REFERENCES {$wpdb->prefix}pterotype_actors(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE {$wpdb->prefix}pterotype_actor_likes (
           actor_id INT UNSIGNED NOT NULL,
           object_id INT UNSIGNED NOT NULL,
           PRIMARY KEY (actor_id, object_id),
           FOREIGN KEY a_likes_actor_fk(actor_id)
               REFERENCES {$wpdb->prefix}pterotype_actors(id),
           FOREIGN KEY a_likes_object_fk(object_id)
               REFERENCES {$wpdb->prefix}pterotype_objects(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE {$wpdb->prefix}pterotype_object_likes (
           object_id INT UNSIGNED NOT NULL,
           like_id INT UNSIGNED NOT NULL,
           PRIMARY KEY (object_id, like_id),
           FOREIGN KEY o_likes_object_fk(object_id)
               REFERENCES {$wpdb->prefix}pterotype_objects(id),
           FOREIGN KEY o_likes_like_fk(like_id)
               REFERENCES {$wpdb->prefix}pterotype_objects(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE {$wpdb->prefix}pterotype_following (
           actor_id INT UNSIGNED NOT NULL,
           object_id INT UNSIGNED NOT NULL,
           state VARCHAR(64) NOT NULL,
           PRIMARY KEY (actor_id, object_id),
           FOREIGN KEY following_actor_fk(actor_id)
               REFERENCES {$wpdb->prefix}pterotype_actors(id),
           FOREIGN KEY following_object_fk(object_id)
               REFERENCES {$wpdb->prefix}pterotype_objects(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE {$wpdb->prefix}pterotype_followers (
           actor_id INT UNSIGNED NOT NULL,
           object_id INT UNSIGNED NOT NULL,
           PRIMARY KEY (actor_id, object_id),
           FOREIGN KEY followers_actor_fk(actor_id)
               REFERENCES {$wpdb->prefix}pterotype_actors(id),
           FOREIGN KEY followers_object_fk(object_id)
               REFERENCES {$wpdb->prefix}pterotype_objects(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE {$wpdb->prefix}pterotype_blocks (
           actor_id INT UNSIGNED NOT NULL,
           blocked_actor_url TEXT NOT NULL,
           FOREIGN KEY blocks_actor_fk(actor_id)
               REFERENCES {$wpdb->prefix}pterotype_actors(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE {$wpdb->prefix}pterotype_shares (
           object_id INT UNSIGNED NOT NULL,
           announce_id INT UNSIGNED NOT NULL,
           PRIMARY KEY (object_id, announce_id),
           FOREIGN KEY shares_object_fk(object_id)
               REFERENCES {$wpdb->prefix}pterotype_objects(id),
           FOREIGN KEY shares_announce_fk(announce_id)
               REFERENCES {$wpdb->prefix}pterotype_objects(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
    $wpdb->query(
        "
       CREATE TABLE {$wpdb->prefix}pterotype_keys (
           actor_id INT UNSIGNED PRIMARY KEY,
           public_key TEXT NOT NULL,
           private_key TEXT NOT NULL,
           FOREIGN KEY keys_actor_fk(actor_id)
               REFERENCES {$wpdb->prefix}pterotype_actors(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
}

function migration_1_1_0() {
    $wpdb->query(
        "
       CREATE TABLE {$wpdb->prefix}pterotype_comments (
           comment_id BIGINT(20) UNSIGNED NOT NULL,
           object_id INT UNSIGNED NOT NULL,
           PRIMARY KEY (comment_id, object_id),
           FOREIGN KEY pt_comments_comment_fk(comment_id)
               REFERENCES {$wpdb->comments}(comment_ID),
           FOREIGN KEY pt_comments_object_fk(object_id)
               references {$wpdb->prefix}pterotype_objects(id)
       )
       ENGINE=InnoDB DEFAULT CHARSET=utf8;
       "
    );
}
?>
