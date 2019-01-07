<?php
namespace pterotype\actors;

require_once plugin_dir_path( __FILE__ ) . '../pgp.php';
require_once plugin_dir_path( __FILE__ ) . 'objects.php';
require_once plugin_dir_path( __FILE__ ) . '../admin/settings.php';

function get_actor( $id ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pterotype_actors WHERE id = %d", $id
    ) );
    return get_actor_from_row( $row );
}

function get_all_actors() {
    global $wpdb;
    $results = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}pterotype_actors"
    );
    if ( ! $results || empty( $results ) ) {
        return array();
    }
    $actors = array();
    foreach ( $results as $row ) {
        $actor = get_actor_from_row( $row );
        $actors[$row->slug] = $actor;
    }
    return $actors;
}

function get_actor_by_slug ( $slug ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pterotype_actors WHERE slug = %s", $slug
    ) );
    return get_actor_from_row( $row );
}

function get_actor_row_by_slug ( $slug ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pterotype_actors WHERE slug = %s", $slug
    ) );
    return $row;
}

function get_actor_id( $slug ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}pterotype_actors WHERE slug = %s", $slug
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
        return get_commenter_actor( $row );
    }
}

function get_commenter_actor( $row ) {
    $slug = $row->slug;
    $actor_id = get_actor_id( $slug );
    $email_address = $row->email;
    $actor = array(
        '@context' => array(
            'https://www.w3.org/ns/activitystreams',
            'https://w3id.org/security/v1',
            array(
                'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
            ),
        ),
        'type' => 'Person',
        'id' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s', $slug )
        ),
        'following' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/following', $slug )
        ),
        'followers' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/followers', $slug )
        ),
        'liked' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/liked', $slug )
        ),
        'inbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/inbox', $slug )
        ),
        'outbox' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s/outbox', $slug )
        ),
        'preferredUsername' => $slug,
        'publicKey' => array(
            'id' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s#publicKey', $slug )
            ),
            'owner' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s', $slug )
            ),
            'publicKeyPem' => \pterotype\pgp\get_public_key( $actor_id ),
        ),
        'manuallyApprovesFollowers' => false,
    );
    if ( ! empty( $row->name ) ) {
        $actor['name'] = $row->name;
    } else {
        $actor['name'] = $row->email;
    }
    if ( ! empty( $row->url ) ) {
        $actor['url'] = $row->url;
    }
    if ( ! empty( $row->icon ) ) {
        $actor['icon'] = make_icon_array( $row->icon );
    }
    return $actor;
}

function get_blog_actor() {
    $actor_id = get_actor_id( PTEROTYPE_BLOG_ACTOR_SLUG );
    $actor = array(
        '@context' => array(
            'https://www.w3.org/ns/activitystreams',
            'https://w3id.org/security/v1',
            array(
                'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
            ),
        ),
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
        'name' => \pterotype\settings\get_blog_name_value(),
        // TODO in the future, make this configurable, both here and in the Webfinger handler
        'preferredUsername' => PTEROTYPE_BLOG_ACTOR_USERNAME,
        'summary' => \pterotype\settings\get_blog_description_value(),
        'url' => network_site_url( '/' ),
        'publicKey' => array(
            'id' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s#publicKey', PTEROTYPE_BLOG_ACTOR_SLUG )
            ),
            'owner' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s', PTEROTYPE_BLOG_ACTOR_SLUG )
            ),
            'publicKeyPem' => \pterotype\pgp\get_public_key( $actor_id ),
        ),
        'manuallyApprovesFollowers' => false,
    );
    $icon = \pterotype\settings\get_blog_icon_value();
    if ( $icon && ! empty( $icon ) ) {
        $actor['icon'] = make_icon_array( $icon );
    }
    return $actor;
}

function get_user_actor( $user ) {
    $handle = get_the_author_meta( 'user_nicename', $user->get('ID'));
    $actor_id = get_actor_id( $handle );
    $actor = array(
        '@context' => array(
            'https://www.w3.org/ns/activitystreams',
            'https://w3id.org/security/v1',
            array(
                'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
            ),
        ),
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
        'icon' => make_icon_array( get_avatar_url( $user->get('ID') ) ),
        'url' => get_the_author_meta( 'user_url', $user->get('ID') ),
        'publicKey' => array(
            'id' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s#publicKey', $handle )
            ),
            'owner' => get_rest_url(
                null, sprintf( '/pterotype/v1/actor/%s', $handle )
            ),
            'publicKeyPem' => \pterotype\pgp\get_public_key( $actor_id ),
        ),
        'manuallyApprovesFollowers' => false,
    );
    return $actor;
}

