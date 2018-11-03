<?php
namespace pterotype\async;

require_once plugin_dir_path( __FILE__ ) . 'outbox.php';
require_once plugin_dir_path( __FILE__ ) . '../client/comments.php';

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

class Handle_Comment_Post_Task extends \WP_Async_Task {
    protected $action = 'pterotype_handle_comment_post';

    protected function prepare_data( $data ) {
        $comment_id = $data[0];
        $comment_approved = $data[1];
        return array( 'comment_id' => $comment_id, 'comment_approved' => $comment_approved );
    }

    protected function run_action() {
        $comment_id = $_POST['comment_id'];
        $comment_approved = $_POST['comment_approved'];
        if ( $comment_approved ) {
            // There's potentially a race between this task and linking the comment
            // in activities\create. It should be okay since getting the comment takes
            // some time, but something to keep in mind
            $comment = \get_comment( $comment_id );
            \pterotype\comments\handle_transition_comment_status(
                'approved', 'nonexistent', $comment
            );
        }
    }
}

function init_tasks() {
    new Send_Accept_Task();
    new Handle_Comment_Post_Task();
}
?>
