<?php
namespace pterotype\init;

require_once plugin_dir_path( __FILE__ ) . 'util.php';
require_once plugin_dir_path( __FILE__ ) . 'server/api.php';
require_once plugin_dir_path( __FILE__ ) . 'server/actors.php';
require_once plugin_dir_path( __FILE__ ) . 'schema.php';
require_once plugin_dir_path( __FILE__ ) . 'server/webfinger.php';
require_once plugin_dir_path( __FILE__ ) . 'client/posts.php';
require_once plugin_dir_path( __FILE__ ) . 'client/comments.php';
require_once plugin_dir_path( __FILE__ ) . 'server/async.php';
require_once plugin_dir_path( __FILE__ ) . 'pgp.php';

add_action( 'rest_api_init', function() {
    \pterotype\api\register_routes();
} );

add_action( 'user_register', function( $user_id ) {
    $slug = get_the_author_meta( 'user_nicename', $user_id );
    \pterotype\actors\create_actor( $slug, 'user' );
    $actor_id = \pterotype\actors\get_actor_id( $slug );
    $keys_created = \pterotype\pgp\get_public_key( $slug );
    if ( ! $keys_created ) {
        $keys = \pterotype\pgp\gen_key( $slug );
        \pterotype\pgp\persist_key( $actor_id, $keys['publickey'], $keys['privatekey'] );
    }
} );

add_action( 'pterotype_init', function() {
    \pterotype\schema\run_migrations();
    \pterotype\actors\initialize_actors();
    if ( ! empty( ob_get_contents() ) ) {
        \pterotype\util\log( 'init.log', ob_get_contents(), false );
    }
} );

add_action( 'pterotype_load', function() {
    \pterotype\schema\run_migrations();
    \pterotype\async\init_tasks();
} );

add_action( 'generate_rewrite_rules', '\pterotype\webfinger\generate_rewrite_rules', 111 );
add_action( 'parse_request', '\pterotype\webfinger\parse_request', 111 );
add_filter( 'query_vars', '\pterotype\webfinger\query_vars' );
add_filter( 'query_vars', '\pterotype\api\query_vars' );
add_action( 'well_known_webfinger', '\pterotype\webfinger\handle' );
add_action( 'transition_post_status', '\pterotype\posts\handle_post_status_change', 10, 3 );
add_action(
    'transition_comment_status', '\pterotype\comments\handle_transition_comment_status', 10, 3
);
add_action( 'comment_post', '\pterotype\comments\handle_comment_post', 10, 2 );
add_action( 'edit_comment', '\pterotype\comments\handle_edit_comment', 10, 1 );
add_action( 'template_redirect', '\pterotype\api\handle_non_api_requests' );
?>
