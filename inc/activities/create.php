<?php
namespace activities\create;

/*
Create a new post or comment (depending on $activity["object"]["type"]),
copying $activity["actor"] to the object's "attributedTo" and
copying any recipients of the activity that aren't on the object
to the object and vice-versa.
*/
function handle( $actor, $activity ) {
    if ( !(array_key_exists( "type", $activity ) && $activity["type"] === "Create") ) {
        return new WP_Error(
            'invalid_activity', __( 'Expecting a Create activity', 'activitypub' )
        );
    }
    if ( !array_key_exists( "object", $activity ) ) {
        return new WP_Error(
            'invalid_object', __( 'Expecting an object', 'activitypub' )
        );
    }
    if ( !array_key_exists( "actor", $activity ) ) {
        // TODO validate that $activity["actor"] is the URL of the $actor
        return new WP_Error(
            'invalid_actor', __( 'Expecting a valid actor', 'activitypub' )
        );
    }
    $object = $activity["object"];
    $actor_id = $activity["actor"];
    $object["attributedTo"] = $actor_id;

}


function reconcile_receivers( $object, $activity ) {
    // TODO copy "audience", "to" "bto", "cc", "bcc"
    // to both object and activity from each other
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
?>
