<?php
namespace pgp;

function gen_key( $actor_slug ) {
    $rsa = new \phpseclib\Crypt\RSA();
    return $rsa->createKey( 2048 );
}

function persist_key( $actor_id, $public_key, $private_key ) {
    global $wpdb;
    return $wpdb->replace(
        'pterotype_keys',
        array(
            'actor_id' => $actor_id,
            'public_key' => $public_key,
            'private_key' => $private_key
        ),
        array( '%d', '%s', '%s' )
    );
}

function get_public_key( $actor_id ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        'SELECT public_key FROM pterotype_keys WHERE actor_id = %d', $actor_id
    ) );
}
?>
