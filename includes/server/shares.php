<?php
namespace pterotype\shares;

require_once plugin_dir_path( __FILE__ ) . 'collections.php';

function add_share( $object_id, $activity_id ) {
    global $wpdb;
    return $wpdb->insert(
        $wpdb->prefix . 'pterotype_shares',
        array(
            'object_id' => $object_id,
            'announce_id' => $activity_id,
        ),
        '%d'
    );
}

function get_shares_collection( $object_id ) {
    global $wpdb;
    $shares = $wpdb->get_results(
        $wpdb->prepare(
            "
           SELECT object FROM {$wpdb->prefix}pterotype_shares
           JOIN {$wpdb->prefix}pterotype_objects
           ON announce_id = {$wpdb->prefix}pterotype_objects.id
           WHERE object_id = %d
           ",
            $object_id
        ),
        ARRAY_A
    );
    if ( !$shares ) {
        $shares = array();
    }
    $collection = \pterotype\collections\make_ordered_collection( array_map(
        function( $result ) {
            return json_decode( $result['object'], true );
        },
        $shares
    ) );
    $collection['id'] = get_rest_url( null, sprintf(
        '/pterotype/v1/object/%d/shares', $object_id
    ) );
    return $collection;
}
?>
