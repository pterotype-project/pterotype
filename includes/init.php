<?php
namespace pterotype\init;

require_once plugin_dir_path( __FILE__ ) . 'util.php';
require_once plugin_dir_path( __FILE__ ) . 'server/api.php';
require_once plugin_dir_path( __FILE__ ) . 'server/actors.php';
require_once plugin_dir_path( __FILE__ ) . 'schema.php';
require_once plugin_dir_path( __FILE__ ) . 'server/webfinger.php';
require_once plugin_dir_path( __FILE__ ) . 'client/posts.php';
require_once plugin_dir_path( __FILE__ ) . 'server/async.php';

add_action( 'rest_api_init', function() {
    \pterotype\api\register_routes();
} );

add_action( 'user_register', function( $user_id ) {
    $slug = get_the_author_meta( 'user_nicename', $user_id );
    \pterotype\actors\create_actor_user( $slug, 'user' );
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
add_action( 'well_known_webfinger', '\pterotype\webfinger\handle' );
add_action( 'transition_post_status', '\pterotype\posts\handle_post_status_change', 10, 3 );
?>
