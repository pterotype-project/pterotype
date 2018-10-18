<?php
namespace init;

require_once plugin_dir_path( __FILE__ ) . 'util.php';
require_once plugin_dir_path( __FILE__ ) . 'server/api.php';
require_once plugin_dir_path( __FILE__ ) . 'server/actors.php';
require_once plugin_dir_path( __FILE__ ) . 'schema.php';
require_once plugin_dir_path( __FILE__ ) . 'server/webfinger.php';
require_once plugin_dir_path( __FILE__ ) . 'client/posts.php';
require_once plugin_dir_path( __FILE__ ) . 'server/async.php';

add_action( 'rest_api_init', function() {
    \api\register_routes();
} );

add_action( 'user_register', function( $user_id ) {
    $slug = get_the_author_meta( 'user_nicename', $user_id );
    \actors\create_actor_user( $slug, 'user' );
} );

add_action( 'pterotype_init', function() {
    \schema\run_migrations();
    \actors\initialize_actors();
    if ( ! empty( ob_get_contents() ) ) {
        \util\log( 'init.log', ob_get_contents(), false );
    }
} );

add_action( 'pterotype_load', function() {
    \schema\run_migrations();
    \async\init_tasks();
} );

add_action( 'generate_rewrite_rules', '\webfinger\generate_rewrite_rules', 111 );
add_action( 'parse_request', '\webfinger\parse_request', 111 );
add_filter( 'query_vars', '\webfinger\query_vars' );
add_action( 'well_known_webfinger', '\webfinger\handle' );
add_action( 'transition_post_status', '\posts\handle_post_status_change', 10, 3 );
?>
