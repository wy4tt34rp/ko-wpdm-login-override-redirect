<?php
/**
 * Plugin Name: KO WPDM Login Override & Redirect
 * Description: Unified login + redirect control for WPDM Operational Users.
 * Version: 1.1.1
 * Author: KO
 */

/**
 * NOTES (do not remove without testing):
 * - LiteSpeed Cache:
 *   - Do Not Cache URIs: /download* and /dashboard-access*
 *   - Do Not Cache Roles: operational_user
 *   - Cache Logged-in Users: OFF
 * - Cloudflare:
 *   - Cache Rule: bypass cache for /download* and /dashboard-access*
 *   - Security Custom Rule: Skip WAF/Bot Fight/Rate Limit for /download* and /dashboard-access*
 * - WPDM:
 *   - Disable WPDM login CAPTCHA to prevent login failure/loops
 */

if (!defined('ABSPATH')) exit;

/**
 * 1) If logged out and visiting /download/*, redirect to /dashboard-access
 */
add_action('template_redirect', function () {

    if (is_admin()) return;

    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';

    // Never redirect the login page itself
    if (strpos($request_uri, '/dashboard-access') === 0) return;

    // Only protect downloads
    if (strpos($request_uri, '/download/') !== 0) return;

    if (is_user_logged_in()) return;

    $login_url = home_url('/dashboard-access/');
    $login_url = add_query_arg(
        'redirect_to',
        rawurlencode('/download/operational-documents/'),
        $login_url
    );

    wp_safe_redirect($login_url);
    exit;

}, 1);

/**
 * 2) Post-login redirect — OPERATIONAL USERS NEVER SEE wp-admin
 */
add_filter('login_redirect', function ($redirect_to, $requested_redirect_to, $user) {

    if (is_wp_error($user) || !($user instanceof WP_User)) {
        return $redirect_to;
    }

    // Operational users ALWAYS go to Operational Documents
    if (in_array('operational_user', (array) $user->roles, true)) {
        return home_url('/download/operational-documents/');
    }

    // Everyone else: normal WordPress behavior
    return $redirect_to;

}, 10, 3);

/**
 * 3) Hard block operational users from wp-admin entirely
 */
add_action('admin_init', function () {

    if (!is_user_logged_in()) return;

    $user = wp_get_current_user();

    if (!in_array('operational_user', (array) $user->roles, true)) {
        return;
    }

    // Allow admin-ajax for front-end functionality
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }

    wp_safe_redirect(home_url('/download/operational-documents/'));
    exit;
});

/**
 * 4) Hide admin bar on frontend for operational users
 */
add_filter('show_admin_bar', function ($show) {

    if (!is_user_logged_in()) return $show;

    $user = wp_get_current_user();
    if (in_array('operational_user', (array) $user->roles, true)) {
        return false;
    }

    return $show;
});

/**
 * Safety: Never let WPDM render its own login form.
 * We use /dashboard-access as the only login surface.
 */
add_filter('wpdm_show_login_form', '__return_false');