function make_icon_array( $icon_url ) {
    $filetype = wp_check_filetype( $icon_url );
    $mime_type = $filetype['type'];
    return array(
        'url' => $icon_url,
        'type' => 'Image',
        'mediaType' => $mime_type,
    );
}

function initialize_actors() {
    global $wpdb;
    $user_slugs = $wpdb->get_col( 
        "SELECT user_nicename FROM {$wpdb->users};"
    );
    foreach ( $user_slugs as $user_slug ) {
        create_actor( $user_slug, 'user' );
        $actor_id = get_actor_id( $user_slug );
        $keys_created = \pterotype\pgp\get_public_key( $actor_id );
        if ( ! $keys_created ) {
            $keys = \pterotype\pgp\gen_key( $user_slug );
            \pterotype\pgp\persist_key( $actor_id, $keys['publickey'], $keys['privatekey'] );
        }
    }
    create_actor( PTEROTYPE_BLOG_ACTOR_SLUG, 'blog' );
    $blog_actor_id = get_actor_id( PTEROTYPE_BLOG_ACTOR_SLUG );
    $keys_created = \pterotype\pgp\get_public_key( $blog_actor_id );
    if ( ! $keys_created ) {
        $keys = \pterotype\pgp\gen_key( PTEROTYPE_BLOG_ACTOR_SLUG );
        \pterotype\pgp\persist_key( $blog_actor_id, $keys['publickey'], $keys['privatekey'] );
    }
}

function create_actor( $slug, $type, $email = null, $url = null, $name = null, $icon = null ) {
    global $wpdb;
    $res = $wpdb->query( get_create_actor_query( $slug, $type, $email, $url, $name, $icon ) );
    if ( $res === false ) {
        return new \WP_Error(
            'db_error',
            __( 'Error creating actor', 'pterotype' )
        );
    }
    $actor = get_actor_by_slug( $slug );
    $res = \pterotype\objects\upsert_object( $actor );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $res->object;
}

function get_create_actor_query( $slug, $type, $email = null, $url = null, $name = null, $icon = ull ) {
    global $wpdb;
    $query = "INSERT IGNORE INTO {$wpdb->prefix}pterotype_actors(slug, type";
    $args = array( $slug, $type );
    if ( $email ) {
        $query = $query . ", email";
        $args[] = $email;
    }
    if ( $url ) {
        $query = $query . ", url";
        $args[] = $url;
    }
    if ( $name ) {
        $query = $query . ", name";
        $args[] = $name;
    }
    if ( $icon ) {
        $query = $query . ", icon";
        $args[] = $icon;
    }
    $query = $query . ") VALUES (";
    $placeholders = join( ',', array_map( function( $el ) { return '%s'; }, $args ) );
    $query = $query . $placeholders . ")";
    return $wpdb->prepare( $query, $args );
}

function upsert_commenter_actor( $email_address, $url = null, $name = null, $icon = null ) {
    global $wpdb;
    $slug = name_to_slug( $name );
    $existing = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pterotype_actors WHERE slug = %s",
        $slug
    ) );
    if ( $existing !== null ) {
        return $slug;
    }
    $res = create_actor( $slug, 'commenter', $email_address, $url, $name, $icon );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    $actor_id = get_actor_id( $slug );
    $keys_created = \pterotype\pgp\get_public_key( $actor_id );
    if ( ! $keys_created ) {
        $keys = \pterotype\pgp\gen_key( $slug );
        \pterotype\pgp\persist_key( $actor_id, $keys['publickey'], $keys['privatekey'] );
    }
    $actor = get_actor_by_slug( $slug );
    $res = \pterotype\objects\upsert_object( $actor );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $slug;
}

function name_to_slug( $name ) {
    if ( ! $name ) {
        return 'anonymous';
    }
    $slug = str_replace( array( '@', '.', ' '), '_', $name );
    return strtolower( preg_replace( '/[^a-zA-Z0-9-_]/', '', $slug ) );
}
?>
