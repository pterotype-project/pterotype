<?php
/**
 * This is https://github.com/willnorris/wordpress-opengraph
 */


// If you have the opengraph plugin running alongside jetpack, we assume you'd
// rather use our opengraph support, so disable jetpack's opengraph functionality.
add_filter( 'jetpack_enable_opengraph', '__return_false' );
add_filter( 'jetpack_enable_open_graph', '__return_false' );


// Disable strict mode by default.
if ( ! defined( 'OPENGRAPH_STRICT_MODE' ) ) {
	define( 'OPENGRAPH_STRICT_MODE', false );
}


/**
 * Add Open Graph XML prefix to <html> element.
 *
 * @uses apply_filters calls 'opengraph_prefixes' filter on RDFa prefix array
 */
function opengraph_add_prefix( $output ) {
	$prefixes = array(
		'og' => 'http://ogp.me/ns#',
	);
	$prefixes = apply_filters( 'opengraph_prefixes', $prefixes );

	$prefix_str = '';
	foreach ( $prefixes as $k => $v ) {
		$prefix_str .= $k . ': ' . $v . ' ';
	}
	$prefix_str = trim( $prefix_str );

	if ( preg_match( '/(prefix\s*=\s*[\"|\'])/i', $output ) ) {
		$output = preg_replace( '/(prefix\s*=\s*[\"|\'])/i', '${1}' . $prefix_str, $output );
	} else {
		$output .= ' prefix="' . $prefix_str . '"';
	}

	return $output;
}
add_filter( 'language_attributes', 'opengraph_add_prefix' );


/**
 * Add additional prefix namespaces that are supported by the opengraph plugin.
 */
function opengraph_additional_prefixes( $prefixes ) {
	if ( is_author() ) {
		$prefixes['profile'] = 'http://ogp.me/ns/profile#';
	}
	if ( is_singular() ) {
		$prefixes['article'] = 'http://ogp.me/ns/article#';
	}

	return $prefixes;
}


/**
 * Get the Open Graph metadata for the current page.
 *
 * @uses apply_filters() Calls 'opengraph_{$name}' for each property name
 * @uses apply_filters() Calls 'twitter_{$name}' for each property name
 * @uses apply_filters() Calls 'opengraph_metadata' before returning metadata array
 */
function opengraph_metadata() {
	$metadata = array();

	 // defualt properties defined at http://ogp.me/
	$properties = array(
		// required
		'title',
		'type',
		'image',
		'url',

		// optional
		'audio',
		'description',
		'determiner',
		'locale',
		'site_name',
		'video',
	);

	foreach ( $properties as $property ) {
		$filter = 'opengraph_' . $property;
		$metadata[ "og:$property" ] = apply_filters( $filter, '' );
	}

	$twitter_properties = array( 'card', 'creator' );

	foreach ( $twitter_properties as $property ) {
		$filter = 'twitter_' . $property;
		$metadata[ "twitter:$property" ] = apply_filters( $filter, '' );
	}

	return apply_filters( 'opengraph_metadata', $metadata );
}


/**
 * Register filters for default Open Graph metadata.
 */
function opengraph_default_metadata() {
	// core metadata attributes
	add_filter( 'opengraph_title', 'opengraph_default_title', 5 );
	add_filter( 'opengraph_type', 'opengraph_default_type', 5 );
	add_filter( 'opengraph_image', 'opengraph_default_image', 5 );
	add_filter( 'opengraph_url', 'opengraph_default_url', 5 );

	add_filter( 'opengraph_description', 'opengraph_default_description', 5 );
	add_filter( 'opengraph_locale', 'opengraph_default_locale', 5 );
	add_filter( 'opengraph_site_name', 'opengraph_default_sitename', 5 );

	// additional prefixes
	add_filter( 'opengraph_prefixes', 'opengraph_additional_prefixes' );

	// additional profile metadata
	add_filter( 'opengraph_metadata', 'opengraph_profile_metadata' );

	// additional article metadata
	add_filter( 'opengraph_metadata', 'opengraph_article_metadata' );

	// twitter card metadata
	add_filter( 'twitter_card', 'twitter_default_card', 5 );
	add_filter( 'twitter_creator', 'twitter_default_creator', 5 );
}
add_action( 'wp', 'opengraph_default_metadata' );


/**
 * Default title property, using the page title.
 */
function opengraph_default_title( $title ) {
	if ( $title ) {
		return $title;
	}

	if ( is_singular() ) {
		$title = get_the_title( get_queried_object_id() );
	} else if ( is_author() ) {
		$author = get_queried_object();
		$title = $author->display_name;
	} else if ( is_category() && single_cat_title( '', false ) ) {
		$title = single_cat_title( '', false );
	} else if ( is_tag() && single_tag_title( '', false ) ) {
		$title = single_tag_title( '', false );
	} else if ( is_archive() && get_post_format() ) {
		$title = get_post_format_string( get_post_format() );
	} else if ( is_archive() && function_exists( 'get_the_archive_title' ) && get_the_archive_title() ) { // new in version 4.1 to get all other archive titles
		$title = get_the_archive_title();
	}

	return $title;
}


