<?php
namespace pterotype\posts;

require_once plugin_dir_path( __FILE__ ) . '../server/activities/create.php';
require_once plugin_dir_path( __FILE__ ) . '../server/activities/update.php';
require_once plugin_dir_path( __FILE__ ) . '../server/activities/delete.php';
require_once plugin_dir_path( __FILE__ ) . '../server/followers.php';
require_once plugin_dir_path( __FILE__ ) . '../server/objects.php';

function handle_post_status_change( $new_status, $old_status, $post ) {
    $actor_slug = PTEROTYPE_BLOG_ACTOR_SLUG;
    $actor_outbox = get_rest_url(
        null, sprintf( 'pterotype/v1/actor/%s/outbox', $actor_slug )
    );
    $post_object = post_to_object( $post );
    $activity = null;
    if ( $new_status == 'publish' && $old_status != 'publish' ) {
        // Create
        $activity = \pterotype\activities\create\make_create( $actor_slug, $post_object );
    } else if ( $new_status == 'publish' && $old_status == 'publish' ) {
        // Update
        $activity = \pterotype\activities\update\make_update( $actor_slug, $post_object );
    } else if ( $new_status != 'publish' && $old_status == 'publish' ) {
        // Delete
        $activity = \pterotype\activities\delete\make_delete( $actor_slug, $post_object );
    }
    if ( $activity && ! is_wp_error( $activity ) ) {
        $followers = \pterotype\followers\get_followers_collection( $actor_slug );
        $activity['to'] = array(
            'https://www.w3.org/ns/activitystreams#Public',
            $followers['id']
        );
        $server = rest_get_server();
        $request = \WP_REST_Request::from_url( $actor_outbox );
        $request->set_method( 'POST' );
        $request->set_body( wp_json_encode( $activity ) );
        $request->add_header( 'Content-Type', 'application/ld+json' );
        $server->dispatch( $request );
    }
}

/**
Return an object of type Article
*/
function post_to_object( $post ) {
    $supported_post_types = array( 'post' );
    if ( ! in_array( $post->post_type, $supported_post_types ) ) {
        return;
    }
    setup_postdata( $post );
    $GLOBALS['post'] = $post;
    $permalink = get_permalink( $post );
    $summary = null;
    if ( $post->post_content ) {
        $summary = apply_filters( 'get_the_excerpt', get_post_field( 'post_excerpt', $post->ID ) );
    }
    $matches = array();
    if ( preg_match( '/(.+)__trashed\/$/', $permalink, $matches ) ) {
        $permalink = $matches[1] . '/';
    }
    $object = array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Article',
        'name' => $post->post_title,
        'content' => $post->post_content,
        'summary' => $summary,
        'attributedTo' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s', PTEROTYPE_BLOG_ACTOR_SLUG )
        ),
        'url' => $permalink,
    );
    $existing = \pterotype\objects\get_object_by_url( $permalink );
    if ( $existing ) {
        $object['id'] = $existing['id'];
    }
    return $object;
}
?>
