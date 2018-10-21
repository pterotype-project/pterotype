<?php
namespace pterotype\pgp;

function gen_key( $actor_slug ) {
    $rsa = new \phpseclib\Crypt\RSA();
    return $rsa->createKey( 2048 );
}

function persist_key( $actor_id, $public_key, $private_key ) {
    global $wpdb;
    return $wpdb->replace(
        $wpdb->prefix . 'pterotype_keys',
        array(
            'actor_id' => $actor_id,
            'public_key' => $public_key,
            'private_key' => $private_key
        ),
        array( '%d', '%s', '%s' )
    );
}

function sign_data( $data, $actor_id ) {
    $secret_key = get_private_key( $actor_id );
    $sig = null;
    openssl_sign( $data, $sig, $secret_key, OPENSSL_ALGO_SHA256 );
    if ( ! $sig ) {
        return new \WP_Error(
            'pgp_error',
            __( 'Unable to sign data', 'pterotype' )
        );
    }
    return base64_encode( $sig );
}

function get_public_key( $actor_id ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        "SELECT public_key FROM {$wpdb->prefix}pterotype_keys WHERE actor_id = %d",
        $actor_id
    ) );
}

function get_private_key( $actor_id ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare(
        "
       SELECT private_key FROM {$wpdb->prefix}pterotype_keys WHERE actor_id = %d
       ",
        $actor_id
    ) );
}
?>