/**
 * Default type property.
 */
function opengraph_default_type( $type ) {
	if ( empty( $type ) ) {
		if ( is_singular( array( 'post', 'page' ) ) ) {
			$type = 'article';
		} else if ( is_author() ) {
			$type = 'profile';
		} else {
			$type = 'website';
		}
	}

	return $type;
}


/**
 * Default image property, using the post-thumbnail and any attached images.
 */
function opengraph_default_image( $image ) {
	if ( $image ) {
		return $image;
	}

	// As of July 2014, Facebook seems to only let you select from the first 3 images
	$max_images = apply_filters( 'opengraph_max_images', 3 );

	// max images can't be negative or zero
	if ( $max_images <= 0 ) {
		$max_images = 1;
	}

	if ( is_singular() ) {
		$id = get_queried_object_id();
		$image_ids = array();

		// list post thumbnail first if this post has one
		if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( $id ) ) {
			$image_ids[] = get_post_thumbnail_id( $id );
			$max_images--;
		}

		// then list any image attachments
		$query = new WP_Query(
			array(
				'post_parent' => $id,
				'post_status' => 'inherit',
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'order' => 'ASC',
				'orderby' => 'menu_order ID',
				'posts_per_page' => $max_images,
			)
		);

		foreach ( $query->get_posts() as $attachment ) {
			if ( ! in_array( $attachment->ID, $image_ids ) ) {
				$image_ids[] = $attachment->ID;
			}
		}

		// get URLs for each image
		$image = array();
		foreach ( $image_ids as $id ) {
			$thumbnail = wp_get_attachment_image_src( $id, 'full' );
			if ( $thumbnail ) {
				$image[] = $thumbnail[0];
			}
		}
	} elseif ( is_attachment() && wp_attachment_is_image() ) {
		$id = get_queried_object_id();
		$image = array( wp_get_attachment_url( $id ) );
	}

	if ( empty( $image ) ) {
		$image = array();

		// add site icon
		if ( function_exists( 'get_site_icon_url' ) && has_site_icon() ) {
			$image[] = get_site_icon_url( 512 );
		}

		// add header images
		if ( function_exists( 'get_uploaded_header_images' ) ) {
			if ( is_random_header_image() ) {
				foreach ( get_uploaded_header_images() as $header_image ) {
					$image[] = $header_image['url'];

					if ( sizeof( $image ) >= $max_images ) {
						break;
					}
				}
			} elseif ( get_header_image() ) {
				$image[] = get_header_image();
			}
		}
	}

	return $image;
}


/**
 * Default url property, using the permalink for the page.
 */
function opengraph_default_url( $url ) {
	if ( empty( $url ) ) {
		if ( is_singular() ) {
			$url = get_permalink();
		} else if ( is_author() ) {
			$url = get_author_posts_url( get_queried_object_id() );
		}
	}

	return $url;
}


/**
 * Default site_name property, using the bloginfo name.
 */
function opengraph_default_sitename( $name ) {
	if ( empty( $name ) ) {
		$name = get_bloginfo( 'name' );
	}

	return $name;
}


/**
 * Default description property, using the excerpt or content for posts, or the
 * bloginfo description.
 */
function opengraph_default_description( $description ) {
	if ( $description ) {
		return $description;
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( ! empty( $post->post_excerpt ) ) {
			$description = $post->post_excerpt;
		} else {
			$description = $post->post_content;
		}
	} else if ( is_author() ) {
		$id = get_queried_object_id();
		$description = get_user_meta( $id, 'description', true );
	} else if ( is_category() && category_description() ) {
		$description = category_description();
	} else if ( is_tag() && tag_description() ) {
		$description = tag_description();
	} else if ( is_archive() && function_exists( 'get_the_archive_description' ) && get_the_archive_description() ) { // new in version 4.1 to get all other archive descriptions
		$description = get_the_archive_description();
	} else {
		$description = get_bloginfo( 'description' );
	}

	// strip description to first 55 words.
	$description = strip_tags( strip_shortcodes( $description ) );
	$description = __opengraph_trim_text( $description );

	return $description;
}


/**
 * Default locale property, using the WordPress locale.
 */
function opengraph_default_locale( $locale ) {
	if ( empty( $locale ) ) {
		$locale = get_locale();
	}

	return $locale;
}


/**
 * Default twitter-card type.
 */
function twitter_default_card( $card ) {
	if ( $card ) {
		return $card;
	}

	$card = 'summary';
	$images = apply_filters( 'opengraph_image', null );

	if ( is_singular() && count( $images ) >= 1 ) {
		$card = 'summary_large_image';
	}

	return $card;
}


