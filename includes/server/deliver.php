<?php
namespace deliver;

require_once plugin_dir_path( __FILE__ ) . 'actors.php';
require_once plugin_dir_path( __FILE__ ) . '../pgp.php';
require_once plugin_dir_path( __FILE__ ) . '../util.php';

// TODO look at inReplyTo, object, target, and tag objects
// and deliver to their audience as well. Recurse through these
// objects up to some limit

function deliver_activity( $actor_slug, $activity ) {
    $actor_id = \actors\get_actor_id( $actor_slug );
    $activity = \util\dereference_object( $activity );
    $recipients = array();
    foreach ( array( 'to', 'bto', 'cc', 'bcc', 'audience' ) as $field ) {
        $recipients = array_merge(
            $recipients, retrieve_recipients_for_field( $field, $activity )
        );
    }
    $recipients = array_unique( $recipients );
    if ( array_key_exists( 'actor', $activity ) ) {
        $actor = \util\dereference_object( $activity['actor'] );
        $recipients = remove_actor_inbox_from_recipients( $actor, $recipients );
    }
    $activity = \objects\strip_private_fields( $activity );
    post_activity_to_inboxes( $actor_id, $activity, $recipients );
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
    if ( is_array( $object ) && array_key_exists( 'type', $object ) ) {
        // It's an Actor, Link, or Collection
        switch ( $object['type'] ) {
        case 'Collection':
        case 'OrderedCollection':
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
        case 'Link':
            if ( array_key_exists( 'href', $object ) ) {
                $response = \util\get_object_from_url( $object['href'] );
                if ( is_wp_error( $response ) ) {
                    return array();
                }
                return get_recipient_urls( $response, $depth + 1, $acc );
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
                $response = \util\get_object_from_url( $object );
                if ( is_wp_error( $response ) ) {
                    return array();
                }
                return get_recipient_urls( $response, $depth + 1, $acc );
            } else {
                return array();
            }
        }
    }
}

function post_activity_to_inboxes( $actor_id, $activity, $recipients ) {
    foreach ( $recipients as $inbox ) {
        if ( \util\is_local_url( $inbox ) ) {
            $request = \WP_REST_Request::from_url( $inbox );
            $request->set_method('POST');
            $request->set_body( $activity );
            $request->add_header( 'Content-Type', 'application/ld+json' );
            $request->add_header( 'Signature', signature_header( $inbox, $actor_id ) );
            $server = rest_get_server();
            $server->dispatch( $request );
        } else {
            $args = array(
                'body' => wp_json_encode( $activity ),
                'headers' => array(
                    'Content-Type' => 'application/ld+json',
                    'Signature' => get_signing_string( $inbox, $actor_id ),
                ),
                'data_format' => 'body',
            );
            wp_remote_post( $inbox, $args );
        }
    }
}

function get_signing_string( $inbox_url ) {
    $now = new \DateTime( 'now', new \DateTimeZone('GMT') );
    $now_str = $now->format( 'D, d M Y H:i:s T' );
    $parsed = parse_url( $inbox_url );
    return "(request-target): post $parsed[path]
host: $parsed[host]
date: $now_str";
}

function signature_header( $inbox_url, $actor_id ) {
    return \pgp\sign_data( get_signing_string( $inbox_url ), $actor_id );
}
?>
