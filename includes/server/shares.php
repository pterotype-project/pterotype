<?php
namespace shares;

require_once plugin_dir_path( __FILE__ ) . 'collections.php';

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

function get_shares_collection( $object_id ) {
    global $wpdb;
    $shares = $wpdb->get_results(
        $wpdb->prepare(
           '
           SELECT activity FROM pterotype_shares
           JOIN pterotype_activities ON announce_id = pterotype_activities.id
           WHERE object_id = %d
           ',
            $object_id
        ),
        ARRAY_A
    );
    if ( !$shares ) {
        $shares = array();
    }
    $collection = \collections\make_ordered_collection( $shares );
    $collection['id'] = get_rest_url( null, sprintf(
        '/pterotype/v1/object/%d/shares', $object_id
    ) );
    return $collection;
}
?>
