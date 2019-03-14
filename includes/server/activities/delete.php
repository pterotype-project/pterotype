<?php
namespace pterotype\activities\delete;

require_once plugin_dir_path( __FILE__ ) . '../objects.php';
require_once plugin_dir_path( __FILE__ ) . '../actors.php';
require_once plugin_dir_path( __FILE__ ) . '../../commentlinks.php';
require_once plugin_dir_path( __FILE__ ) . '../../util.php';

function handle_outbox( $actor, $activity ) {
    if ( !array_key_exists( 'object', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an object', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = $activity['object'];
    $tombstone = \pterotype\objects\delete_object( $object );
    if ( is_wp_error( $tombstone ) ) {
        return $tombstone;
    }
    $activity['object'] = $tombstone;
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
    if ( !array_key_exists( 'id', $activity ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an id', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $object = \pterotype\util\dereference_object( $activity['object'] );
    if ( !array_key_exists( 'id', $object ) ) {
        return new \WP_Error(
            'invalid_activity',
            __( 'Expected an id', 'pterotype' ),
            array( 'status' => 400 )
        );
    }
    $authorized = check_authorization( $activity );
    if ( is_wp_error( $authorized ) ) {
        return $authorized;
    }
    delete_linked_comment( $object );
    $res = \pterotype\objects\delete_object( $object );
    if ( is_wp_error( $res ) ) {
        return $res;
    }
    return $activity;
}

function delete_linked_comment( $object ) {
    $object_id = \pterotype\objects\get_object_id( $object['id'] );
    $comment_id = \pterotype\commentlinks\get_comment_id( $object_id );
    if ( ! $comment_id ) {
        return;
    }
    \pterotype\commentlinks\unlink_comment( $comment_id, $object_id );
    \wp_delete_comment( $comment_id, true );
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

function make_delete( $actor_slug, $object ) {
    $actor = \pterotype\actors\get_actor_by_slug( $actor_slug );
    if ( is_wp_error( $actor ) ) {
        return $actor;
    }
    return array(
        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
        'type' => 'Delete',
        'actor' => $actor['id'],
        'object' => $object
    );
}
?>
