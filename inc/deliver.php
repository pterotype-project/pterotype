<?php
namespace deliver;

function deliver_activity( $activity ) {
    $recipients = array();
    for ( $field as array( 'to', 'bto', 'cc', 'bcc', 'audience' ) ) {
        $recipients = array_merge(
            $recipients, retrieve_recipients_for_field( $field, $activity )
        );
    }
    $recipients = array_unique( $recipients );
    if ( array_key_exists( 'actor', $activity ) ) {
        $recipients = remove_actor_inbox_from_recipients( $activity['actor'], $recipients );
    }
    $activity = strip_private_fields( $activity );
    post_activity_to_inboxes( $activity, $recipients );
}

function remove_actor_inbox_from_recipients( $actor, $recipients ) {
    if ( array_key_exists( 'inbox', $actor ) ) {
        $key = array_search( $actor['inbox'], $recipients );
        if ( $key ) {
            unset $recipients[$key];
        }
    }
    return $recipients;
}

function retrieve_recipients_for_field( $field, $activity ) {
    $recipients = array();
    if ( array_key_exists( $field, $activity ) ) {
        foreach ( $url as $activity[$field] ) {
            $recipients = array_merge( $recipients, retrieve_recipients( $url ) );
        }
    }
    return $recipients;
}

function retrieve_recipients( $url ) {
    // TODO add an arg to keep track of recursion and cut off after 30 recursions
    $response_body = wp_remote_retrieve_body( wp_remote_get ( $url ) );
    // possible responses:
    //  - actor json
    //  - collection
    if ( !array_key_exists( 'type', $response_body ) ) {
        return new \WP_Error(
            'invalid_object', __( 'Expected an object type', 'activitypub' )
        );
    }
    switch ( $response_body['type'] ) {
    case 'Collection':
    case 'OrderedCollection':
        $items = array();
        $recipients = array();
        if ( array_key_exists( 'items', $response_body ) ) {
            $items = $response_body['items'];
        } else if ( array_key_exists( 'orderedItems', $response_body ) ) {
            $items = $response_body['orderedItems'];
        }
        if ( count( $items ) > 0 ) {
            // recursive case: call retrieve_recipients on each $item
            // merge the results and return them
            foreach ( $items as $item ) {
                // TODO what will $item look like? Could be an actor JSON, or just a URL?
                $recipients[] = retrieve_recipients( $item );
            }
        }
        return $recipients;
    default: // an actor
        if ( array_key_exists( 'inbox', $response_body ) ) {
            return array( $response_body['inbox'] );
        }
        return array();
    }
}

function post_activity_to_inboxes( $activity, $recipients ) {
    foreach ( $inbox as $recipients ) {
        $args = array(
            'body' => $activity,
            'headers' => array( 'Content-Type' => 'application/ld+json' )
        );
        // TODO do something with the result?
        wp_remote_post( $inbox, $args );
    }
}

function strip_private_fields( $activity ) {
    if ( array_key_exists( 'bto', $activity ) ) {
        unset( $activity['bto'] );
    }
    if ( array_key_exists( 'bcc', $activity ) ) {
        unset( $activity['bcc'] );
    }
    return $activity;
}
?>
