<?php
namespace pterotype\webfinger;

require_once plugin_dir_path( __FILE__ ) . 'actors.php';

function generate_rewrite_rules( $wp_rewrite ) {
    $dot_well_known = array(
        '.well-known/webfinger' => 'index.php?well-known=webfinger'
    );
    $wp_rewrite->rules = $dot_well_known + $wp_rewrite->rules;
}

function parse_request( $req ) {
    if ( ! array_key_exists( 'well-known', $req->query_vars ) ) {
        return;
    }
    if ( $req->query_vars['well-known'] === 'webfinger' ) {
        do_action( 'well_known_webfinger', $req->query_vars );
    }
}

function query_vars( $query_vars ) {
    $query_vars[] = 'well-known';
    $query_vars[] = 'resource';
    return $query_vars;
}

function handle( $query ) {
    if ( ! array_key_exists( 'resource', $query ) ) {
        header( 'HTTP/1.1 400 Bad Request', true, 400 );
        echo __( 'Expected a "resource" parameter', 'pterotype' );
        exit;
    } 
    $resource = $query['resource'];
    $matches = array();
    $matched = preg_match( '/^acct:([^@]+)@(.+)$/', $resource, $matches );
    if ( ! $matched ) {
        header( 'HTTP/1.1 404 Not Found', true, 404 );
        echo __( 'Resource not found', 'pterotype' );
        exit;
    }
    $account_name = $matches[1];
    $account_host = $matches[2];
    if ( $account_host !== $_SERVER['HTTP_HOST'] ) {
        header( 'HTTP/1.1 404 Not Found', true, 404 );
        echo __( 'Resource not found', 'pterotype' );
        exit;
    }
    if ( $account_name === PTEROTYPE_BLOG_ACTOR_USERNAME ) {
        $account_name = PTEROTYPE_BLOG_ACTOR_SLUG;
    }
    get_webfinger_json( $resource, $account_name );
    exit;
}

function get_webfinger_json( $resource, $actor_slug ) {
    $actor = \pterotype\actors\get_actor_by_slug( $actor_slug );
    if ( is_wp_error( $actor ) ) {
        header( 'HTTP/1.1 404 Not Found', true, 404 );
        echo __( 'Resource not found', 'pterotype' );
        exit;
    }
    $json = array(
        'subject' => $resource,
        'links' => array( array(
            'rel' => 'self',
            'type' => 'application/activity+json',
            'href' => $actor['id'],
        ) ),
    );
    header( 'Content-Type: application/jrd+json', true );
    echo wp_json_encode( $json );
    exit;
}
?>
