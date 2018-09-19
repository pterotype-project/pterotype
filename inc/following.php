<?php
namespace following;

$PENDING = 'PENDING';
$FOLLOWING = 'FOLLOWING';

function request_follow( $actor_id, $object_id ) {
    global $wpdb;
    return $wpdb->insert(
        'pterotype_activitypub_following',
        array( 'actor_id' => $actor_id,
               'object_id' => wp_json_encode( $object ),
               'state' => $PENDING
        )
    );
}
?>
