<?php
namespace init;

require_once plugin_dir_path( __FILE__ ) . '/outbox.php';
require_once plugin_dir_path( __FILE__ ) . '/api.php';
require_once plugin_dir_path( __FILE__ ) . '/objects.php';
require_once plugin_dir_path( __FILE__ ) . '/activities.php';
require_once plugin_dir_path( __FILE__ ) . '/actors.php';
require_once plugin_dir_path( __FILE__ ) . '/likes.php';
require_once plugin_dir_path( __FILE__ ) . '/following.php';
require_once plugin_dir_path( __FILE__ ) . '/blocks.php';

add_action( 'rest_api_init', function() {
    \api\register_routes();
} );

add_action( 'user_register', function( $user_id ) {
    $slug = get_the_author_meta( 'user_nicename', $user_id );
    \actors\create_actor_from_user( $slug );
} );

add_action( 'activitypub_init', function() {
    \activities\create_activities_table();
    \objects\create_object_table();
    \outbox\create_outbox_table();
    \actors\create_actors_table();
    \actors\initialize_user_actors();
    \likes\create_likes_table();
    \following\create_following_table();
    \blocks\create_blocks_table();
} );
?>