/**
 * Default twitter-card creator.
 */
function twitter_default_creator( $creator ) {
	if ( $creator || ! is_singular() ) {
		return $creator;
	}

	$post = get_queried_object();
	$author = $post->post_author;
	$twitter = get_the_author_meta( 'twitter', $author );

	if ( ! $twitter ) {
		return $creator;
	}

	// check if twitter-account matches "http://twitter.com/username"
	if ( preg_match( '/^http:\/\/twitter\.com\/(#!\/)?(\w+)/i', $twitter, $matches ) ) {
		$creator = '@' . $matches[2];
	} elseif ( preg_match( '/^@?(\w+)$/i', $twitter, $matches ) ) { // check if twitter-account matches "(@)username"
		$creator = '@' . $matches[1];
	}

	return $creator;
}


/**
 * Output Open Graph <meta> tags in the page header.
 */
function opengraph_meta_tags() {
	$metadata = opengraph_metadata();
	foreach ( $metadata as $key => $value ) {
		if ( empty( $key ) || empty( $value ) ) {
			continue;
		}
		$value = (array) $value;

		foreach ( $value as $v ) {
			// check if "strict mode" is enabled
			if ( OPENGRAPH_STRICT_MODE === false ) {
				// use "property" and "name"
				printf('<meta property="%1$s" name="%1$s" content="%2$s" />' . PHP_EOL,
				esc_attr( $key ), esc_attr( $v ) );
			} else {
				// use "name" attribute for Twitter Cards
				if ( stripos( $key, 'twitter:' ) === 0 ) {
					printf( '<meta name="%1$s" content="%2$s" />' . PHP_EOL,
					esc_attr( $key ), esc_attr( $v ) );
				} else { // use "property" attribute for Open Graph
					printf( '<meta property="%1$s" content="%2$s" />' . PHP_EOL,
					esc_attr( $key ), esc_attr( $v ) );
				}
			}
		}
	}
}
add_action( 'wp_head', 'opengraph_meta_tags' );


/**
 * Include profile metadata for author pages.
 *
 * @link http://ogp.me/#type_profile
 */
function opengraph_profile_metadata( $metadata ) {
	if ( is_author() ) {
		$id = get_queried_object_id();
		$metadata['profile:first_name'] = get_the_author_meta( 'first_name', $id );
		$metadata['profile:last_name'] = get_the_author_meta( 'last_name', $id );
		$metadata['profile:username'] = get_the_author_meta( 'nicename', $id );
	}

	return $metadata;
}


/**
 * Include article metadata for posts and pages.
 *
 * @link http://ogp.me/#type_article
 */
function opengraph_article_metadata( $metadata ) {
	if ( ! is_singular() ) {
		return $metadata;
	}

	$post = get_queried_object();
	$author = $post->post_author;

	// check if page/post has tags
	$tags = wp_get_object_terms( $post->ID, 'post_tag' );
	if ( $tags && is_array( $tags ) ) {
		foreach ( $tags as $tag ) {
			$metadata['article:tag'][] = $tag->name;
		}
	}

	// check if page/post has categories
	$categories = wp_get_object_terms( $post->ID, 'category' );
	if ( $categories && is_array( $categories ) ) {
		$metadata['article:section'][] = current( $categories )->name;
	}

	$metadata['article:published_time'] = get_the_time( 'c', $post->ID );
	$metadata['article:modified_time'] = get_the_modified_time( 'c', $post->ID );
	$metadata['article:author'][] = get_author_posts_url( $author );

	$facebook = get_the_author_meta( 'facebook', $author );

	if ( ! empty( $facebook ) ) {
		$metadata['article:author'][] = $facebook;
	}

	return $metadata;
}


/**
 * Add "twitter" as a contact method
 */
function opengraph_user_contactmethods( $user_contactmethods ) {
	$user_contactmethods['twitter'] = __( 'Twitter', 'opengraph' );
	$user_contactmethods['facebook'] = __( 'Facebook (Profile URL)', 'opengraph' );

	return $user_contactmethods;
}
add_filter( 'user_contactmethods', 'opengraph_user_contactmethods', 1 );


/**
 * Add 512x512 icon size
 *
 * @param  array $sizes sizes available for the site icon
 * @return array        updated list of icons
 */
function opengraph_site_icon_image_sizes( $sizes ) {
	$sizes[] = 512;

	return array_unique( $sizes );
}
add_filter( 'site_icon_image_sizes', 'opengraph_site_icon_image_sizes' );


/**
 * Helper function to trim text using the same default values for length and
 * 'more' text as wp_trim_excerpt.
 */
function __opengraph_trim_text( $text ) {
	$excerpt_length = apply_filters( 'excerpt_length', 55 );
	$excerpt_more = apply_filters( 'excerpt_more', ' [...]' );

	return wp_trim_words( $text, $excerpt_length, $excerpt_more );
}
