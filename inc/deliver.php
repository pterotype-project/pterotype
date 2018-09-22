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
    if ( array_key_exists( $field, $activity ) ) {
        $to_value = $activity[$field];
        return get_recipient_urls( $to_value, 0, array() );
    }
    return array();
}

/*
$object is either an Actor, a Link, a Collection/OrderedCollection,
a single object id (url), or an array of object ids. If it's a url, it
should dereference to one of the above types
*/
function get_recipient_urls( $object, $depth, $acc ) {
    if ( $depth === 30 ) {
        return $acc;
    }
    if ( array_key_exists( 'type', $object ) ) {
        // It's an Actor, Link, or Collection
        switch ( $object['type'] ) {
        case Collection:
        case OrderedCollection:
            $items = array();
            if ( array_key_exists( 'items', $object ) ) {
                $items = $object['items'];
            } else if ( array_key_exists( 'orderedItems', $object ) ) {
                $items = $object['orderedItems'];
            }
            $recipients = $acc;
            foreach ( $items as $item ) {
                $recipients = array_merge(
                    $recipients,
                    get_recipient_urls( $item, $depth + 1, array_merge( $recipients, $acc ) )
                );
            }
            return $recipients;
        case Link:
            if ( array_key_exists( 'href', $object ) ) {
                $link_target = wp_remote_retrieve_body( wp_remote_get( $object['href'] ) );
                return get_recipient_urls( $link_target, $depth + 1, $acc );
            } else {
                return array();
            }
        default:
            // An Actor
            if ( array_key_exists( 'inbox', $object ) ) {
                return array( $object['inbox'] );
            } else {
                return array();
            }
        }
    } else {
        // Assume it's an array of object ids (urls) or a single url
        if ( is_array( $object ) ) {
            $recipients = $acc;
            foreach( $object as $url ) {
                $recipients = array_merge(
                    $recipients,
                    get_recipient_urls( $url, $depth + 1, array_merge( $recipients, $acc ) )
                );
            }
            return $recipients;
        } else {
            if ( filter_var( $object, FILTER_VALIDATE_URL ) ) {
                $response_body = wp_remote_retrieve_body( wp_remote_get ( $object ) );
                return get_recipient_urls( $response_body, $depth + 1, $acc );
            } else {
                return array();
            }
        }
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
