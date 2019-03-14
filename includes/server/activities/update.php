<?php
namespace pterotype\activities\update;

require_once plugin_dir_path( __FILE__ ) . '../objects.php';
require_once plugin_dir_path( __FILE__ ) . '../../commentlinks.php';
require_once plugin_dir_path( __FILE__ ) . '../../util.php';
require_once plugin_dir_path( __FILE__ ) . 'create.php';

function handle_outbox( $actor_slug, $activity ) {
    if ( !(array_key_exists( 'type', $activity ) && $activity['type'] === 'Update') ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expecting an Update activity', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expecting an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $update_object = \pterotype\util\dereference_object( $activity['object'] );
    if ( !array_key_exists( 'id', $update_object ) ) {
        return new \WP_Error(
            'invalid_object',
            __( 'Object must have an "id" parameter', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $existing_object = \pterotype\objects\get_object_by_activitypub_id( $update_object['id'] );
    if ( is_wp_error( $existing_object ) ) {
        return $existing_object;
    }
    $updated_object = array_merge( $existing_object, $update_object );
    $updated_object = \pterotype\objects\update_object( $updated_object );
    if ( is_wp_error( $updated_object ) ) {
        return $updated_object;
    }
    return $activity;
}

function handle_inbox( $actor_slug, $activity ) {
    if ( !(array_key_exists( 'type', $activity ) && $activity['type'] === 'Update') ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expecting an Update activity', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'id', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Activities must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expecting an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = \pterotype\util\dereference_object( $activity['object'] );
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Objects must have an "id" field', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $authorized = check_authorization( $activity );
    if ( is_wp_error( $authorized ) ) {
        return $authorized;
    }
    $object_row = \pterotype\objects\upsert_object( $object );
    if ( is_wp_error( $object_row ) ) {
        return $object_row;
    }
    update_linked_comment( $object_row->object );
    return $activity;
}

function check_authorization( $activity ) {
    $object = $activity['object'];
    $parsed_activity_id = parse_url( $activity['id'] );
    $activity_origin = $parsed_activity_id['host'];
    $parsed_object_id = parse_url( $object['id'] );
    $object_origin = $parsed_object_id['host'];
    if ( ( !$activity_origin || !$object_origin ) || $activity_origin !== $object_origin ) {
        return new \WP_Error(
            'unauthorized',
            __( 'Unauthorized Update activity', 'pterotype' ),
            array( 'status' => 403 )
        );
    }
    return true;
}

function make_update( $actor_slug, $object ) {
    $actor = \pterotype\actors\get_actor_by_slug( $actor_slug );
    if ( is_wp_error( $actor ) ) {
        return $actor;
    }
    return array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Update',
        'actor' => $actor['id'],
        'object' => $object
    );
}

function update_linked_comment( $updated_object ) {
    $object_id = \pterotype\objects\get_object_id( $updated_object['id'] );
    $comment_id = \pterotype\commentlinks\get_comment_id( $object_id );
    if ( ! $comment_id ) {
        return;
    }
    $comment = \get_comment( $comment_id );
    if ( ! $comment || is_wp_error( $comment ) ) {
        return;
    }
    $post_id = $comment->comment_post_ID;
    $comment_parent = null;
    if ( $comment->comment_parent !== '0' ) {
        $comment_parent = $comment->comment_parent;
    }
    $updated_comment = \pterotype\activities\create\make_comment_from_object(
        $updated_object, $post_id, $comment_parent
    );
    $updated_comment['comment_ID'] = $comment->comment_ID;
    if ( $comment != $updated_comment ) {
        \wp_update_comment( $updated_comment );
    }
}
?>
