<?php
namespace deliver;

require_once plugin_dir_path( __FILE__ ) . 'activities.php';

function deliver_activity( $activity ) {
    $recipients = array();
    foreach ( array( 'to', 'bto', 'cc', 'bcc', 'audience' ) as $field ) {
        $recipients = array_merge(
            $recipients, retrieve_recipients_for_field( $field, $activity )
        );
    }
    $recipients = array_unique( $recipients );
    if ( array_key_exists( 'actor', $activity ) ) {
        $recipients = remove_actor_inbox_from_recipients( $activity['actor'], $recipients );
    }
    $activity = \activities\strip_private_fields( $activity );
    post_activity_to_inboxes( $activity, $recipients );
}

function remove_actor_inbox_from_recipients( $actor, $recipients ) {
    if ( array_key_exists( 'inbox', $actor ) ) {
        $key = array_search( $actor['inbox'], $recipients );
        if ( $key ) {
            unset( $recipients[$key] );
        }
    }
    return $recipients;
}

function retrieve_recipients_for_field( $field, $activity ) {
    $recipients = array();
    if ( array_key_exists( $field, $activity ) ) {
        foreach ( $activity[$field] as $object ) {
            $recipients = array_merge( $recipients, retrieve_recipients( $object , 0 ) );
        }
    }
    return $recipients;
}

function retrieve_recipients( $object, $depth ) {
    if ( $depth === 30 ) {
        return array();
    }
    if ( !array_key_exists( 'type', $object ) ) {
        return new \WP_Error(
            'invalid_object', __( 'Expected an object type', 'pterotype' ), array( 'status' => 400 )
        );
    }
    switch ( $object['type'] ) {
    case 'Collection':
    case 'OrderedCollection':
        $items = array();
        $recipients = array();
        if ( array_key_exists( 'items', $object ) ) {
            $items = $object['items'];
        } else if ( array_key_exists( 'orderedItems', $object ) ) {
            $items = $object['orderedItems'];
        }
        if ( count( $items ) > 0 ) {
            // recursive case: call retrieve_recipients on each $item
            // merge the results and return them
            foreach ( $items as $item ) {
                $recipients[] = retrieve_recipients( $item, $depth + 1 );
            }
        }
        return $recipients;
    case 'Link':
        if ( !array_key_exists( 'href', $object ) ) {
            return new \WP_Error(
                'invalid_link',
                __( 'Link requires an "href" field', 'pterotype' ),
                array( 'status' => 400 )
            );
        }
        $link_target = wp_remote_retrieve_body( wp_remote_get ( $response_body['href'] ) );
        return retrieve_recipients( $link_target, $depth + 1 );
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
?>
