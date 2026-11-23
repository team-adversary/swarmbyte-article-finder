=== SwarmByte Article Finder ===
Contributors: swarmbyte
Tags: search, articles, rest-api
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 2.1.1
Requires PHP: 8.0

Adds a public search interface and REST API for published SwarmByte articles.

== Description ==

SwarmByte Article Finder adds an accessible search form for the `article` post type. Visitors can search published articles by keyword and open an individual article by its slug. Legacy entries are loaded from the private article archive service.

Add the finder to a post, page, or template with:

`[swarmbyte_article_finder]`

== Installation ==

1. Upload the `swarmbyte-article-finder` directory to `/wp-content/plugins/`.
2. Activate **SwarmByte Article Finder** through the WordPress Plugins screen.
3. Ensure the site registers a public `article` post type.
4. Add the `[swarmbyte_article_finder]` shortcode where the search form should appear.

== REST API ==

= Search articles =

`GET /wp-json/swarmbyte/v1/articles?q=<term>`

Returns up to three published articles. Search terms must contain between 2 and 80 characters.

= Load an article =

`GET /wp-json/swarmbyte/v1/article?article=<slug>`

Returns a published WordPress article or an entry from the private legacy archive.

== Changelog ==

= 2.1.1 =

* Moved legacy article storage to the private archive service.
