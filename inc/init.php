<?php
namespace init;

require_once plugin_dir_path( __FILE__ ) . '/api.php';
require_once plugin_dir_path( __FILE__ ) . '/actors.php';
require_once plugin_dir_path( __FILE__ ) . '/db.php';

add_action( 'rest_api_init', function() {
    \api\register_routes();
} );

add_action( 'user_register', function( $user_id ) {
    $slug = get_the_author_meta( 'user_nicename', $user_id );
    \actors\create_actor_from_user( $slug );
} );

add_action( 'pterotype_init', function() {
    \db\run_migrations();
    \actors\initialize_user_actors();
} );
?>
