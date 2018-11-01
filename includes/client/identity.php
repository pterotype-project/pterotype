<?php
namespace pterotype\identity;

require_once plugin_dir_path( __FILE__ ) . '../server/actors.php';
require_once plugin_dir_path( __FILE__ ) . '../server/activities/update.php';

function update_identity( $actor_slug ) {
    $actor = \pterotype\actors\get_actor_by_slug( $actor_slug );
    if ( ! $actor || is_wp_error( $actor ) ) {
        return;
    }
    $update = \pterotype\activities\update\make_update( $actor_slug, $actor );
    if ( ! $update || is_wp_error( $update ) ) {
        return;
    }
    $update['to'] = array(
        'https://www.w3.org/ns/activitystreams#Public',
        $actor['followers']
    );
    $server = \rest_get_server();
    $request = \WP_REST_Request::from_url( $actor['outbox'] );
    $request->set_method( 'POST' );
    $request->set_body( wp_json_encode( $update ) );
    $request->add_header( 'Content-Type', 'application/ld+json' );
    $server->dispatch( $request );
}
?>
