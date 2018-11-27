<?php
namespace pterotype\init;

require_once plugin_dir_path( __FILE__ ) . 'util.php';
require_once plugin_dir_path( __FILE__ ) . 'server/api.php';
require_once plugin_dir_path( __FILE__ ) . 'server/actors.php';
require_once plugin_dir_path( __FILE__ ) . 'schema.php';
require_once plugin_dir_path( __FILE__ ) . 'server/webfinger.php';
require_once plugin_dir_path( __FILE__ ) . 'client/posts.php';
require_once plugin_dir_path( __FILE__ ) . 'client/comments.php';
require_once plugin_dir_path( __FILE__ ) . 'client/identity.php';
require_once plugin_dir_path( __FILE__ ) . 'server/async.php';
require_once plugin_dir_path( __FILE__ ) . 'pgp.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/admin.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/settings.php';

if ( ! function_exists( 'opengraph_metadata' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'lib/opengraph.php';
}

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
    if ( ob_get_contents() ) {
        \error_log( ob_get_contents() );
    }
} );

add_action( 'pterotype_load', function() {
    \pterotype\schema\run_migrations();
    \pterotype\async\init_tasks();
} );

add_action( 'pterotype_uninstall', function() {
    \pterotype\schema\purge_all_data();
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

add_action( 'update_option_blogname', function() {
    \pterotype\identity\update_identity( PTEROTYPE_BLOG_ACTOR_SLUG );
} );

add_action( 'update_option_blogdescription', function() {
    \pterotype\identity\update_identity( PTEROTYPE_BLOG_ACTOR_SLUG );
} );

$theme = \get_option( 'stylesheet' );
add_action( "update_option_theme_mods_$theme", function() {
     \pterotype\identity\update_identity( PTEROTYPE_BLOG_ACTOR_SLUG );
} );

add_action( 'update_option_pterotype_blog_name', function() {
    \pterotype\identity\update_identity( PTEROTYPE_BLOG_ACTOR_SLUG );
} );

add_action( 'update_option_pterotype_blog_description', function() {
    \pterotype\identity\update_identity( PTEROTYPE_BLOG_ACTOR_SLUG );
} );

add_action( 'update_option_pterotype_blog_icon', function() {
    \pterotype\identity\update_identity( PTEROTYPE_BLOG_ACTOR_SLUG );
} );

add_action( 'admin_menu', function() {
    \pterotype\admin\register_admin_page();
    \pterotype\settings\register_settings_sections();
    \pterotype\settings\register_settings_fields();
} );

add_filter( 'get_avatar', '\pterotype\comments\get_avatar_filter', 10, 5 );
?>
