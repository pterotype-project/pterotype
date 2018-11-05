<?php
namespace pterotype\admin;

require_once plugin_dir_path( __FILE__ ) . 'icon.php';

function render_admin_html() {
    if ( ! \current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?= \esc_html(\get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            \settings_fields('pterotype_settings');
            \do_settings_sections('pterotype');
            \submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

function register_admin_page() {
    \add_menu_page(
        'Pterotype',
        'Pterotype ',
        'manage_options',
        'pterotype',
        '\pterotype\admin\render_admin_html',
        \pterotype\admin\icon\icon_uri()
    );
}
