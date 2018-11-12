<?php
namespace pterotype\schema;

require_once plugin_dir_path( __FILE__ ) . 'client/identity.php';
require_once plugin_dir_path( __FILE__ ) . 'server/activities/delete.php';
require_once plugin_dir_path( __FILE__ ) . 'server/actors.php';

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
    apply_migration( '1.1.1', 'migration_1_1_1' );
    apply_migration( '1.2.0', 'migration_1_2_0' );
    apply_migration( '1.2.1', 'migration_1_2_1' );
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
           object TEXT NOT NULL
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
    global $wpdb;
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

function migration_1_1_1() {
    global $wpdb;
    $wpdb->query(
        "
        ALTER TABLE {$wpdb->prefix}pterotype_actors
            ADD email VARCHAR(255),
            ADD url VARCHAR(255),
            ADD name VARCHAR(255),
            ADD icon VARCHAR(255);
        "
    );
}

function migration_1_2_0() {
    global $wpdb;
    $wpdb->query(
        "
        ALTER TABLE {$wpdb->prefix}pterotype_objects
            MODIFY object TEXT NOT NULL,
            ADD url VARCHAR(255);
        "
    );
    // Migrate existing objects to use the new url field
    $objects = $wpdb->get_results(
        "SELECT activitypub_id, object FROM {$wpdb->prefix}pterotype_objects",
        OBJECT_K
    );
    if ( ! $objects || empty( $objects ) ) {
        return;
    }
    $ids_to_urls = array_map(
        function( $row ) {
            $json = \json_decode( $row->object, true );
            if ( array_key_exists( 'url', $json ) ) {
                return $json['url'];
            }
        },
        $objects
    );
    $ids_to_urls = array_filter( $ids_to_urls );
    $query = "INSERT INTO {$wpdb->prefix}pterotype_objects (activitypub_id, url) VALUES";
    // build values
    foreach( $ids_to_urls as $activitypub_id => $url ) {
        $query = $query . $wpdb->prepare( " (%s, %s),", $activitypub_id, $url );
    }
    $query = substr( $query, 0, -1 );
    $query = $query . " ON DUPLICATE KEY UPDATE url=VALUES(url)";
    $res = $wpdb->query( $query );
    // Compact existing objects so we only store 1 copy of each object
    foreach( $objects as $row ) {
        $object = \json_decode( $row->object, true );
        $updated = $object;
        foreach ( $object as $field => $value ) {
            if ( is_array( $value ) && array_key_exists( 'id', $value ) ) {
                // Insert the child, ignoring if it exists
                $child_url = '';
                if ( array_key_exists( 'url', $value ) ) {
                    $child_url = $value['url'];
                }
                $child_type = '';
                if ( array_key_exists( 'type', $value ) ) {
                    $child_type = $value['type'];
                }
                $wpdb->query( $wpdb->prepare(
                    "
                    INSERT INTO {$wpdb->prefix}pterotype_objects
                        (activitypub_id, type, object, url )
                    VALUES (%s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE activitypub_id = activitypub_id;
                    ",
                    $value['id'], $child_type, wp_json_encode( $value ), $child_url
                ) );
                $updated[$field] = $value['id'];
            }
        }
        $wpdb->update(
            "{$wpdb->prefix}pterotype_objects",
            array( 'object' => wp_json_encode( $updated ) ),
            array( 'activitypub_id' => $row->activitypub_id ),
            '%s', '%s'
        );
    }
}

function migration_1_2_1() {
    \pterotype\identity\update_identity( PTEROTYPE_BLOG_ACTOR_SLUG );
}

function purge_all_data() {
    global $wpdb;
    $actors = \pterotype\actors\get_all_actors();
    foreach ( $actors as $slug => $actor ) {
        $delete = \pterotype\activities\delete\make_delete(
            $slug, $actor
        );
        $delete['to'] = array(
            'https://www.w3.org/ns/activitystreams#Public',
            $actor['followers']
        );
        $server = \rest_get_server();
        $request = \WP_REST_Request::from_url( $actor['outbox'] );
        $request->set_method( 'POST' );
        $request->set_body( wp_json_encode( $delete ) );
        $request->add_header( 'Content-Type', 'application/ld+json' );
        $server->dispatch( $request );
    }
    $pfx = $wpdb->prefix;
    $wpdb->query(
        "DROP INDEX OBJECTS_ACTIVITYPUB_ID_INDEX ON {$pfx}pterotype_objects"
    );
    $wpdb->query(
        "
        DROP TABLE {$pfx}pterotype_comments, {$pfx}pterotype_keys,
            {$pfx}pterotype_blocks, {$pfx}pterotype_shares,
            {$pfx}pterotype_following, {$pfx}pterotype_followers,
            {$pfx}pterotype_actor_likes, {$pfx}pterotype_object_likes,
            {$pfx}pterotype_outbox, {$pfx}pterotype_inbox,
            {$pfx}pterotype_actors, {$pfx}pterotype_objects
        "
    );
    \delete_option( 'pterotype_previously_migrated_version' );
}
?>
