<?php
/**
 * Plugin Name: SwarmByte Article Finder
 * Description: Public article search for anonymous SwarmByte visitors.
 * Version: 2.1.1
 */

defined('ABSPATH') || exit;

/** Registers the public REST routes used by the article finder. */
add_action('rest_api_init', function (): void {
    register_rest_route('swarmbyte/v1', '/articles', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'swarmbyte_find_articles',
        'permission_callback' => '__return_true',
        'args' => [
            'q' => [
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => static fn($value): bool => mb_strlen(trim((string) $value)) >= 2 && mb_strlen(trim((string) $value)) <= 80,
            ],
        ],
    ]);

    register_rest_route('swarmbyte/v1', '/article', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'swarmbyte_load_article',
        'permission_callback' => '__return_true',
        'args' => [
            'article' => [
                'required' => true,
                'validate_callback' => 'swarmbyte_validate_article_name',
                'sanitize_callback' => 'swarmbyte_normalize_article_name',
            ],
        ],
    ]);
});

/** Checks whether an article name is a supported relative archive name. */
function swarmbyte_validate_article_name($value): bool {
    return is_string($value)
        && $value !== ''
        && mb_strlen($value) <= 120
        && !str_starts_with($value, '/')
        && !str_contains($value, '../');
}

/** Normalizes article names from older versions of the archive. */
function swarmbyte_normalize_article_name($value): string {
    $normalized = Normalizer::normalize(sanitize_text_field((string) $value), Normalizer::FORM_KC);

    return $normalized === false ? '' : $normalized;
}

/** Searches the published article catalog for public visitors. */
function swarmbyte_find_articles(WP_REST_Request $request): WP_REST_Response {
    $articles = get_posts([
        'post_type' => 'article',
        'post_status' => 'publish',
        'numberposts' => 3,
        's' => $request->get_param('q'),
    ]);

    return rest_ensure_response(array_map(static fn(WP_Post $article): array => [
        'title' => $article->post_title,
        'excerpt' => $article->post_excerpt,
        'url' => get_permalink($article),
    ], $articles));
}

/** Loads a published article or a legacy entry from the private archive client. */
function swarmbyte_load_article(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $name = $request->get_param('article');
    $article = get_page_by_path($name, OBJECT, 'article');
    if ($article instanceof WP_Post && $article->post_status === 'publish') {
        return rest_ensure_response([
            'title' => $article->post_title,
            'content' => apply_filters('the_content', $article->post_content),
            'url' => get_permalink($article),
        ]);
    }

    $url = add_query_arg('path', 'articles/'.$name, 'http://blog_article_sandbox:8080/article');
    $response = wp_remote_get($url, [
        'timeout' => 3,
        'redirection' => 0,
        'limit_response_size' => 1024 * 1024,
    ]);
    if (is_wp_error($response)) {
        return new WP_Error('swarmbyte_archive_unavailable', 'Article archive unavailable.', ['status' => 503]);
    }
    if (wp_remote_retrieve_response_code($response) !== 200) {
        return new WP_Error('swarmbyte_article_not_found', 'Article not found.', ['status' => 404]);
    }

    return rest_ensure_response(['content' => wp_remote_retrieve_body($response)]);
}

/** Renders the public article finder shortcode. */
add_shortcode('swarmbyte_article_finder', function (): string {
    wp_enqueue_style('swarmbyte-article-finder', plugin_dir_url(__FILE__).'public.css', [], '2.1.1');
    wp_enqueue_script('swarmbyte-article-finder', plugin_dir_url(__FILE__).'public.js', [], '2.1.1', true);

    return '<section class="panel swarmbyte-article-finder" aria-labelledby="article-finder-title">'
        .'<div><h2 id="article-finder-title">Find a briefing</h2><p>Search the SwarmByte library by technology, company, or security event.</p></div>'
        .'<form class="swarmbyte-finder-form" data-endpoint="'.esc_url(rest_url('swarmbyte/v1/articles')).'">'
        .'<label for="swarmbyte-article-query">Search articles</label><div class="swarmbyte-finder-controls">'
        .'<input id="swarmbyte-article-query" name="q" type="search" minlength="2" maxlength="80" placeholder="Try robotics or memory" required>'
        .'<button type="submit">Search</button></div></form>'
        .'<p class="swarmbyte-finder-status" aria-live="polite"></p><div class="swarmbyte-finder-results"></div></section>';
});
