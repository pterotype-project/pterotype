<?php
namespace pterotype\comments;

require_once plugin_dir_path( __FILE__ ) . '../server/activities/create.php';
require_once plugin_dir_path( __FILE__ ) . '../server/activities/update.php';
require_once plugin_dir_path( __FILE__ ) . '../server/activities/delete.php';
require_once plugin_dir_path( __FILE__ ) . '../server/objects.php';
require_once plugin_dir_path( __FILE__ ) . '../commentlinks.php';

function handle_comment_post( $comment_id, $comment_approved ) {
    do_action( 'pterotype_handle_comment_post', $comment_id, $comment_approved );
}

function handle_edit_comment( $comment_id ) {
    $comment = \get_comment( $comment_id );
    if ( $comment->comment_approved ) {
        handle_transition_comment_status( 'approved', 'approved', $comment );
    }
}

function handle_transition_comment_status( $new_status, $old_status, $comment ) {
    $existing = \pterotype\commentlinks\get_object_id( $comment->comment_ID );
    if ( $existing ) {
        return;
    }
    // This creates a new commenter actor if necessary
    $actor_slug = get_comment_actor_slug( $comment );
    if ( is_wp_error( $actor_slug ) ) {
        return;
    }
    $actor_outbox = get_rest_url(
        null, sprintf( 'pterotype/v1/actor/%s/outbox', $actor_slug )
    );
    $comment_object = comment_to_object( $comment, $actor_slug );
    $activity = null;
    if ( $new_status == 'approved' && $old_status != 'approved' ) {
        // Create
        $activity = \pterotype\activities\create\make_create( $actor_slug, $comment_object );
    } else if ( $new_status == 'approved' && $old_status == 'approved' ) {
        // Update
        $activity = \pterotype\activities\update\make_update( $actor_slug, $comment_object );
    } else if ( $new_status == 'trash' && $old_status != 'trash' ) {
        // Delete
        $activity = \pterotype\activities\delete\make_delete( $actor_slug, $comment_object );
    }
    if ( $activity && ! is_wp_error( $activity ) ) {
        $followers = \pterotype\followers\get_followers_collection( $actor_slug );
        $activity['to'] = get_comment_to( $comment_object, $followers['id'] );
        $server = rest_get_server();
        $request = \WP_REST_Request::from_url( $actor_outbox );
        $request->set_method( 'POST' );
        $request->set_body( wp_json_encode( $activity ) );
        $request->add_header( 'Content-Type', 'application/ld+json' );
        $server->dispatch( $request );
    }
}

function get_comment_actor_slug( $comment ) {
    if ( $comment->user_id !== '0' ) {
        return get_comment_user_actor_slug( $comment->user_id );
    } else {
        return get_comment_guest_actor_slug( $comment );
    }
}

function get_comment_user_actor_slug( $user_id ) {
    if ( \user_can( $user_id, 'publish_posts' ) ) {
        return PTEROTYPE_BLOG_ACTOR_SLUG;
    } else {
        $user = \get_userdata( $user_id );
        return $user->user_nicename;
    }
}

function get_comment_guest_actor_slug( $comment ) {
    $email_address = $comment->comment_author_email;
    $url = $comment->comment_author_url;
    if ( empty( $url ) ) {
        $url = null;
    }
    $name = $comment->comment_author;
    if ( empty( $name ) ) {
        $name = null;
    }
    $icon = \get_avatar_url( $email_address );
    if ( ! $icon ) {
        $icon = null;
    }
    $slug = \pterotype\actors\upsert_commenter_actor(
        $email_address, $url, $name, $icon
    );
    return $slug;
}

