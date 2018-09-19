<?php
namespace actors;

function get_actor( $id ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        'SELECT * FROM pterotype_activitypub_actors WHERE id = %d', $id
    ) );
    return get_user_from_row( $row );
}

function get_actor_by_slug ( $slug ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        'SELECT * FROM pterotype_activitypub_actors WHERE slug = %s', $slug
    ) );
    return get_actor_from_row( $row );
}

function get_actor_id( $slug ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        "SELECT slug FROM pterotype_activitypub_actors WHERE slug = %s", $slug
    ) );
}

function get_actor_from_row( $row ) {
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
        "id" => get_rest_url( null, sprintf( '/pterotype/v1/actor/%s', $handle ) ),
        "following" => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/following', $handle ) ),
        "followers" => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/followers', $handle ) ),
        "liked" => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/liked', $handle ) ),
        "inbox" => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/inbox', $handle ) ),
        "outbox" => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/outbox', $handle ) ),
        "preferredUsername" => $handle,
        "name" => get_the_author_meta( 'display_name', $user->get('ID') ),
        "summary" => get_the_author_meta( 'description', $user->get('ID') ),
        "icon" => get_avatar_url ( $user->get('ID') ),
        "url" => get_the_author_meta( 'user_url', $user->get('ID') ),
    );
    return $actor;
}

/*
For every user in the WP instance, create a new actor row for that user
if it doesn't already exist
*/
function initialize_user_actors() {
    global $wpdb;
    $user_slugs = $wpdb->get_col( 
        "SELECT user_nicename FROM wp_users;"
    );
    foreach ( $user_slugs as $user_slug ) {
        create_actor_from_user( $user_slug );
    }
}

function create_actor_from_user( $user_slug ) {
    global $wpdb;
    $wpdb->query( $wpdb->prepare(
        "INSERT IGNORE INTO pterotype_activitypub_actors(slug, type) VALUES(%s, 'user')", $user_slug
    ) );
}
?>
