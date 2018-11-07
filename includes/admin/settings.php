<?php
namespace pterotype\settings;

function register_settings_sections() {
    \register_setting( 'pterotype_settings', 'pterotype_blog_name', array(
        'type' => 'string',
        'description' => __( "The site's name in the Fediverse", 'pterotype' ),
        'show_in_rest' => true,
    ) );
    \register_setting( 'pterotype_settings', 'pterotype_blog_description', array(
        'type' => 'string',
        'description' => __( "The site's description in the Fediverse", 'pterotype' ),
        'show_in_rest' => true,
    ) );
    \add_settings_section(
        'pterotype_identity',
        'Fediverse Identity',
        function() {
        ?><p>These settings determine how your blog will look in other Fediverse apps</p><?php
        },
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
            <input type="text"
                   name="pterotype_blog_name"
                   value="<?php echo get_blog_name_value(); ?>">
            <?php
        },
        'pterotype',
        'pterotype_identity'
    );
    \add_settings_field(
        'pterotype_blog_description',
        'Site Description',
        function() {
            \wp_editor( get_blog_description_value(), 'pterotype_blog_description', array(
                'teeny' => true,
                'textarea_rows' => 20,
                'wpautop' => false,
                'media_buttons' => false,
                'editor_css' => '<style>.wp-editor-wrap { max-width: 768px; }</style>'
            ) );
        },
        'pterotype',
        'pterotype_identity'
    );
}

function get_blog_name_value() {
    $name = \get_option( 'pterotype_blog_name' );
    if ( $name && ! empty( $name ) ) {
        return $name;
    }
    return \get_bloginfo( 'name' );
}

function get_blog_description_value() {
    $description = \get_option( 'pterotype_blog_description' );
    if ( $description && ! empty( $description ) ) {
        return $description;
    }
    return \get_bloginfo( 'description' );
}
?>
