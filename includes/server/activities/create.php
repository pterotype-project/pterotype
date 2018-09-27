<?php
namespace activities\create;

require_once plugin_dir_path( __FILE__ ) . '../objects.php';

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
    $object = \objects\create_local_object( $object );
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
    $object = \objects\upsert_object( $object );
    if ( is_wp_error( $object ) ) {
        return $object;
    }
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
?>
