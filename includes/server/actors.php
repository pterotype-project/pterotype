<?php
namespace actors;

function get_actor( $id ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        'SELECT * FROM pterotype_actors WHERE id = %d', $id
    ) );
    return get_user_from_row( $row );
}

function get_actor_by_slug ( $slug ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        'SELECT * FROM pterotype_actors WHERE slug = %s', $slug
    ) );
    return get_actor_from_row( $row );
}

function get_actor_id( $slug ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        'SELECT id FROM pterotype_actors WHERE slug = %s', $slug
    ) );
}

function get_actor_from_row( $row ) {
    if ( !$row ) {
        return new \WP_Error(
            'not_found', __( 'Actor not found', 'pterotype' ), array( 'status' => 404 )
        );
    }
    switch ( $row->type ) {
    case 'blog':
        return get_blog_actor();
    case 'user':
        $user = get_user_by( 'slug', $row->slug );
        return get_user_actor( $user );
    case 'commenter':
        return new \WP_Error(
            'not_implemented', __( 'Commenter actors not yet implemented', 'pterotype' )
        );
    }
}

function get_blog_actor() {
    $actor = array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Organization',
        'id' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'following' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/following', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'followers' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/followers', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'liked' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/liked', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'inbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/inbox', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'outbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/outbox', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'name' => get_bloginfo( 'name' ),
        // TODO in the future, make this configurable, both here and in the Webfinger handler
        'preferredUsername' => PTEROTYPE_BLOG_ACTOR_USERNAME,
        'summary' => get_bloginfo( 'description' ),
        'url' => network_site_url( '/' ),
    );
    if ( has_custom_logo() ) {
        $actor['icon'] = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ) )[0];
    }
    return $actor;
}

function get_user_actor( $user ) {
    $handle = get_the_author_meta( 'user_nicename', $user->get('ID'));
    $actor = array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Person',
        'id' => get_rest_url( null, sprintf( '/pterotype/v1/actor/%s', $handle ) ),
        'following' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/following', $handle ) ),
        'followers' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/followers', $handle ) ),
        'liked' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/liked', $handle ) ),
        'inbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/inbox', $handle ) ),
        'outbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/outbox', $handle ) ),
        'preferredUsername' => $handle,
        'name' => get_the_author_meta( 'display_name', $user->get('ID') ),
        'summary' => get_the_author_meta( 'description', $user->get('ID') ),
        'icon' => get_avatar_url( $user->get('ID') ),
        'url' => get_the_author_meta( 'user_url', $user->get('ID') ),
    );
    return $actor;
}

function initialize_actors() {
    global $wpdb;
    $user_slugs = $wpdb->get_col( 
        'SELECT user_nicename FROM wp_users;'
    );
    foreach ( $user_slugs as $user_slug ) {
        create_actor( $user_slug, 'user' );
    }
    create_actor( PTEROTYPE_BLOG_ACTOR_SLUG, 'blog' );
}

function create_actor( $slug, $type ) {
    global $wpdb;
    return $wpdb->query( $wpdb->prepare(
        'INSERT IGNORE INTO pterotype_actors(slug, type) VALUES(%s, %s)',
        $slug,
        $type
    ) );
}
?>
