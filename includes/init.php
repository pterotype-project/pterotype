<?php
namespace init;

require_once plugin_dir_path( __FILE__ ) . 'server/api.php';
require_once plugin_dir_path( __FILE__ ) . 'server/actors.php';
require_once plugin_dir_path( __FILE__ ) . 'migrations.php';

add_action( 'rest_api_init', function() {
    \api\register_routes();
} );

add_action( 'user_register', function( $user_id ) {
    $slug = get_the_author_meta( 'user_nicename', $user_id );
    \actors\create_actor_user( $slug, 'user' );
} );

add_action( 'pterotype_init', function() {
    \actors\initialize_actors();
} );

add_action( 'pterotype_load', function() {
    \migrations\run_migrations();
} );
?>
