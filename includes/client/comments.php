<?php
namespace pterotype\comments;

require_once plugin_dir_path( __FILE__ ) . '../server/activities/create.php';
require_once plugin_dir_path( __FILE__ ) . '../server/activities/update.php';
require_once plugin_dir_path( __FILE__ ) . '../server/activities/delete.php';

function handle_transition_comment_status( $old_status, $new_status, $comment ) {
    $actor_slug = get_comment_actor_slug( $comment );
    $actor_outbox = get_rest_url(
        null, sprintf( 'pterotype/v1/actor/%s/outbox', $actor_slug )
    );
    $comment_object = comment_to_object( $comment );
    $activity = null;
    if ( $new_status == 'approve' && $old_status != 'approve' ) {
        // Create
        $activity = \pterotype\activities\create\make_create( $actor_slug, $comment_object );
    } else if ( $new_status == 'approve' && $old_status == 'approve' ) {
        // Update
        $activity = \pterotype\activities\update\make_update( $actor_slug, $comment_object );
    } else if ( $new_status == 'trash' && $old_status != 'trash' ) {
        // Delete
        $activity = \pterotype\activities\delete\make_delete( $actor_slug, $comment_object );
    }
    if ( $activity && ! is_wp_error( $activity ) ) {
        $followers = \pterotype\followers\get_followers_collection( $actor_slug );
        $activity['to'] = get_comment_to( $comment, $followers['id'] );
        $server = rest_get_server();
        $request = \WP_REST_Request::from_url( $actor_outbox );
        $request->set_method( 'POST' );
        $request->set_body( wp_json_encode( $activity ) );
        $request->add_header( 'Content-Type', 'application/ld+json' );
        $server->dispatch( $request );
    }
}

function get_comment_actor_slug( $comment ) {
    if ( $comment->user_id !== 0 ) {
        return get_comment_user_actor_slug( $comment->user_id );
    } else {
        return get_comment_email_actor_slug( $comment->comment_author_email );
    }
}

function get_comment_user_actor_slug( $user_id ) {

}

function get_comment_email_actor_slug( $email_address ) {

}

function comment_to_object( $comment ) {

}

function get_comment_to( $comment, $followers_id ) {
    $to = array(
        'https://www.w3.org/ns/activitystreams#Public',
        $followers_id,
    );
    // TODO traverse comment reply chain to retrieve others to address to
    return $to;
}
?>
