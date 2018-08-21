<?php
namespace actors;

function author_to_user( $user ) {
    $handle = get_the_author_meta( 'user_nicename', $user->get('ID'));
    $actor = array(
        "@context" => array( "https://www.w3.org/ns/activitystreams" ),
        "type" => "Person",
        "id" => get_rest_url( null, sprintf( '/activitypub/v1/user/%s', $handle ) ),
        "following" => "TODO", // link to following JSON
        "followers" => "TODO", // link to followers JSON
        "liked" => "TODO", // link to liked JSON
        "inbox" => "TODO", // link to inbox JSON
        "outbox" => "TODO", // link to outbox JSON
        "preferredUsername" => $handle,
        "name" => get_the_author_meta( 'display_name', $user->get('ID') ),
        "summary" => get_the_author_meta( 'description', $user->get('ID') ),
        "icon" => get_avatar_url ( $user->get('ID') ),
        "url" => get_the_author_meta( 'user_url', $user->get('ID') ),
    );
    return $actor;
}
?>
