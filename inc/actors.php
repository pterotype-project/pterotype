<?php
namespace actors;

function get_actor( $id ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        'SELECT * FROM activitypub_actors WHERE id = %d', $id
    ) );
    return get_user_from_row( $row );
}

function get_actor_by_slug ( $slug ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        'SELECT * FROM activitypub_actors WHERE slug = %s', $slug
    ) );
    return get_user_from_row( $row );
}

function get_user_from_row( $row ) {
    switch ( $row->type ) {
    case "user":
        $user = get_user_by( 'slug', $row->slug );
        return get_user_actor( $user );
    case "commenter":
        return new \WP_Error(
            'not_implemented', __( 'Commenter actors not yet implemented', 'activitypub' )
        );
    }
}

function get_user_actor( $user ) {
    $handle = get_the_author_meta( 'user_nicename', $user->get('ID'));
    $actor = array(
        "@context" => array( "https://www.w3.org/ns/activitystreams" ),
        "type" => "Person",
        "id" => get_rest_url( null, sprintf( '/activitypub/v1/actor/%s', $handle ) ),
        "following" => get_rest_url(
            null, sprintf( '/activitypub/v1/actor/%s/following', $handle ) ),
        "followers" => get_rest_url(
            null, sprintf( '/activitypub/v1/actor/%s/followers', $handle ) ),
        "liked" => get_rest_url(
            null, sprintf( '/activitypub/v1/actor/%s/liked', $handle ) ),
        "inbox" => get_rest_url(
            null, sprintf( '/activitypub/v1/actor/%s/inbox', $handle ) ),
        "outbox" => get_rest_url(
            null, sprintf( '/activitypub/v1/actor/%s/outbox', $handle ) ),
        "preferredUsername" => $handle,
        "name" => get_the_author_meta( 'display_name', $user->get('ID') ),
        "summary" => get_the_author_meta( 'description', $user->get('ID') ),
        "icon" => get_avatar_url ( $user->get('ID') ),
        "url" => get_the_author_meta( 'user_url', $user->get('ID') ),
    );
    return $actor;
}

function create_actors_table() {
    global $wpdb;
    $wpdb->query(
        "
        CREATE TABLE IF NOT EXISTS activitypub_actors(
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(255) UNIQUE NOT NULL,
            type VARCHAR(64)
        );
        "
    );
}
?>
