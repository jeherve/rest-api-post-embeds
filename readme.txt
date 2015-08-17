=== REST API Post Embeds ===
Contributors: jeherve
Tags: shortcode, embed, posts, jetpack, api, wp api, rest api
Stable tag: 1.1
Requires at least: 4.3
Tested up to: 4.3

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
* `ignore_sticky_posts`:
	* Default to `false`.
	* Use `true` or `false` to decide whether you want the embedded post list to include sticky posts.
* `include_images`:
	* Default to `true`.
	* When set to `true`, if the posts include a [Featured Image](https://codex.wordpress.org/Post_Thumbnails), it will be displayed above the post.
	* Not available when using the WP REST API.
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
	* Not available when using the WP REST API.
* `order`:
	* Order in which the posts are displayed, desc or asc.
	* Default to `DESC`.
* `order_by`:
	* What should the posts be ordered by? The accepted values are as follows: `modified` (date modified), `title`,`comment_count`, `ID`, `date`.
	* Default is `date`.
* `number`:
	* Number of posts to display.
	* Default to the number of posts you've set under Settings > Reading in your dashboard.
	* If that number is higher than 100, the default changes to `20`.
* `before`:
	* Only return posts dated before the specified date.
	* Default to none.
	* Not available when using the WP REST API.
* `after`:
	* Only return posts dated after the specified date.
	* Default to none.
	* Not available when using the WP REST API.
* `tag`:
	* Only return posts belonging to a specific tag name or slug.
	* Default to none.
* `category`:
	* Only return posts belonging to a specific category name or slug.
	* Default to none.
* `type`:
	* Specify the post type.
	* Defaults to `post`, use `any` to query for both posts and pages.
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

== Changelog ==

= 1.1 =
Releast Date: August 17, 2015

* Add support for the WP REST API, thanks to the new `wpapi` shortcode parameter.
* Refactor the plugin organization to make it easier to customize for third-party plugin developers.

= 1.0 =
Release Date: August 14, 2015

* Initial release
