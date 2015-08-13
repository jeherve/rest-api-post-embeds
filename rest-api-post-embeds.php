<?php
/*
 * Plugin Name: REST API Post Embeds
 * Plugin URI: http://wordpress.org/plugins/rest-api-post-embeds
 * Description: Embed posts from your site or others' into your posts and pages.
 * Author: Jeremy Herve
 * Version: 1.0
 * Author URI: http://jeremy.hu
 * License: GPL2+
 * Textdomain: jeherve_post_embed
 */

class Jeherve_Post_Embeds {
	private static $instance;

	static function get_instance() {
		if ( ! self::$instance )
			self::$instance = new Jeherve_Post_Embeds;

		return self::$instance;
	}

	private function __construct() {
		add_filter(    'jeherve_post_embed_blog_id',          array( $this, 'get_blog_details' ), 10, 1 );
		add_filter(    'jeherve_post_embed_query_url',        array( $this, 'build_query_URL' ), 10, 3 );
		add_filter(    'jeherve_post_embed_post_list_before', array( $this, 'headline' ), 10, 2 );
		add_filter(    'jeherve_post_embed_post_list_after',  array( $this, 'credits' ), 10, 2 );
		add_shortcode( 'jeherve_post_embed',                  array( $this, 'jeherve_post_embed_shortcode' ) );
		add_action(    'wp_enqueue_scripts',                  array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		wp_register_style( 'jeherve_post_embed', plugins_url( 'style.css', __FILE__) );
		wp_enqueue_style( 'jeherve_post_embed' );
	}

	/**
	 * Get the WordPress.com blog ID of your own site.
	 *
	 * @since 1.0.0
	 *
	 * @return absint $blog_id WordPress.com blog ID.
	 */
	public function get_blog_details( $blog_id ) {

		// Get the blog's ID
		if ( class_exists( 'Jetpack_Options' ) ) {
			$blog_id = Jetpack_Options::get_option( 'id' );
		}

		if ( $blog_id ) {
			return absint( $blog_id );
		} else {
			return;
		}

	}


	/**
	 * Build the REST API query URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string $string Query URL.
	 */
	public function build_query_URL( $url, $atts, $args ) {

		$blog_id = '';

		/**
		 * Filter the blog ID used to query posts.
		 * By default it's your own site's blog ID.
		 *
		 * @since 1.0.0
		 *
		 * @param string $blog_id Blog identifier. Can be a WordPress.com blog ID, or a normalized Jetpack / WordPress.com site URL (without protocol).
		 */
		$blog_id = apply_filters( 'jeherve_post_embed_blog_id', $blog_id );

		// Overwrite the blog ID if it was defined in the shortcode.
		if ( $atts[ 'url' ] ) {
			$blog_id = urlencode( $atts[ 'url' ] );
		}

		// If no post ID, let's stop right there
		if ( ! $blog_id ) {
			return;
		}

		// Query the WordPress.com REST API URL.
		$url = sprintf( esc_url( 'https://public-api.wordpress.com/rest/v1.1/sites/%s/posts/' ), $blog_id );

		// Add query arguments.
		$base_url = add_query_arg( $args, $url );

		/**
		 * Filter the base query URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $base_url Base API query URL. Defaults to the WordPress.com REST API URL includin the blog ID.
		 * @param array $args Array of query arguments.
		 */
		$base_url = apply_filters( 'jeherve_post_embed_base_api_url', $base_url, $args );

		return esc_url( $base_url );

	}


	/**
	 * Headline.
	 *
	 * @since 1.0.0
	 *
	 * @param string $headline Headline's HTML output.
	 * @param array $atts Array of shortcode attributes.
	 *
	 * @return string $var HTML code to prepend to the post embed list.
	 */
	public function headline( $headline, $atts ) {
		if ( ! isset( $atts[ 'headline' ] ) ) {
			return;
		}

		$headline = sprintf(
			'<h3 class="jeherve-post-embeds-headline">%1$s</h3>',
			esc_html( $atts[ 'headline' ] )
		);

		return $headline;
	}

	/**
	 * Credits HTML output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $credits Credits HTML output.
	 * @param array $atts Array of shortcode attributes.
	 *
	 * @return string $credits Credits HTML output.
	 */
	public function credits( $credits, $atts ) {
		if ( ! true === $atts[ 'include_credits' ] || ! isset( $atts[ 'url' ] ) ) {
			return;
		}

		$credits = '<div class="jeherve-post-embeds-credits">';
		$credits .= sprintf(
			_x( 'Source: <a class="jeherve-post-embeds-credit-link" href="http://%1$s">%1$s</a>', 'Site URL', 'jeherve_post_embed' ),
			$atts[ 'url' ]
		);
		$credits .= '</div>';

		return $credits;
	}


	/**
	 * Get posts.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Array of shortcode attributes.
	 * @param array $args Array of query arguments.
	 *
	 * @return string post_list()
	 */
	public function get_posts( $atts, $args ) {

		// If we didn't get data from the shortcode, stop right now.
		if ( ! $atts || ! $args ) {
			return;
		}

		/**
		 * Filter the Query URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url URL querying an API to get a list of posts in a JSON format.
		 * @param array $atts Array of shortcode attributes.
		 * @param array $args Array of query arguments.
		 */
		$query_url = apply_filters( 'jeherve_post_embed_query_url', '', $atts, $args );

		// Build a hash of the query URL. We'll use it later when building the transient.
		if ( $query_url ) {
			$query_hash = substr( md5( $query_url ), 0, 21 );
		} else {
			return;
		}

		// Look for data in our transient. If nothing, let's get a list of posts to display
		$data_from_cache = get_transient( 'jeherve_post_embed_' . $query_hash );
		if ( false === $data_from_cache ) {
			$response = wp_remote_get( esc_url_raw( $query_url ) );
			set_transient( 'jeherve_post_embed_' . $query_hash, $response, 10 * MINUTE_IN_SECONDS );
		} else {
			$response = $data_from_cache;
		}

		if ( is_wp_error( $response ) ) {
			return '<p>' . __( 'Error in the response. We cannot load blog data at this time. Make sure Jetpack is properly connected on the site.', 'jeherve_post_embed' ) . '</p>';
		}

		// Let's start working with our list of posts
		$posts_info = json_decode( $response[ 'body' ] );

		// If we get an error in that response, let's give up now
		if ( isset( $posts_info->error ) && 'jetpack_error' == $posts_info->error ) {
			return '<p>' . __( 'Error in the posts being returned. We cannot load blog data at this time.', 'jeherve_post_embed' ) . '</p>';
		}

		/**
		 * How many posts should we display?
		 * Check if we got that data from the shortcode.
		 */
		if ( isset( $args[ 'number' ] ) ) {
			$number_of_posts = $args[ 'number' ];
		} else {
			return;
		}

		// Build our list
		return $this->post_list( $posts_info, $number_of_posts, $atts );

	}


	/**
	 * Build the post list
	 *
	 * @since 1.0.0
	 *
	 * @param array $posts_info Array of data about our posts.
	 * @param int $number_of_posts Number of posts we want to display.
	 * @param array $atts Array of shortcode attributes.
	 *
	 * @return string $list_layout HTML output of our posts.
	 */
	public function post_list( $posts_info, $number_of_posts, $atts ) {

		$class = ( $atts[ 'wrapper_class' ] ) ? $atts[ 'wrapper_class' ] : '';

		$list_layout = '<div class="jeherve-post-embeds ' . $class .  '">' . "\n";

		/**
		 * Insert content before the list of posts, inside the div.
		 *
		 * @since 1.0.0
		 *
		 * @param string $var HTML code to prepend to the post embed list.
		 * @param array $atts Array of shortcode attributes.
		 */
		$list_layout .= apply_filters( 'jeherve_post_embed_post_list_before', '', $atts );

		for ( $i = 0; $i < $number_of_posts; $i++ ) {
			$single_post = $posts_info->posts[$i];

			$article = '';

			// Title
			$post_title = ( $single_post->title ) ? $single_post->title : __( ' ( No Title ) ', 'jeherve_post_embed' );
			$article .= sprintf(
				'<h4 class="post-embed-post-title"><a href="%1$s">%2$s</a></h4>',
				esc_url( $single_post->URL ),
				esc_html( $post_title )
			);

			// Featured Image
			if (
				isset( $atts[ 'include_images' ] ) &&
				( isset( $single_post->featured_image ) && ! empty ( $single_post->featured_image ) )
			) {

				// Let's get the theme's content_width and use that as max image width unless specified otherwise via the filter.
				global $content_width;
				if ( isset( $content_width ) ) {
					$image_params = array( 'w' => $content_width );
				}

				// If we have image size data from the shortcode, let's use it.
				if ( isset( $atts[ 'image_size' ] ) ) {
					$image_params = array( 'resize' => $atts[ 'image_size' ] );
				}

				/**
				 * Allows setting up custom Photon parameters to manipulate the image output in the Display Posts widget.
				 *
				 * @see https://developer.wordpress.com/docs/photon/
				 *
				 * @since 1.0.0
				 *
				 * @param array $image_params Array of Photon Parameters.
				 */
				$image_params = apply_filters( 'jeherve_post_embed_image_params', $image_params );

				$article .= sprintf(
					'<div class="post-embed-post-thumbnail"><a title="%1$s" href="%2$s"><img src="%3$s" alt="%1$s"/></a></div>',
					esc_attr( $post_title ),
					esc_url( $single_post->URL ),
					apply_filters( 'jetpack_photon_url', $single_post->featured_image, $image_params )
				);

			}

			// Excerpt.
			if ( true === $atts[ 'include_excerpt' ] &&
				( isset( $single_post->excerpt ) && ! empty( $single_post->excerpt ) )
			) {
				$article .= sprintf (
					'<div class="post-embed-post-excerpt">%s</div>',
					$single_post->excerpt
				);
			}

			// Full post content.
			if ( true === $atts[ 'include_content' ] &&
			( isset( $single_post->content ) && ! empty( $single_post->content ) )
			) {
				$article .= sprintf (
					'<div class="post-embed-post-content">%s</div>',
					$single_post->content
				);
			}

			// Wrap the article in a div.
			$article = sprintf(
				'<div class="post-embed-article">%s</div>',
				$article
			);

			/**
			 * Filters the layout of a single article in the list.
			 *
			 * @since 1.0.0
			 *
			 * @param string $article Article layout.
			 * @param array $single_post Array of information about the post.
			 */
			$article = apply_filters( 'jeherve_post_embed_article_layout', $article, $single_post );

			// Add article to list
			$list_layout .= $article;

		}

		/**
		 * Insert content after the list of posts, inside the div.
		 *
		 * @since 1.0.0
		 *
		 * @param string $var HTML code to append to the post embed list.
		 * @param array $atts Array of shortcode attributes.
		 */
		$list_layout .= apply_filters( 'jeherve_post_embed_post_list_after', '', $atts );

		$list_layout .= "\n" . '</div>' . "\n";

		return $list_layout;
	}

	/**
	 * Create a shortcode so people can output the list anywhere they want.
	 */
	public function jeherve_post_embed_shortcode( $atts ) {

		// Default Shortcode attributes
		$atts = shortcode_atts( array(
			'ignore_sticky_posts' => false,
			'include_images'      => true,
			'include_excerpt'     => true,
			'include_content'     => false,
			'include_credits'     => true,
			'image_size'          => false,
			'order'               => 'DESC',
			'order_by'            => 'date',
			'number'              => '20',
			'after'               => '',
			'before'              => '',
			'tag'                 => '',
			'category'            => '',
			'type'                => 'post',
			'exclude'             => '',
			'status'              => 'publish',
			'author'              => '',
			'wrapper_class'       => '',
			'url'                 => '',
			'headline'            => '',
		), $atts, 'jeherve_post_embed' );

		// Sanitize every attribute
		$ignore_sticky_posts = $this->jeherve_post_embed_convert_string_bool( $atts[ 'ignore_sticky_posts' ] );
		$include_images      = $this->jeherve_post_embed_convert_string_bool( $atts[ 'include_images' ] );
		$include_excerpt     = $this->jeherve_post_embed_convert_string_bool( $atts[ 'include_excerpt' ] );
		$include_content     = $this->jeherve_post_embed_convert_string_bool( $atts[ 'include_content' ] );
		$include_credits     = $this->jeherve_post_embed_convert_string_bool( $atts[ 'include_credits' ] );
		$image_size          = $atts[ 'image_size' ]; // Validated below.
		$order               = sanitize_key( $atts[ 'order' ] );
		$order_by            = sanitize_key( $atts[ 'order_by' ] );
		$number              = intval( $atts[ 'number' ] );
		$before              = $this->jeherve_post_embed_convert_date( $atts[ 'before' ] );
		$after               = $this->jeherve_post_embed_convert_date( $atts[ 'after' ] );
		$tag                 = sanitize_text_field( $atts[ 'tag' ] );
		$category            = sanitize_text_field( $atts[ 'category' ] );
		$type                = sanitize_text_field( $atts[ 'type' ] );
		$exclude             = $atts[ 'exclude' ]; // Validated below.
		$status              = $atts[ 'status' ]; // Validated below.
		$author              = intval( $atts[ 'author' ] );
		$wrapper_class       = sanitize_html_class( $atts[ 'wrapper_class' ] );
		$url                 = $atts[ 'url' ]; // Validated below.
		$headline            = sanitize_text_field( $atts[ 'headline' ] );


		// Sticky Posts
		if ( $ignore_sticky_posts ) {
			$args[ 'sticky' ] = '1';
		}

		// Should we include images?
		if ( $include_images ) {
			$atts[ 'include_images' ] = true;
		}

		// Should we include excerpts?
		if ( $include_excerpt ) {
			$atts[ 'include_excerpt' ] = true;
		}

		// Should we include content?
		if ( $include_content ) {
			$atts[ 'include_content' ] = true;
		}

		// Should we include credits?
		if ( $include_credits ) {
			$atts[ 'include_credits' ] = true;
		}

		// Build a sanitized array of width and height values
		if ( $image_size ) {
			// make sure we have a comma separated list of integers.
			$atts[ 'image_size' ] = implode( ',', array_map( 'absint', explode( ',', $image_size ) ) );
		}

		// Sanitize order in which the posts are displayed, desc (default) or asc.
		if ( $order ) {
			if ( 'ASC' == $order ) {
				$args[ 'order' ] = 'ASC';
			}
		}

		// Sanitize Order by. Default is by date.
		if ( $order_by ) {
			if ( 'modified' == $order_by ) {
				$args[ 'order_by' ] = 'modified';
			} elseif ( 'title' == $order_by ) {
				$args[ 'order_by' ] = 'title';
			} elseif ( 'comment_count' == $order_by ) {
				$args[ 'order_by' ] = 'comment_count';
			} elseif ( 'ID' == $order_by ) {
				$args[ 'order_by' ] = 'ID';
			} else {
				$args[ 'order_by' ] = 'date';
			}
		}

		// The number of posts to return. Limit: 100. Default to 20.
		if ( $number ) {
			if ( $number < 1 || 100 < $number ) {
				$args[ 'number' ] = '20';
			} else {
				$args[ 'number' ] = $number;
			}
		} else {
			$args[ 'number' ] = get_option( 'posts_per_page' );
			if ( $args[ 'number' ] < 1 || 100 < $args[ 'number' ] ) {
				$args[ 'number' ] = '20';
			}
		}

		// Return posts dated before the specified datetime.
		if ( $before ) {
			$args[ 'before' ] = $before;
		}

		// Return posts dated after the specified datetime.
		if ( $after ) {
			$args[ 'after' ] = $after;
		}

		// Tag name or slug.
		if ( $tag ) {
			$args[ 'tag' ] = $tag;
		}

		// Category name or slug.
		if ( $category ) {
			$args[ 'category' ] = $category;
		}

		// Specify the post type. Defaults to 'post', use 'any' to query for both posts and pages.
		if ( $type ) {
			if ( 'any' == $type ) {
				$args[ 'type' ] = 'any';
			}
		}

		// Excludes the specified post ID(s) from the response.
		if ( $exclude ) {
			// make sure we have a comma separated list of integers.
			$args[ 'exclude' ] = implode( ',', array_map( 'absint', explode( ',', $exclude ) ) );
		}

		/**
		 * Sanitize and validate Post Status.
		 *
		 * Props @billerickson
		 * @see https://plugins.trac.wordpress.org/browser/display-posts-shortcode/tags/2.4/display-posts-shortcode.php#L165
		 */
		$status = explode( ', ', $status );
		$validated = array();
		$available = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash', 'any' );
		foreach ( $status as $unvalidated ) {
			if ( in_array( $unvalidated, $available ) ) {
				$validated[] = $unvalidated;
			}
			if ( ! empty( $validated ) ) {
				$args[ 'status' ] = $validated;
			}
		}
		// And the rebuild a string from our array of validated statuses.
		$args[ 'status' ] = implode( ',', $validated );

		// Author. Accepts IDs only.
		if ( $author ) {
			$args[ 'author' ] = $author;
		}

		// Wrapper class
		if ( $wrapper_class ) {
			$atts[ 'wrapper_class' ] = $wrapper_class;
		}

		// Clean up URL.
		if ( $url ) {
			$url = esc_url( $url );
			$url = str_replace( array( 'http://', 'https://' ), '', $url );
			$url = untrailingslashit( $url );

			// Normalize www.
			if ( 'www.' === substr( $url, 0, 4 ) ) {
				$url = substr( $url, 4 );
			}

			// And add the clean URL to the array of shortcode attributes.
			$atts[ 'url' ] = $url;
		}

		// Display a headline inside an h3.
		if ( $headline ) {
			$atts[ 'headline' ] = $headline;
		}

		// Finally, return the shortcode.
		return $this->get_posts( $atts, $args );
	}


	/**
	 * Convert string to boolean
	 * because (bool) "false" == true
	 *
	 * Props @billerickson
	 * @see https://plugins.trac.wordpress.org/browser/display-posts-shortcode/tags/2.4/display-posts-shortcode.php#L323
	 */
	public static function jeherve_post_embed_convert_string_bool( $value ) {
		return ! empty( $value ) && 'true' == $value ? true : false;
	}

	/**
	 * Convert a date into an iso 8601 datetime.
	 */
	public static function jeherve_post_embed_convert_date( $value ) {
		if ( ! empty( $value ) ) {
			return date( DATE_ISO8601, strtotime( $value, current_time( 'timestamp' ) ) );
		} else {
			return;
		}
	}

}

// And boom.
Jeherve_Post_Embeds::get_instance();
