<?php
/*
Plugin Name: MatomoTracking
Plugin URI: https://github.com/bluewalk/yourls-matomo
Description: Track the redirections with Matomo
Version: 1.0
Author: bluewalk
Author URI: https://bluewalk.net
*/

// No direct call
if (!defined('YOURLS_ABSPATH')) die();

// Register your plugin admin page
yourls_add_action('plugins_loaded', 'matomo_track__init');
function matomo_track__init()
{
    yourls_register_plugin_page('matomo_track_settings', 'Matomo Tracking', 'matomo_track__settings');
}

// The function for the admin page
function matomo_track__settings()
{
    // Check if form was submitted
    if (isset($_POST['url'])) {
        yourls_verify_nonce('matomo_track__settings');

        matomo_track__settings_update();
    }

    $site_id = yourls_get_option('matomo_track__site_id', 1);
    $url = yourls_get_option('matomo_track__url', 'https://matomo.url');
    $token = yourls_get_option('matomo_track__token', '');
    $nonce = yourls_create_nonce('matomo_track__settings');

    echo <<<HTML
        <main>
            <h2>Matomo Track Settings</h2>
            <form method="post">
            <input type="hidden" name="nonce" value="$nonce" />
            <p>
                <label>Matomo URL</label>
                <input type="text" name="url" value="$url" />
            </p>
            <p>
                <label>Token</label>
                <input type="text" name="token" value="$token" />
            </p>
            <p>
                <label>Site ID</label>
                <input type="text" name="site_id" value="$site_id" />
            </p>
            <p><input type="submit" value="Save" class="button" /></p>
            </form>
        </main>
HTML;
}

// Function to save the options in the database
function matomo_track__settings_update()
{
    $site_id = $_POST['site_id'];
    $url = $_POST['url'];
    $token = $_POST['token'];

    yourls_update_option('matomo_track__site_id', $site_id);
    yourls_update_option('matomo_track__url', $url);
    yourls_update_option('matomo_track__token', $token);
}

// Hook our custom function into the 'pre_redirect' event
yourls_add_action('pre_redirect', 'matomo_track__perform');

function matomo_track__perform($args)
{
    $url = $args[0];

    // Read options from database
    $matomo_url = yourls_get_option('matomo_track__url', 'https://matomo.url');
    $site_id = yourls_get_option('matomo_track__site_id', 1);
    $token = yourls_get_option('matomo_track__token', '');

    require('MatomoTracker.php');

    $matomoTracker = new MatomoTracker($site_id, $matomo_url);
    $matomoTracker->setTokenAuth($token);
    $matomoTracker->setIp($_SERVER['REMOTE_ADDR']);
    $matomoTracker->setUserAgent($_SERVER['HTTP_USER_AGENT']);
    $matomoTracker->doTrackPageView('Redirect to ' . $url);
}
