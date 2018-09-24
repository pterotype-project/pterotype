<?php
namespace shares;

function add_share( $object_id, $activity_id ) {
    global $wpdb;
    return $wpdb->insert(
        'pterotype_shares',
        array(
            'object_id' => $object_id,
            'activity_id' => $activity_id,
        ),
        '%d'
    );
}
?>
