<?php
namespace pterotype\activities\create;

require_once plugin_dir_path( __FILE__ ) . '../objects.php';
require_once plugin_dir_path( __FILE__ ) . '../actors.php';
require_once plugin_dir_path( __FILE__ ) . '../../util.php';

/*
Create a new post or comment (depending on $activity["object"]["type"]),
copying $activity["actor"] to the object's "attributedTo" and
copying any recipients of the activity that aren't on the object
to the object and vice-versa.

Returns either the modified $activity or a WP_Error.
*/
function handle_outbox( $actor_slug, $activity ) {
    if ( !(array_key_exists( 'type', $activity ) && $activity['type'] === 'Create') ) {
        return new \WP_Error(
            'invalid_activity', __( 'Expecting a Create activity', 'pterotype' )
        );
    }
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_object', __( 'Expecting an object', 'pterotype' )
        );
    }
    if ( !array_key_exists( 'actor', $activity ) ) {
        return new \WP_Error(
            'invalid_actor', __( 'Expecting a valid actor', 'pterotype' )
        );
    }
    $object = $activity['object'];
    $attributed_actor = $activity['actor'];
    $object['attributedTo'] = $attributed_actor;
    reconcile_receivers( $object, $activity );
    $object = scrub_object( $object );
    $object = \pterotype\objects\create_local_object( $object );
    if ( is_wp_error( $object ) ) {
        return $object;
    }
    $activity['object'] = $object;
    return $activity;
}

function handle_inbox( $actor_slug, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    $object_row = \pterotype\objects\upsert_object( $object );
    if ( is_wp_error( $object_row ) ) {
        return $object_row;
    }
    sync_comments( $activity );
    return $activity;
}

function reconcile_receivers( &$object, &$activity ) {
    copy_field_value( 'audience', $object, $activity );
    copy_field_value( 'audience', $activity, $object );

    copy_field_value( 'to', $object, $activity );
    copy_field_value( 'to', $activity, $object );

    copy_field_value( 'cc', $object, $activity );
    copy_field_value( 'cc', $activity, $object );

    // copy bcc and bto to activity for delivery but not to object
    copy_field_value( 'bcc', $object, $activity );
    copy_field_value( 'bto', $object, $activity );
}

function copy_field_value( $field, $from, &$to ) {
    if ( array_key_exists( $field, $from ) ) {
        if ( array_key_exists ( $field, $to ) ) {
            $to[$field] = array_unique(
                array_merge( $from[$field], $to[$field] )
            );
        } else {
            $to[$field] = $from[$field];
        }
    }
}

function scrub_object( $object ) {
    unset( $object['bcc'] );
    unset( $object['bto'] );
    return $object;
}

function make_create( $actor_slug, $object ) {
    $actor = \pterotype\actors\get_actor_by_slug( $actor_slug );
    if ( is_wp_error( $actor ) ) {
        return $actor;
    }
    $activity = array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Create',
        'actor' => $actor,
        'object' => $object
    );
    return $activity;
}

function sync_comments( $activity ) {
    $object = $activity['object'];
    if ( ! array_key_exists( 'inReplyTo', $object ) ) {
        return;
    }
    $inReplyTo = \pterotype\util\dereference_object( $object['inReplyTo'] );
    $parent = \pterotype\objects\get_object_by_activitypub_id( $inReplyTo['id'] );
    if ( ! $parent || is_wp_error( $parent ) ) {
        return;
    }
    if ( ! array_key_exists( 'url', $parent ) ) {
        return;
    }
    if ( ! \pterotype\util\is_local_url( $parent['url'] ) ) {
        return;
    }
    $url = $parent['url'];
    $post_id = \url_to_postid( $url );
    if ( $post_id === 0 ) {
        return;
    }
    $parent_comment_id = null;
    if ( strpos( $url, '?pterotype_comment=' ) !== false ) {
        $matches = array();
        preg_match( '/\?pterotype_comment=comment-(\d+)/', $url, $matches );
        $parent_comment_id = $matches[1];
    }
    $comment = make_comment_from_object( $object, $post_id, $parent_comment_id );
    \wp_new_comment( $comment );
}

function make_comment_from_object( $object, $post_id, $parent_comment_id = null ) {
    $actor = null;
    if ( array_key_exists( 'attributedTo', $object ) ) {
        $actor = \pterotype\util\dereference_object( $object['attributedTo'] );
    } else if ( array_key_exists( 'actor', $object ) ) {
        $actor = \pterotype\util\dereference_object( $object['actor'] );
    }
    if ( ! $actor || is_wp_error( $actor ) ) {
        return;
    }
    $comment = array(
        'comment_author' => get_actor_name( $actor ),
        'comment_content' => $object['content'],
        'comment_post_ID' => $post_id,
        'comment_author_email' => get_actor_email( $actor ),
        'comment_type' => '',
    );
    if ( $parent_comment_id ) {
        $comment['comment_parent'] = $parent_comment_id;
    }
    if ( array_key_exists( 'url', $actor ) ) {
        $comment['comment_author_url'] = $actor['url'];
    }
    return $comment;
}

function get_actor_name( $actor ) {
    if ( array_key_exists( 'name', $actor ) && ! empty( $actor['name'] ) ) {
        return $actor['name'];
    }
    if ( array_key_exists( 'preferredUsername', $actor ) &&
         ! empty( $actor['preferredUsername' ] ) ) {
        return $actor['preferredUsername'];
    }
    if ( array_key_exists( 'url', $actor ) && ! empty( $actor['url' ] ) ) {
        return $actor['url'];
    }
    return $actor['id'];
}

function get_actor_email( $actor ) {
    $preferredUsername = $actor['id'];
    if ( array_key_exists( 'preferredUsername', $actor ) ) {
        $preferredUsername = $actor['preferredUsername'];
    } else if ( array_key_exists( 'name', $actor ) ) {
        $preferredUsername = str_replace( ' ', '_', $actor['name'] );
    }
    $parsed = parse_url( $actor['id'] );
    if ( $parsed && array_key_exists( 'host', $parsed ) ) {
        $host = $parsed['host'];
        if ( array_key_exists( 'port', $parsed ) ) {
            $host = $host . ':' . $parsed['port'];
        }
        return $preferredUsername . '@' . $host;
    } else {
        return $preferredUsername . '@fakeemails.getpterotype.com';
    }
}
?>
