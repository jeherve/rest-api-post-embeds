=== REST API Post Embeds ===
Contributors: jeherve
Tags: shortcode, embed, posts, jetpack, api
Stable tag: 1.0
Requires at least: 4.3
Tested up to: 4.3

Embed posts from your site or others' into your posts and pages.

== Description ==

This plugin allows you to use the `jeherve_post_embed` shortcode to embed posts from your site or others' anywhere on your site.

When creating the shortcode, you can use any of the 20 shortcode parameters to make sure the embed will include the posts you want to display, and will look the way you want it to look.

**Important:** Right now, you can only pull posts from sites using the [Jetpack](http://jetpack.me) plugin, with the JSON API module. In the future, I plan to add support for the [REST API](https://wordpress.org/plugins/rest-api/) plugin as well.

== Installation ==

1. Install the plugin either via the WordPress.org plugin directory, or by uploading the files to your server.
2. Edit the post or page where you want to embed posts.
3. Add the `jeherve_post_embed` shortcde.
4. Boom! Done.

= Shortcode parameters =

The `jeherve_post_embed` shortcode includes 20 different parameters, listed below:

* `url`:
	* URl of the site from which you want to retrieve posts.
	* **Important**: the site you'll pull posts from has to use the [Jetpack](http://jetpack.me) plugin, with the JSON API module.
	* Defaults to your own site.
* `ignore_sticky_posts`:
	* Default to `false`.
	* Use `true` or `false` to decide whether you want the embedded post list to include sticky posts.
* `include_images`:
	* Default to `true`.
	* When set to `true`, if the posts include a [Featured Image](https://codex.wordpress.org/Post_Thumbnails), it will be displayed above the post.
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
* `number`:
	* Number of posts to display.
	* Default to the number of posts you've set under Settings > Reading in your dashboard.
	* If that number is higher than 100, the default changes to `20`.
* `before`:
	* Only return posts dated before the specified date.
	* Default to none.
* `after`:
	* Only return posts dated after the specified date.
	* Default to none.
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
* `status`:
	* Default to `publish`.
	* Accepts the following values: `publish`, `pending`, `draft`, `auto-draft`, `future`, `private`, `inherit`, `trash`, `any`
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

That means that Jetpack can't return a list of posts from that site.
* It could be because Jetpack isn't installed or connected properly on that site.
* It could be because the site owner deactivated the JSON API module.

If the site is yours, and if you can't figure out what's wrong, you'll need to contact the Jetpack support team for help. You can do so [via email](http://jetpack.me/contact-support/) or via [the Jetpack support forums here on WordPress.org](http://wordpress.org/support/plugin/jetpack).

== Changelog ==

= 1.0 =
Release Date: August 13, 2015

* Initial release
