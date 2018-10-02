<?php
namespace webfinger;

function generate_rewrite_rules( $wp_rewrite ) {
    $dot_well_known = array(
        '.well-known/webfinger' => 'index.php?well-known=webfinger'
    );
    $wp_rewrite->rules = $dot_well_known + $wp_rewrite->rules;
}

function parse_request( $req ) {
    if ( ! array_key_exists( 'well-known', $req->query_vars ) ) {
        return;
    }
    if ( $req->query_vars['well-known'] === 'webfinger' ) {
        do_action( 'well_known_webfinger', $req->query_vars );
    }
}

function query_vars( $query_vars ) {
    $query_vars[] = 'well-known';
    return $query_vars;
}

function handle( $query ) {
    echo var_dump( $query );
    exit;
}
?>
