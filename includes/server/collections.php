<?php
namespace pterotype\collections;

function make_ordered_collection( $objects ) {
    $ordered_collection = array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'OrderedCollection',
        'totalItems' => count( $objects ),
        'orderedItems' => $objects
    );
    return $ordered_collection;
}
?>
