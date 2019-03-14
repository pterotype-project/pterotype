<?php
namespace pterotype\activities\create;

require_once plugin_dir_path( __FILE__ ) . '../objects.php';
require_once plugin_dir_path( __FILE__ ) . '../actors.php';
require_once plugin_dir_path( __FILE__ ) . '../../util.php';
require_once plugin_dir_path( __FILE__ ) . '../../commentlinks.php';

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
    link_comment( $object );
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
    $object = \pterotype\util\dereference_object( $activity['object'] );
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
        'actor' => $actor['id'],
        'object' => $object
    );
    return $activity;
}

function link_comment( $object ) {
    $object = \pterotype\util\dereference_object( $object );
    if ( ! array_key_exists( 'url', $object ) ) {
        return;
    }
    if ( ! \pterotype\util\is_local_url( $object['url'] ) ) {
        return;
    }
    $comment_id = get_comment_id_from_url( $object['url'] );
    if ( ! $comment_id ) {
        return;
    }
    $object_id = \pterotype\objects\get_object_id( $object['id'] );
    \pterotype\commentlinks\link_comment( $comment_id, $object_id );
}

function sync_comments( $activity ) {
    $object = \pterotype\util\dereference_object( $activity['object'] );
    $object_id = \pterotype\objects\get_object_id( $object['id'] );
    $comment_exists = \pterotype\commentlinks\get_comment_id( $object_id );
    if ( $comment_exists ) {
        return;
    }
    if ( ! array_key_exists( 'inReplyTo', $object ) ) {
        return;
    }
    if ( is_array( $object['inReplyTo'] ) && array_key_exists( 'id', $object['inReplyTo'] ) ) {
        $inReplyToId = $object['inReplyTo']['id'];
    } else {
        $inReplyToId = $object['inReplyTo'];
    }
    $parent_row = \pterotype\objects\get_object_row_by_activity_id( $inReplyToId );
    if ( ! $parent_row || is_wp_error( $parent_row ) ) {
        return;
    }
    $parent_comment_id = \pterotype\commentlinks\get_comment_id( $parent_row->id );
    if ( $parent_comment_id ) {
        $parent_comment = \get_comment( $parent_comment_id );
        if ( ! $parent_comment ) {
            return;
        }
        if ( ! \comments_open( $parent_comment->comment_post_ID ) ) {
            return;
        }
        $comment = make_comment_from_object( $object, $parent_comment->comment_post_ID, $parent_comment_id );
        $comment_id = \wp_new_comment( $comment );
        link_new_comment( $comment_id, $object_id );
        return;
    } else {
        $parent = \pterotype\util\dereference_object( $parent_row->object );
        if ( ! array_key_exists( 'url', $parent ) ) {
            return;
        }
        $url = $parent['url'];
        $post_id = \url_to_postid( $url );
        if ( $post_id === 0 ) {
            return;
        }
        if ( ! \comments_open( $post_id ) ) {
            return;
        }
        $comment = make_comment_from_object( $object, $post_id, $parent_comment_id );
        $comment_id = \wp_new_comment( $comment );
        link_new_comment( $comment_id, $object_id );
    }
}

function link_new_comment( $comment_id, $object_id ) {
    return \pterotype\commentlinks\link_comment( $comment_id, $object_id );
}

function get_comment_id_from_url( $url ) {
    if ( strpos( $url, '?pterotype_comment=' ) !== false ) {
        $matches = array();
        preg_match( '/\?pterotype_comment=comment-(\d+)/', $url, $matches );
        return $matches[1];
    }
    return null;
}

function make_comment_from_object( $object, $post_id, $parent_comment_id = null ) {
    $object = \pterotype\util\dereference_object( $object );
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
