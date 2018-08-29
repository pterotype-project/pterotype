<?php
namespace actors;

function get_actor( $user ) {
    $handle = get_the_author_meta( 'user_nicename', $user->get('ID'));
    $actor = array(
        "@context" => array( "https://www.w3.org/ns/activitystreams" ),
        "type" => "Person",
        "id" => get_rest_url( null, sprintf( '/activitypub/v1/actor/%s', $handle ) ),
        "following" => get_rest_url(
            null, sprintf( '/activitypub/v1/actor/%s/following', $handle ) ),
        "followers" => get_rest_url(
            null, sprintf( '/activitypub/v1/actor/%s/followers', $handle ) ),
        "liked" => get_rest_url(
            null, sprintf( '/activitypub/v1/actor/%s/liked', $handle ) ),
        "inbox" => get_rest_url(
            null, sprintf( '/activitypub/v1/actor/%s/inbox', $handle ) ),
        "outbox" => get_rest_url(
            null, sprintf( '/activitypub/v1/actor/%s/outbox', $handle ) ),
        "preferredUsername" => $handle,
        "name" => get_the_author_meta( 'display_name', $user->get('ID') ),
        "summary" => get_the_author_meta( 'description', $user->get('ID') ),
        "icon" => get_avatar_url ( $user->get('ID') ),
        "url" => get_the_author_meta( 'user_url', $user->get('ID') ),
    );
    return $actor;
}
?>
