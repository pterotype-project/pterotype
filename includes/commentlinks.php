<?php
namespace pterotype\commentlinks;

function get_object_id( $comment_id ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        "SELECT object_id FROM {$wpdb->prefix}pterotype_comments WHERE comment_id = %d",
        $comment_id
    ) );
}

function get_comment_id( $object_id ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        "SELECT comment_id FROM {$wpdb->prefix}pterotype_comments WHERE object_id = %d",
        $object_id
    ) );
}

function get_object_activitypub_id( $comment_id ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        "
       SELECT activitypub_id FROM {$wpdb->prefix}pterotype_comments
       JOIN {$wpdb->prefix}pterotype_objects
         ON {$wpdb->prefix}pterotype_comments.object_id = {$wpdb->prefix}pterotype_objects.id
       WHERE comment_id = %d
       ",
        $comment_id
    ) );
}

function link_comment( $comment_id, $object_id ) {
    global $wpdb;
    return $wpdb->insert(
        "{$wpdb->prefix}pterotype_comments",
        array( 'comment_id' => $comment_id, 'object_id' => $object_id ),
        '%d'
    );
}

function unlink_comment( $comment_id, $object_id ) {
    global $wpdb;
    return $wpdb->delete(
        "{$wpdb->prefix}pterotype_comments",
        array( 'comment_id' => $comment_id, 'object_id' => $object_id ),
        '%d'
    );
}
?>
