<?php
namespace pterotype\async;

require_once plugin_dir_path( __FILE__ ) . 'outbox.php';

class Send_Accept_Task extends \WP_Async_Task {
    protected $action = 'pterotype_send_accept';

    protected function prepare_data( $data ) {
        $actor_slug = $data[0];
        $accept = $data[1];
        return array( 'actor_slug' => $actor_slug, 'accept' => $accept );
    }

    protected function run_action() {
        $actor_slug = $_POST['actor_slug'];
        $accept = $_POST['accept'];
        if ( $actor_slug && $accept ) {
            sleep( 5 );
            \pterotype\outbox\handle_activity( $actor_slug, $accept );
        }
    }
}

function init_tasks() {
    new Send_Accept_Task();
}
?>
