<?php
/*
Plugin Name: ActivityPub
*/

function post_published_activity( $ID, $post ) {
    // TODO
    $author = $post->post_author;
}
add_action( 'publish_post', 'post_published_activity', 10, 2 );
?>
