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
    \register_setting( 'pterotype_settings', 'pterotype_blog_icon', array(
        'type' => 'string',
        'description' => __( "The URL of the site's icon in the Fediverse", 'pterotype' ),
        'show_in_rest' => true,
    ) );
    \add_settings_section(
        'pterotype_identity',
        __( 'Fediverse Identity', 'pterotype' ),
        function() {
            echo '<p>' . __(
                'These settings determine how your blog will look in other Fediverse apps',
                'pterotype'
            ) . '</p>';
        },
        'pterotype'
    );
}

function register_settings_fields() {
    \add_settings_field(
        'pterotype_blog_name',
        __( 'Site Name', 'pterotype' ),
        function() {
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
        __( 'Site Description', 'pterotype' ),
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
    \add_settings_field(
        'pterotype_blog_icon',
        __( 'Site Icon', 'pterotype' ),
        function() {
            \wp_enqueue_media();
            \wp_enqueue_script(
                'pterotype_media_script',
                \plugin_dir_url( __FILE__ ) . '../../js/icon-upload.js'
            );
            ?>
            <div class="image-preview-wrapper">
                <img id="pterotype_blog_icon_image"
                     src="<?php echo get_blog_icon_value(); ?>"
                     width="100px"
                     height="100px"
                     style="width: 100px; max-height: 100px;">
            </div>
            <input type="hidden"
                   name="pterotype_blog_icon"
                   id="pterotype_blog_icon"
                   value="<?php echo get_blog_icon_value(); ?>">
            <input id="pterotype_blog_icon_button"
                   type="button"
                   class="button"
                   value="<?php _e( 'Choose icon', 'pterotype' ) ?>">
           <?php
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

function get_blog_icon_value() {
    $icon = \get_option( 'pterotype_blog_icon' );
    if ( $icon && ! empty( $icon ) ) {
        return $icon;
    }
    if ( \has_custom_logo() ) {
        $theme_mod = \wp_get_attachment_image_src( \get_theme_mod( 'custom_logo' ) );
        return $theme_mod[0];
    }
    return null;
}
?>
