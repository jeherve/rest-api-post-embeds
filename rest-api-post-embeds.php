<?php
/**
 * Plugin Name: REST API Post Embeds
 * Plugin URI: https://wordpress.org/plugins/rest-api-post-embeds
 * Description: Embed posts from your site or others' into your posts and pages.
 * Author: Jeremy Herve
 * Version: 1.4.0
 * Author URI: https://jeremy.hu
 * License: GPL2+
 * Text Domain: rest-api-post-embeds
 * Domain Path: /languages
 *
 * @package Jeherve_Post_Embeds
 */

/**
 * Create our main plugin class.
 */
class Jeherve_Post_Embeds {
	private static $instance;

	static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new Jeherve_Post_Embeds;
		}

		return self::$instance;
	}

	private function __construct() {
		// Prepare query.
		add_filter(    'jeherve_post_embed_blog_id',        array( $this, 'get_blog_details' ), 10, 1 );
		add_filter(    'jeherve_post_embed_query_url',      array( $this, 'build_query_URL' ), 10, 3 );

		// Create shortcode.
		add_shortcode( 'jeherve_post_embed',                array( $this, 'jeherve_post_embed_shortcode' ) );

		// Output embed.
		add_filter(    'jeherve_post_embed_post_loop',      array( $this, 'opening_div' ), 1, 4 );
		add_filter(    'jeherve_post_embed_post_loop',      array( $this, 'headline' ), 2, 4 );
		add_filter(    'jeherve_post_embed_post_loop',      array( $this, 'wpcom_post_loop' ), 10, 4 );
		add_filter(    'jeherve_post_embed_post_loop',      array( $this, 'wpapi_post_loop' ), 11, 4 );
		add_filter(    'jeherve_post_embed_featured_image', array( $this, 'photonized_resized_featured_image_url' ), 10, 2 );
		add_filter(    'jeherve_post_embed_article_layout', array( $this, 'article_wrap' ), 10, 2 );
		add_filter(    'jeherve_post_embed_post_loop',      array( $this, 'credits' ), 98, 4 );
		add_filter(    'jeherve_post_embed_post_loop',      array( $this, 'closing_div' ), 99, 4 );

		add_action(    'wp_enqueue_scripts',                array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		wp_register_style( 'jeherve_post_embed', plugins_url( 'style.css', __FILE__ ) );
		wp_enqueue_style( 'jeherve_post_embed' );
	}


	/**
	 * Get the WordPress.com blog ID of your own site.
	 *
	 * @since 1.0.0
	 *
	 * @param  absint $blog_id WordPress.com blog ID.
	 * @return absint $blog_id WordPress.com blog ID.
	 */
	public function get_blog_details( $blog_id ) {

		// Get the blog's ID.
		if ( class_exists( 'Jetpack_Options' ) ) {
			$blog_id = Jetpack_Options::get_option( 'id' );
		}

		if ( $blog_id ) {
			return absint( $blog_id );
		} else {
			return home_url();
		}

	}


	/**
	 * Build the API query URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string $string Query URL.
	 */
	public function build_query_URL( $url, $atts, $args ) {

		/**
		 * Filter the blog ID used to query posts.
		 * By default it's your own site's blog ID.
		 *
		 * @since 1.0.0
		 *
		 * @param string $blog_id Blog identifier. Can be a WordPress.com blog ID, or a normalized Jetpack / WordPress.com site URL (without protocol).
		 */
		$blog_id = apply_filters( 'jeherve_post_embed_blog_id', '' );

		// Overwrite the blog ID if it was defined in the shortcode.
		if ( $atts['url'] ) {
			$blog_id = urlencode( $atts['url'] );
		}

		// If no post ID, let's stop right there.
		if ( ! $blog_id ) {
			return;
		}

		// Are we using the WP REST API?
		if ( true === $atts['wpapi'] && ! absint( $blog_id ) ) {
			// Get the post type we want to query.
			if ( isset( $atts['post_type'] ) && 'any' != $atts['post_type'] ) {
				$post_type = $atts['post_type'];
			} else {
				$post_type = 'posts';
			}

			// Query the WP REST API (V2).
			$url = sprintf(
				esc_url( '%1$s/wp-json/wp/v2/%2$s/' ),
				$blog_id,
				$post_type
			);
		} else {
			// Query the WordPress.com REST API URL.
			$url = sprintf( esc_url( 'https://public-api.wordpress.com/rest/v1.1/sites/%s/posts/' ), $blog_id );
		}

		// Look for tag ID if we are using the WP REST API and the shortcode includes info about a tag.
		if ( true === $atts['wpapi'] && isset( $args['tag'] ) ) {
			$args['tags'] = $this->get_cat_tag_id( $args['tag'], 'tags', $atts );
			unset( $args['tag'] );
		}

		// Look for category ID if we are using the WP REST API and the shortcode includes info about a category.
		if ( true === $atts['wpapi'] && isset( $args['category'] ) ) {
			$args['categories'] = $this->get_cat_tag_id( $args['category'], 'categories', $atts );
			unset( $args['category'] );
		}

		// Add query arguments.
		$base_url = add_query_arg( $args, $url );

		/**
		 * Filter the base query URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $base_url Base API query URL.
		 * @param array  $args     Array of query arguments.
		 */
		$base_url = apply_filters( 'jeherve_post_embed_base_api_url', $base_url, $args );

		return $base_url;

	}


	/**
	 * Opening div. Added very early.
	 *
	 * @since 1.1.0
	 *
	 * @param string  $loop            Post loop.
	 * @param array   $posts_info      Array of data about our posts.
	 * @param int     $number_of_posts Number of posts we want to display.
	 * @param array   $atts            Array of shortcode attributes.
	 *
	 * @return string $var             HTML code to prepend to the post embed list.
	 */
	public function opening_div( $loop, $posts_info, $number_of_posts, $atts ) {
		$class = ( $atts['wrapper_class'] ) ? $atts['wrapper_class'] : '';

		$opening = '<div class="jeherve-post-embeds ' . $class . '">' . "\n";

		return $opening . $loop;
	}


	/**
	 * Headline.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $loop            Post loop.
	 * @param array   $posts_info      Array of data about our posts.
	 * @param int     $number_of_posts Number of posts we want to display.
	 * @param array   $atts            Array of shortcode attributes.
	 *
	 * @return string $var             HTML code to prepend to the post embed list.
	 */
	public function headline( $loop, $posts_info, $number_of_posts, $atts ) {
		if ( isset( $atts['headline'] ) && ! empty( $atts['headline'] ) ) {
			$headline = sprintf(
				'<h3 class="jeherve-post-embeds-headline">%1$s</h3>',
				esc_html( $atts['headline'] )
			);
			return $headline . $loop;
		}
		return $loop;
	}


	/**
	 * WordPress.com REST API post loop.
	 *
	 * @since 1.1.0
	 *
	 * @param string  $loop            Post loop.
	 * @param array   $posts_info      Array of data about our posts.
	 * @param int     $number_of_posts Number of posts we want to display.
	 * @param array   $atts            Array of shortcode attributes.
	 *
	 * @return string $loop            Post loop.
	 */
	public function wpcom_post_loop( $loop, $posts_info, $number_of_posts, $atts ) {

		// Bail if the shortcode uses the WP REST API.
		if ( true === $atts['wpapi'] ) {
			return $loop;
		}

		if ( $posts_info->found < $number_of_posts ) {
			$number_of_posts = $posts_info->found;
		}

		for ( $i = 0; $i < $number_of_posts; $i++ ) {
			$single_post = $posts_info->posts[ $i ];

			$article = '';

			// Title.
			$post_title = ( $single_post->title ) ? $single_post->title : __( ' ( No Title ) ', 'rest-api-post-embeds' );
			if ( true === $atts['include_title'] ) {
				$article .= sprintf(
					'<h4 class="post-embed-post-title"><a href="%1$s">%2$s</a></h4>',
					esc_url( $single_post->URL ),
					esc_html( $post_title )
				);
			}

			// Featured Image.
			if (
				( isset( $atts['include_images'] ) && true === $atts['include_images'] )
				&& ( isset( $single_post->featured_image ) && ! empty( $single_post->featured_image ) )
			) {

				$article .= sprintf(
					'<div class="post-embed-post-thumbnail"><a title="%1$s" href="%2$s"><img src="%3$s" alt="%1$s"/></a></div>',
					esc_attr( $post_title ),
					esc_url( $single_post->URL ),
					/**
					 * Modify our Featured Image URL.
					 * Uses Jetpack's Photon module to resize the image.
					 *
					 * @since 1.2.0
					 *
					 * @param string $featured_image_url Featured Image URL.
					 * @param array  $atts               Shortcode attributes.
					 */
					apply_filters( 'jeherve_post_embed_featured_image', esc_url( $single_post->featured_image ), $atts )
				);

			}

			// Excerpt.
			if (
				true === $atts['include_excerpt'] &&
				( isset( $single_post->excerpt ) && ! empty( $single_post->excerpt ) )
			) {
				$article .= sprintf(
					'<div class="post-embed-post-excerpt">%s</div>',
					$single_post->excerpt
				);
			}

			// Full post content.
			if (
				true === $atts['include_content'] &&
				( isset( $single_post->content ) && ! empty( $single_post->content ) )
			) {
				$article .= sprintf(
					'<div class="post-embed-post-content">%s</div>',
					$single_post->content
				);
			}

			/**
			 * Filters the layout of a single article in the list.
			 *
			 * @since 1.0.0
			 *
			 * @param string $article     Article layout.
			 * @param array  $single_post Array of information about the post.
			 */
			$article = apply_filters( 'jeherve_post_embed_article_layout', $article, $single_post );

			$loop .= $article;

		} // End loop

		return $loop;
	}


	/**
	 * WP REST API post loop.
	 *
	 * @since 1.1.0
	 *
	 * @param string  $loop            Post loop.
	 * @param array   $posts_info      Array of data about our posts.
	 * @param int     $number_of_posts Number of posts we want to display.
	 * @param array   $atts            Array of shortcode attributes.
	 *
	 * @return string $str             Post loop.
	 */
	public function wpapi_post_loop( $loop, $posts_info, $number_of_posts, $atts ) {

		// Bail if the shortcode doesn't use the WP REST API.
		if ( 'true' != $atts['wpapi'] ) {
			return $loop;
		}

		foreach ( array_slice( $posts_info, 0, $number_of_posts ) as $post ) {
			$article = '';

			// Title.
			$post_title = ( $post->title->rendered ) ? $post->title->rendered : __( ' ( No Title ) ', 'rest-api-post-embeds' );
			if ( true === $atts['include_title'] ) {
				$article .= sprintf(
					'<h4 class="post-embed-post-title"><a href="%1$s">%2$s</a></h4>',
					esc_url( $post->link ),
					esc_html( $post_title )
				);
			}

			// Featured Image.
			if (
				( isset( $atts['include_images'] ) && true === $atts['include_images'] )
				&& ( isset( $post->featured_media ) && ! empty( $post->featured_media ) )
			) {
				// Get the Featured Image URL from the Featured Image ID.
				$featured_image_url = $this->get_wpapi_featured_image( $post->featured_media, $atts );

				if ( empty( $featured_image_url ) ) {
					continue;
				}

				$article .= sprintf(
					'<div class="post-embed-post-thumbnail"><a title="%1$s" href="%2$s"><img src="%3$s" alt="%1$s"/></a></div>',
					esc_attr( $post_title ),
					esc_url( $post->link ),
					/** This filter is documented in rest-api-post-embeds.php */
					apply_filters( 'jeherve_post_embed_featured_image', esc_url( $featured_image_url ), $atts )
				);
			}

			// Excerpt.
			if (
				true === $atts['include_excerpt'] &&
				( isset( $post->excerpt->rendered ) && ! empty( $post->excerpt->rendered ) )
			) {
				$article .= sprintf(
					'<div class="post-embed-post-excerpt">%s</div>',
					$post->excerpt->rendered
				);
			}

			// Full post content.
			if (
				true === $atts['include_content'] &&
				( isset( $post->content->rendered ) && ! empty( $post->content->rendered ) )
			) {
				$article .= sprintf(
					'<div class="post-embed-post-content">%s</div>',
					$post->content->rendered
				);
			}

			/** This filter is documented above. */
			$article = apply_filters( 'jeherve_post_embed_article_layout', $article, $post );

			$loop .= $article;
		}

		return $loop;
	}


	/**
	 * Wrap the article in a div.
	 *
	 * @since 1.1.0
	 *
	 * @param string  $article     Article layout.
	 * @param array   $single_post Array of information about the post.
	 *
	 * @return string $article     Article layout.
	 */
	public function article_wrap( $article, $single_post ) {
		$article = sprintf(
			'<div class="post-embed-article">%s</div>',
			$article
		);
		return $article;
	}


	/**
	 * Credits HTML output.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $loop            Post loop.
	 * @param array   $posts_info      Array of data about our posts.
	 * @param int     $number_of_posts Number of posts we want to display.
	 * @param array   $atts            Array of shortcode attributes.
	 *
	 * @return string $str             Credits HTML output.
	 */
	public function credits( $loop, $posts_info, $number_of_posts, $atts ) {
		if ( true === $atts['include_credits'] && isset( $atts['url'] ) ) {
			$credits = '<div class="jeherve-post-embeds-credits">';
			$credits .= sprintf(
				_x( 'Source: <a class="jeherve-post-embeds-credit-link" href="http://%1$s">%1$s</a>', 'Site URL', 'rest-api-post-embeds' ),
				$atts['url']
			);
			$credits .= '</div>';
			return $loop . $credits;
		}
		return $loop;
	}


	/**
	 * Closing div. added at the very end of the post list.
	 *
	 * @since 1.1.0
	 *
	 * @param string  $loop            Post loop.
	 * @param array   $posts_info      Array of data about our posts.
	 * @param int     $number_of_posts Number of posts we want to display.
	 * @param array   $atts            Array of shortcode attributes.
	 *
	 * @return string $str             Closing div HTML output.
	 */
	public function closing_div( $loop, $posts_info, $number_of_posts, $atts ) {
		$closing = "\n" . '</div>' . "\n";

		return $loop . $closing;
	}


	/**
	 * Get posts.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $atts        Array of shortcode attributes.
	 * @param array   $args        Array of query arguments.
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
		 * @param string $url  URL querying an API to get a list of posts in a JSON format.
		 * @param array  $atts Array of shortcode attributes.
		 * @param array  $args Array of query arguments.
		 */
		$query_url = apply_filters( 'jeherve_post_embed_query_url', '', $atts, $args );

		// Build a hash of the query URL. We'll use it later when building the transient.
		if ( $query_url ) {
			$query_hash = substr( md5( $query_url ), 0, 21 );
		} else {
			return;
		}

		// Look for data in our transient. If nothing, let's get a list of posts to display.
		$data_from_cache = get_transient( 'jeherve_post_embed_' . $query_hash );
		if ( false === $data_from_cache ) {
			$response = wp_remote_get( esc_url_raw( $query_url ) );

			if ( is_wp_error( $response ) || empty( $response ) ) {
				return '<p>' . __( 'Error in the response. We cannot load blog data at this time.', 'rest-api-post-embeds' ) . '</p>';
			}

			$posts_data = wp_remote_retrieve_body( $response );

			if ( is_wp_error( $posts_data ) || empty( $posts_data ) ) {
				return '<p>' . __( 'Error in the data received from the site. We cannot load blog data at this time.', 'rest-api-post-embeds' ) . '</p>';
			}

			$posts_info = json_decode( $posts_data );

			// If we get an error in that response, let's give up now.
			if ( isset( $posts_info->error ) && 'jetpack_error' == $posts_info->error ) {
				return '<p>' . __( 'Error in the posts being returned. We cannot load blog data at this time.', 'rest-api-post-embeds' ) . '</p>';
			} elseif ( empty( $posts_info ) ) {
				return '<p>' . __( 'This query did not return any results. Are you sure the site uses the WP REST API?', 'rest-api-post-embeds' ) . '</p>';
			} elseif ( isset( $posts_info->found ) && '0' == $posts_info->found ) {
				return '<p>' . __( 'No posts found for that query. Try different parameters.', 'rest-api-post-embeds' ) . '</p>';
			} else {
				/**
				 * Filter the amount of time each post list is cached.
				 *
				 * @since 1.3.0
				 *
				 * @param string $post_list_caching Amount of time each post list is cached. Default to 10 minutes.
				 */
				$post_list_caching = apply_filters( 'jeherve_post_embed_posts_cache', 10 * MINUTE_IN_SECONDS );

				set_transient( 'jeherve_post_embed_' . $query_hash, $posts_info, $post_list_caching );
			}

		} else {
			$posts_info = $data_from_cache;
		}

		/**
		 * How many posts should we display?
		 * Check if we got that data from the shortcode.
		 */
		if ( isset( $args['number'] ) ) {
			$number_of_posts = $args['number'];
		} elseif ( isset( $args['per_page'] ) ) {
			$number_of_posts = $args['per_page'];
		} else {
			return;
		}

		// Build our list.
		return $this->post_list( $posts_info, $number_of_posts, $atts );

	}


	/**
	 * Get Featured Image from the WP REST API.
	 *
	 * @since 1.2.0
	 *
	 * @param int     $featured_id  Featured Image ID.
	 * @param array   $atts         Shortcode attributes.
	 *
	 * @return string $featured_url Featured Image URL.
	 */
	public function get_wpapi_featured_image( $featured_id, $atts ) {
		if ( ! $atts || ! $featured_id ) {
			return;
		}

		/** This filter is documented in rest-api-post-embeds.php */
		$blog_id = apply_filters( 'jeherve_post_embed_blog_id', '' );

		// Overwrite the blog ID if it was defined in the shortcode.
		if ( $atts['url'] ) {
			$blog_id = urlencode( $atts['url'] );
		}

		// If no post ID, let's stop right there.
		if ( ! $blog_id ) {
			return;
		}

		$featured_query_url = sprintf(
			esc_url( '%1$s/wp-json/wp/v2/media/%2$s/' ),
			$blog_id,
			$featured_id
		);

		// Build a hash of the query URL. We'll use it later when building the transient.
		if ( $featured_query_url ) {
			$featured_query_hash = substr( md5( $featured_query_url ), 0, 21 );
		} else {
			return;
		}

		// Look for data in our transient. If nothing, let's get a list of posts to display.
		$cached_featured = get_transient( 'jeherve_post_embed_featured_' . $featured_id . '_' . $featured_query_hash );
		if ( false === $cached_featured ) {
			$featured_response = wp_remote_retrieve_body(
				wp_remote_get( esc_url_raw( $featured_query_url ) )
			);

			/**
			 * Filter the amount of time each Featured Image is cached.
			 *
			 * @since 1.3.0
			 *
			 * @param string $featured_img_caching Amount of time each Featured Image is cached. Default is 10 hours.
			 */
			$featured_img_caching = apply_filters( 'jeherve_post_embed_featured_cache', 10 * HOUR_IN_SECONDS );

			set_transient( 'jeherve_post_embed_' . $featured_id . '_' . $featured_query_hash, $featured_response, $featured_img_caching );
		} else {
			$featured_response = $cached_featured;
		}

		if ( ! is_wp_error( $featured_response )  ) {
			$featured_info = json_decode( $featured_response, true );
			$featured_url = $featured_info['guid']['rendered'];
		} else {
			return;
		}

		return $featured_url;
	}


	/**
	 * Modify our Featured Image URL.
	 * The image goes through Photon if Jetpack is active, and is resized if parameters are available.
	 *
	 * @since 1.2.0
	 *
	 * @param string  $featured_image_url Featured Image URL.
	 * @param array   $atts               Shortcode attributes.
	 *
	 * @return string $featured_image_url Modified Featured Image URL.
	 */
	public function photonized_resized_featured_image_url( $featured_image_url, $atts ) {

		$image_params = array();

		// Let's get the theme's content_width and use that as max image width unless specified otherwise via the filter.
		global $content_width;
		if ( isset( $content_width ) ) {
			$image_params = array( 'w' => $content_width );
		}

		// If we have image size data from the shortcode, let's use it.
		if ( isset( $atts['image_size'] ) ) {
			$image_params = array( 'resize' => $atts['image_size'] );
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

		/** This filter is documented in Jetpack */
		return apply_filters( 'jetpack_photon_url', $featured_image_url, $image_params );
	}

	/**
	 * Build the post list
	 *
	 * @since 1.0.0
	 *
	 * @param array   $posts_info      Array of data about our posts.
	 * @param int     $number_of_posts Number of posts we want to display.
	 * @param array   $atts            Array of shortcode attributes.
	 *
	 * @return string $list_layout     HTML output of our posts.
	 */
	public function post_list( $posts_info, $number_of_posts, $atts ) {
		/**
		 * Filter the post loop.
		 *
		 * @since 1.1.0
		 *
		 * @param string $loop            Post loop.
		 * @param array  $posts_info      Array of data about our posts.
		 * @param int    $number_of_posts Number of posts we want to display.
		 * @param array  $atts            Array of shortcode attributes.
		 */
		return apply_filters( 'jeherve_post_embed_post_loop', '', $posts_info, $number_of_posts, $atts );
	}

	/**
	 * Create a shortcode so people can output the list anywhere they want.
	 *
	 * @param array $atts Array of shortcode attributes.
	 */
	public function jeherve_post_embed_shortcode( $atts ) {

		// Default Shortcode attributes.
		$atts = shortcode_atts( array(
			'ignore_sticky_posts' => false,
			'include_images'      => true,
			'include_title'       => true,
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
			'type'                => '',
			'exclude'             => '',
			'author'              => '',
			'wrapper_class'       => '',
			'url'                 => '',
			'headline'            => '',
			'wpapi'               => false,
		), $atts, 'jeherve_post_embed' );

		// Sanitize every attribute.
		$ignore_sticky_posts = $this->jeherve_post_embed_convert_string_bool( $atts['ignore_sticky_posts'] );
		$include_images      = $this->jeherve_post_embed_convert_string_bool( $atts['include_images'] );
		$include_title       = $this->jeherve_post_embed_convert_string_bool( $atts['include_title'] );
		$include_excerpt     = $this->jeherve_post_embed_convert_string_bool( $atts['include_excerpt'] );
		$include_content     = $this->jeherve_post_embed_convert_string_bool( $atts['include_content'] );
		$include_credits     = $this->jeherve_post_embed_convert_string_bool( $atts['include_credits'] );
		$image_size          = $atts['image_size']; // Validated below.
		$order               = sanitize_key( $atts['order'] );
		$order_by            = sanitize_key( $atts['order_by'] );
		$number              = intval( $atts['number'] );
		$before              = $this->jeherve_post_embed_convert_date( $atts['before'] );
		$after               = $this->jeherve_post_embed_convert_date( $atts['after'] );
		$tag                 = sanitize_text_field( $atts['tag'] );
		$category            = sanitize_text_field( $atts['category'] );
		$type                = sanitize_text_field( $atts['type'] );
		$exclude             = $atts['exclude']; // Validated below.
		$author              = intval( $atts['author'] );
		$wrapper_class       = sanitize_html_class( $atts['wrapper_class'] );
		$url                 = $this->jeherve_post_embed_clean_url( $atts['url'] );
		$headline            = sanitize_text_field( $atts['headline'] );
		$wpapi               = $this->jeherve_post_embed_convert_string_bool( $atts['wpapi'] );

		// Should we use the WP REST API instead of the WordPress.com REST API?
		if ( $wpapi ) {
			$atts['wpapi'] = true;
		}

		// Sticky Posts.
		if ( $ignore_sticky_posts ) {
			$args['sticky'] = '1';
			// The WP REST API uses the "ignore_sticky_posts" parameter instead.
			if ( true === $atts['wpapi'] ) {
				$args['ignore_sticky_posts'] = $args['sticky'];
				unset( $args['sticky'] );
			}
		}

		// Should we include images?
		if ( $include_images ) {
			$atts['include_images'] = true;
		}

		// Should we include titles?
		if ( $include_title ) {
			$atts['include_title'] = true;
		}

		// Should we include excerpts?
		if ( $include_excerpt ) {
			$atts['include_excerpt'] = true;
		}

		// Should we include content?
		if ( $include_content ) {
			$atts['include_content'] = true;
		}

		// Should we include credits?
		if ( $include_credits ) {
			$atts['include_credits'] = true;
		}

		// Build a sanitized array of width and height values.
		if ( $image_size ) {
			// make sure we have a comma separated list of integers.
			$atts['image_size'] = implode( ',', array_map( 'absint', explode( ',', $image_size ) ) );
		}

		// Sanitize order in which the posts are displayed, desc (default) or asc.
		if ( $order ) {
			if ( 'ASC' == $order ) {
				$args['order'] = 'ASC';
			}
		}

		/**
		 * Sanitize Order by. Default is by date.
		 * I only list a few of the parameters available, will add more if it's requested.
		 * Until then, one can use the `jeherve_post_embed_base_api_url` filter to specify custom parameters.
		 *
		 * @see https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters
		 */
		if ( $order_by ) {
			if ( 'modified' == $order_by ) {
				$args['order_by'] = 'modified';
			} elseif ( 'title' == $order_by ) {
				$args['order_by'] = 'title';
			} elseif ( 'comment_count' == $order_by ) {
				$args['order_by'] = 'comment_count';
			} elseif ( 'ID' == $order_by ) {
				$args['order_by'] = 'ID';
			} elseif (
				// The WP REST API includes a rand parameter as well.
				'rand' == $order_by && true === $atts['wpapi'] ) {
				$args['order_by'] = 'rand';
			} else {
				$args['order_by'] = 'date';
			}

			// The WP REST API uses the "orderby" parameter instead of "order_by".
			if ( true === $atts['wpapi'] ) {
				$args['orderby'] = $args['order_by'];
				unset( $args['order_by'] );
			}
		}

		// The number of posts to return. Limit: 100. Default to 20.
		if ( $number ) {
			if ( $number < 1 || 100 < $number ) {
				$args['number'] = '20';
			} else {
				$args['number'] = $number;
			}
		} else {
			$args['number'] = get_option( 'posts_per_page' );
			if ( $args['number'] < 1 || 100 < $args['number'] ) {
				$args['number'] = '20';
			}
		}
		// The WP REST API uses the "per_page" parameter instead.
		if ( true === $atts['wpapi'] ) {
			$args['per_page'] = $args['number'];
			unset( $args['number'] );
		}

		/**
		 * Date Queries.
		 *
		 * These are simple with the WordPress.com REST API, but are not yet available with the WP REST API.
		 *
		 * @see https://github.com/WP-API/WP-API/issues/389
		 */
		// Return posts dated before the specified datetime.
		if ( $before ) {
			$args['before'] = $before;
			if ( true === $atts['wpapi'] ) {
				unset( $args['before'] );
			}
		}

		// Return posts dated after the specified datetime.
		if ( $after ) {
			$args['after'] = $after;
			if ( true === $atts['wpapi'] ) {
				unset( $args['after'] );
			}
		}

		/**
		 * Tag.
		 * The WordPress.com REST API accepts a name or a slug.
		 * The REST API only accepts IDs. We'll transform that later before to run the query.
		 */
		if ( $tag ) {
			$args['tag'] = $tag;
		}

		/**
		 * Category.
		 * The WordPress.com REST API accepts a name or a slug.
		 * The REST API only accepts IDs. We'll transform that later before to run the query.
		 */
		if ( $category ) {
			$args['category'] = $category;
		}

		/**
		 * Specify the post type.
		 *
		 * For WordPress.com things are extermely simple. Defaults to 'post', use 'any' to query for both posts and pages.
		 * For the WP REST API, we have more options.
		 *
		 * @see https://codex.wordpress.org/Class_Reference/WP_Query#Type_Parameters
		 * The WP REST API doesn't seem to handle the 'page' option in post types, though.
		 * There is a different endpoint for each post type.
		 * We'll change the query URL accordingly.
		 */
		if ( $type ) {
			if ( 'any' == $type ) {
				$args['type'] = 'any';
			} else {
				$args['type'] = $type;
			}

			// Now let's handle WP REST API.
			if ( true === $atts['wpapi'] ) {
				$args['post_type'] = $args['type'];
				unset( $args['type'] );
			}
		}

		// Excludes the specified post ID(s) from the response.
		if ( $exclude ) {
			// make sure we have a comma separated list of integers.
			$args['exclude'] = implode( ',', array_map( 'absint', explode( ',', $exclude ) ) );
			/** `post__not_in` is only available in authenticated requests as it's marked as private in WordPress.
			 * We consequently can't use it here.
			 *
			 * @see https://github.com/WP-API/WP-API/issues/1357
			 */
			if ( true === $atts['wpapi'] ) {
				// $args['post__not_in'] = explode( ',', $args['exclude'] );
				unset( $args['exclude'] );
			}
		}

		// Author. Accepts IDs only.
		if ( $author ) {
			$args['author'] = $author;
		}

		// Wrapper class.
		if ( $wrapper_class ) {
			$atts['wrapper_class'] = $wrapper_class;
		}

		// Clean up URL.
		if ( $url ) {
			$atts['url'] = $url;
		}

		// Display a headline inside an h3.
		if ( $headline ) {
			$atts['headline'] = $headline;
		}

		// Finally, return the shortcode.
		return $this->get_posts( $atts, $args );
	}

	/**
	 * Get a category or a tag ID from the WP REST API.
	 *
	 * @since 1.4.0
	 *
	 * @param string $term_name Name of the Category or Tag.
	 * @param string $tax_name  'categories' or 'tags'.
	 * @param array  $atts      Shortcode attributes.
	 *
	 * @return string $term_id Category or Tag ID.
	 */
	public function get_cat_tag_id( $term_name, $tax_name, $atts ) {
		if ( ! $atts || ! $term_name || ! $tax_name ) {
			return;
		}

		/** This filter is documented in rest-api-post-embeds.php */
		$blog_id = apply_filters( 'jeherve_post_embed_blog_id', '' );

		// Overwrite the blog ID if it was defined in the shortcode.
		if ( $atts['url'] ) {
			$blog_id = urlencode( $atts['url'] );
		}

		// If no post ID, let's stop right there.
		if ( ! $blog_id ) {
			return;
		}

		$tax_query_url = sprintf(
			esc_url( '%1$s/wp-json/wp/v2/%2$s?slug=%3$s' ),
			$blog_id,
			$tax_name,
			$term_name
		);

		// Build a hash of the query URL. We'll use it later when building the transient.
		if ( $tax_query_url ) {
			$tax_query_hash = substr( md5( $tax_query_url ), 0, 21 );
		} else {
			return;
		}

		// Look for data in our transient.
		$cached_tax = get_transient( 'jeherve_post_embed_term_' . $term_name . '_' . $tax_query_hash );
		if ( false === $cached_tax ) {
			$term_response = wp_remote_retrieve_body(
				wp_remote_get( esc_url_raw( $tax_query_url ) )
			);

			/**
			 * Filter the amount of time each Term info is cached.
			 *
			 * @since 1.4.0
			 *
			 * @param string $term_caching Amount of time each term is cached. Default is a day.
			 */
			$term_caching = apply_filters( 'jeherve_post_embed_term_cache', 1 * DAY_IN_SECONDS );

			set_transient( 'jeherve_post_embed_term_' . $term_name . '_' . $tax_query_hash, $term_response, $term_caching );
		} else {
			$term_response = $cached_tax;
		}

		if ( ! is_wp_error( $term_response )  ) {
			$term_info = json_decode( $term_response, true );
			$term_id = $term_info[0]['id'];
		} else {
			return;
		}

		return $term_id;
	}

	/**
	 * Convert string to boolean
	 * because (bool) "false" == true
	 *
	 * Props @billerickson
	 *
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

	/**
	 * Clean up URL
	 *
	 * @since 1.1.0
	 *
	 * @param string $url Site URL.
	 *
	 * @return string $url Clean URL. No scheme, no www.
	 */
	public static function jeherve_post_embed_clean_url( $url ) {
		$url = esc_url( $url );
		$url = str_replace( array( 'http://', 'https://' ), '', $url );
		$url = untrailingslashit( $url );

		return $url;
	}

}
// And boom.
Jeherve_Post_Embeds::get_instance();
