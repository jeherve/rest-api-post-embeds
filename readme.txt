=== REST API Post Embeds ===
Contributors: jeherve
Tags: shortcode, embed, posts, jetpack, api, wp api, rest api
Stable tag: 1.5.0
Requires at least: 5.6
Tested up to: 6.8

Embed posts from your site or others' into your posts and pages.

== Description ==

This plugin allows you to use the `jeherve_post_embed` shortcode to embed posts from your site or others' anywhere on your site.

When creating the shortcode, you can use any of the 20 shortcode parameters to make sure the embed will include the posts you want to display, and will look the way you want it to look.

**Important:** You can only pull posts from 3 different types of sites:

* WordPress.com sites.
* Sites using the [Jetpack](http://jetpack.me) plugin, with the JSON API module.
* Sites using the [REST API](https://wordpress.org/plugins/rest-api/) plugin, by adding `wpapi="true"` to your shortcode parameters.

**Questions, problems?**

Take a look at the *Installation* and *FAQ* tabs here. If that doesn't help, [post in the support forums](http://wordpress.org/support/plugin/rest-api-post-embeds).

**Want to contribute with a patch?**

[Join me on GitHub!](https://github.com/jeherve/rest-api-post-embeds/)

== Installation ==

1. Install the plugin either via the WordPress.org plugin directory, or by uploading the files to your server.
2. Edit the post or page where you want to embed posts.
3. Add the `jeherve_post_embed` shortcode.
4. Boom! Done.

= Shortcode parameters =

The `jeherve_post_embed` shortcode includes different parameters, listed below:

* `url`:
	* URl of the site from which you want to retrieve posts.
	* Accepts URLs (no need to specify the scheme).
	* **Important**: If you use the default method to get posts, the site you'll pull posts from has to be hosted on WordPress.com, or use the [Jetpack](http://jetpack.me) plugin with the JSON API module. If you use the WP REST API, the site has to use the [WP REST API](https://wordpress.org/plugins/rest-api/) plugin, version 2 or above.
	* Defaults to your own site.
* `wpapi`:
	* Default to `false`.
	* Set to `true` to retrieve posts from a site using the WP REST API plugin.
* `ignore_sticky_posts`:
	* Default to `false`.
	* Use `true` or `false` to decide whether you want the embedded post list to include sticky posts.
* `include_images`:
	* Default to `true`.
	* When set to `true`, if the posts include a [Featured Image](https://codex.wordpress.org/Post_Thumbnails), it will be displayed above the post.
* `include_title`:
	* Default to `true`.
	* Includes the post title.
* `include_excerpt`:
	* Default to `true`.
	* Includes an excerpt if it exists.
* `include_content`:
	* Default to `false`.
	* Includes the whole post content (including images).
* `include_credits`:
	* Default to `true`.
	* Includes a link at the bottom of the posts list, linking to the source where the posts were found.
	* That credit link won't be displayed if you're embedding posts from your own site.
* `image_size`:
	* Allows you to control the size of the Featured Image displayed above the posts, if you've set `include_images` to true.
	* For that option to work, you'll need to use Jetpack on your site, as well as the [Photon module](http://jetpack.me/support/photon/).
	* Option should follow this format: `width,height`, `width` and `height` being the value in pixels.
	* By default, the images will be as wide as your theme's `$content_width` value ([reference](https://codex.wordpress.org/Content_Width)).
* `order`:
	* Order in which the posts are displayed, desc or asc.
	* Default to `DESC`.
* `order_by`:
	* What should the posts be ordered by? The accepted values are as follows: `modified` (date modified), `title`,`comment_count`, `ID`, `date`.
	* Default is `date`.
	* When using the WP REST API, only the following values are allowed: `date`, `relevance`, `id`, `include`, `title`, `slug`.
* `number`:
	* Number of posts to display.
	* Default to the number of posts you've set under Settings > Reading in your dashboard.
	* If that number is higher than 100, the default changes to `20`.
* `before`:
	* Only return posts dated before the specified date.
	* Default to none.
	* Use an ISO 8601 date format such as 2021-03-21.
* `after`:
	* Only return posts dated after the specified date.
	* Default to none.
	* Use an ISO 8601 date format such as 2021-03-21.
* `tag`:
	* Only return posts belonging to a specific tag name or tag slug.
	* Default to none.
* `category`:
	* Only return posts belonging to a specific category name or category slug.
	* Default to none.
* `type`:
	* Specify the post type.
	* Defaults to `posts`, use `any` to query for both posts and pages.
* `exclude`:
	* Excludes the specified post ID(s) from the response.
	* Accepts a comma-separated list of Post IDs.
	* Default to none.
	* Not available when using the WP REST API.
* `author`:
	* Specify posts from a given author ID.
	* Default to none.
* `wrapper_class`:
	* Add a class to the wrapper around the post list.
	* Default to none.
* `headline`:
	* Displays a headline inside an `h3`, before the post list.
	* Default to none.

== Frequently Asked Questions ==

= Can I insert the shortcode directly in my theme? =

Yes, you can use the `do_shortcode` function to do so. You can read more about it [here](https://developer.wordpress.org/reference/functions/do_shortcode/).

= I get the following error instead of my post list: `We cannot load blog data at this time`. =

See the instructions [here](https://wordpress.org/support/topic/how-to-fix-error-in-the-response-we-cannot-load-blog-data-at-this-time).

= Are there other ways for me to customize the post embed list? =

Yes! The plugin includes quite a few filters you can use to customize the post list. You can [browse the plugin's source code](https://github.com/jeherve/rest-api-post-embeds/blob/master/rest-api-post-embeds.php) to find out more.

* `jeherve_post_embed_blog_id` allows you to specify a custom blog ID or normalized Jetpack or WordPress.com site URL.
* `jeherve_post_embed_base_api_url` allows you to specify another REST API URL where you'll get your posts from. It defaults to the WordPress.com REST API.
* `jeherve_post_embed_query_url` allows you to change the final URL (including the options you've set in the shortcode parameters) used to query posts.
* `jeherve_post_embed_post_loop` allows you to build your own post loop from the data you get from the API.
* `jeherve_post_embed_image_params` allows you to specify custom Photon parameters applied to the Featured Images. It accepts an array of parameters. The accepted parameters are available [here](https://developer.wordpress.com/docs/photon/).
* `jeherve_post_embed_article_layout` allows you to filter the layout of a single article in the list.
* `jeherve_post_embed_featured_image` allows you to replace the Featured Image used for each post.
* `jeherve_post_embed_posts_cache` allows you to control how long the post list is cached.
* `jeherve_post_embed_featured_cache` allows you to control how long the featured images are cached.
* `jeherve_post_embed_term_cache` allows you to control how long the terms are cached.

== Changelog ==

= 1.5.0 =
Release Date: March 15, 2021

* Date Queries: fix date format when fetching posts from custom dates.
* Date Queries: allow WP REST API to make date queries as well.
* WP REST API: better fallback when the API does not accept one of the query parameters.

= 1.4.1 =
Release Date: October 20, 2017

* WP REST API: Make sure we always return data for Custom Post Type queries.

= 1.4.0 =
Release Date: November 17, 2016

* WP REST API: replace the filter param by top level query parameters. This makes category and tag filtering work again.
* Handle all Posts Types, including Pages, in the WP REST API.
* Coding Standards cleanup.
* Make sure the number of posts option is respected when using the WP REST API.

= 1.3.3 =
Release Date: April 26, 2016

* Make sure we don't display Featured Images when the attribute isn't set to true.

= 1.3.2 =
Release Date: March 14, 2016

* Allow the WordPress.com REST API to get Custom Post Types.

= 1.3.1 =
Release Date: February 13, 2016

* Ensure the plugin can be translated via WordPress.org.

= 1.3 =
Release Date: February 3, 2016

* Change the way Featured Images are called. [Featured Images are now named Featured Media in the WP REST API](https://github.com/WP-API/WP-API/pull/2044).
* Introduce 2 new filters to control how long transients are cached.

= 1.2.2 =
Release Date: September 21, 2015

* Add more checks to avoid errors when returned posts don't match query.

= 1.2.1 =
Release Date: August 26, 2015

* Add more error messages when an API query doesn't return expected results.
* Make sure custom WP REST API queries are correct (square brackets should not be stripped from query URL).

= 1.2 =
Release Date: August 26, 2015

* Use the `media` endpoint from the WP REST API to grab the featured image when available.
* Refactor API queries to avoid caching responses from the APIs when they included an error.
* Add a new filter, `jeherve_post_embed_featured_image`.
* Add an additional embed style, `embed-grid`.
* Include an uninstall.php to delete all transients created by the plugin when uninstalling it.

= 1.1 =
Releast Date: August 17, 2015

* Add support for the WP REST API, thanks to the new `wpapi` shortcode parameter.
* Refactor the plugin organization to make it easier to customize for third-party plugin developers.

= 1.0 =
Release Date: August 14, 2015

* Initial release
