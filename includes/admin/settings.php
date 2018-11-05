<?php
namespace pterotype\settings;

function register_settings_sections() {
    \add_settings_section(
        'pterotype_identity',
        'Fediverse Identity',
        '',
        'pterotype'
    );
}

function register_settings_fields() {
    \add_settings_field(
        'pterotype_blog_name',
        'Site Name',
        function() {
            // TODO fill this with the existing option or the site default if not set
            ?>
            <input type="text" name="pterotype_blog_name">
            <?php
        },
        'pterotype',
        'pterotype_identity'
    );
}
?>