function comment_to_object( $comment, $actor_slug ) {
    $post = \get_post( $comment->comment_post_ID );
    \setup_postdata( $post );
    $post_permalink = \get_permalink( $post );
    $post_object = \pterotype\objects\get_object_by_url( $post_permalink );
    $inReplyTo = $post_object['id'];
    if ( $comment->comment_parent !== '0' ) {
        $parent_comment = \get_comment( $comment->comment_parent );
        $parent_object_activitypub_id = \pterotype\commentlinks\get_object_activitypub_id(
            $parent_comment->comment_ID
        );
        if ( $parent_object_activitypub_id ) {
            $inReplyTo = $parent_object_activitypub_id;
        }
    }
    $link = get_comment_object_url ( \get_comment_link( $comment ) );
    $object = array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Note',
        'content' => $comment->comment_content,
        'attributedTo' => get_rest_url(
            null, sprintf( '/pterotype/v1/actor/%s', $actor_slug )
        ),
        'url' => $link,
        'inReplyTo' => $inReplyTo,
    );
    $existing_activitypub_id = \pterotype\commentlinks\get_object_activitypub_id(
        $comment->comment_ID
    );
    if ( $existing_activitypub_id ) {
        $object['id'] = $existing_activitypub_id;
    }
    return $object;
}

function get_comment_object_url( $comment_link ) {
    $parsed = \wp_parse_url( $comment_link );
    if ( ! $parsed ) {
        return;
    }
    $anchor = $parsed['fragment'];
    $base = $parsed['scheme'] . '://' . $parsed['host'];
    if ( array_key_exists( 'port', $parsed ) ) {
        $base = $base . ':' . $parsed['port'];
    }
    return $base . $parsed['path'] . '?pterotype_comment=' . $anchor;
}

function get_comment_to( $comment, $followers_id ) {
    $to = array(
        'https://www.w3.org/ns/activitystreams#Public',
        $followers_id,
    );
    $to = array_values( array_unique( array_merge( $to, traverse_reply_chain( $comment ) ) ) );
    return $to;
}

function traverse_reply_chain( $comment ) {
    return traverse_reply_chain_helper( $comment, 0, array() );
}

function traverse_reply_chain_helper( $object, $depth, $acc ) {
    if ( $depth === 50 ) {
        return $acc;
    }
    if ( ! array_key_exists( 'inReplyTo', $object ) ) {
        return $acc;
    }
    $parent = \pterotype\util\dereference_object( $object['inReplyTo'] );
    $recipients = array();
    foreach( array( 'to', 'bto', 'cc', 'bcc', 'audience' ) as $field ) {
        if ( array_key_exists( $field, $parent ) ) {
            $new_recipients = $parent[$field];
            if ( ! is_array( $new_recipients ) ) {
                $new_recipients = array( $new_recipients );
            }
            $recipients = array_unique( array_merge( $recipients, $new_recipients ) );
        }
    }
    if ( array_key_exists( 'attributedTo', $parent ) ) {
        $attributed_to = \pterotype\util\dereference_object( $parent['attributedTo'] );
        $recipients[] = $attributed_to['id'];
    }
    if ( array_key_exists( 'actor', $parent ) ) {
        $actor = \pterotype\util\dereference_object( $parent['actor'] );
        $recipients[] = $actor['id'];
    }
    $recipients = array_values( array_unique( $recipients ) );
    return traverse_reply_chain_helper(
        $parent, $depth + 1, array_values( array_unique( array_merge( $acc, $recipients ) ) )
    );
}

function get_avatar_filter( $avatar, $comment, $size, $default, $alt ) {
    if ( ! is_object( $comment )
         || ! isset( $comment->comment_ID )
         || $comment->user_id !== '0' ) {
        return $avatar;
    }
    $comment_id = $comment->comment_ID;
    $object_id = \pterotype\commentlinks\get_object_id( $comment_id );
    if ( ! $object_id ) {
        return $avatar;
    }
    $object = \pterotype\objects\get_object( $object_id );
    if ( ! $object || is_wp_error( $object ) || ! array_key_exists( 'attributedTo', $object ) ) {
        return $avatar;
    }
    $actor = \pterotype\util\dereference_object( $object['attributedTo'] );
    if ( ! $actor || is_wp_error( $actor ) || ! array_key_exists( 'icon', $actor ) ) {
        return $avatar;
    }
    $icon = $actor['icon'];
    if ( ! $icon || ! is_array( $icon ) || ! array_key_exists( 'url', $icon ) ) {
        return $avatar;
    }
    $src = $icon['url'];
    return "<img alt='{$alt}'
                src='{$src}'
                class='avatar avatar-{$size}'
                height='{$size}'
                width='{$size}' />";
}
?>
