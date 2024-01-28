<?php
/**
* 	Plugin Name: Registration Blocker with Wildcards
* 	Plugin URI: https://truthemes.com/wp-registration-blocker/
* 	Description: Blocks certain usernames or email domains from user registration using wildcards like *, ?, and #.
* 	Version: 1.1.04
* 	Author: Cory L Curtis
* 	Author URI: https://truthemes.com/
* 	License: GPL-2.0+
* 	License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

defined('ABSPATH') or die('No script kiddies please!');

// Enqueue the admin CSS
function regblocker_enqueue_admin_styles() {
    wp_enqueue_style('regblocker_admin_css', plugin_dir_url(__FILE__) . 'admin.css');
}
add_action('admin_enqueue_scripts', 'regblocker_enqueue_admin_styles');

// Create the options in the database if they don't exist
function regblocker_initialize_options() {
    if (false == get_option('regblocker_blocked_strings')) {
        add_option('regblocker_blocked_strings');
    }
}
register_activation_hook(__FILE__, 'regblocker_initialize_options');

// Function to create the admin page
function regblocker_create_admin_page() {
    add_menu_page(
        __('Registration Blocker', 'regblocker'),
        __('Registration Blocker', 'regblocker'),
        'manage_options',
        'regblocker',
        'regblocker_settings_page',
        'dashicons-shield-alt',
        82
    );
}
add_action('admin_menu', 'regblocker_create_admin_page');

// Function to display the admin settings page
function regblocker_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Security Nonce
    wp_nonce_field('regblocker_nonce_check', 'regblocker_nonce');

    // Retrieve blocked strings
    $blocked_strings = get_option('regblocker_blocked_strings');

	echo '<div class="wrap-blocker">';
    echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
    echo '<p><b>Enter text for usernames and email addresses you wish to block, one per line.</b></p>
			<p>Use <b>*</b> as a wildcard, <b>?</b>  fo any single character, and <b>#</b>  for any single integer.<br>
			The plugin will invalidate registration no matter where the text is found in the username or email address.</p>';
    echo '<form method="post" action="options.php">';
    
    settings_fields('regblocker_options_group');
    do_settings_sections('regblocker');
    
    echo '<textarea id="regblocker_blocked_strings" name="regblocker_blocked_strings">';
    echo esc_textarea($blocked_strings);
    echo '</textarea>';
	submit_button ( 'Save Me!');
    
    if (isset($_GET['settings-updated'])) {
        add_settings_error('regblocker_messages', 'regblocker_message', __('Settings Saved', 'regblocker'), 'updated');
    }
    
    echo '</form>';
	
	echo '<div class="truthemes-div">';
	echo '<p class="truthemes-p"><b>Copyright © ' . date('Y') . ' &bull; <a class="noogies-a" href="https://technoogies.com" target="_blank" rel="noopener">Technoogies, LLC</a> &mdash; Crafted By <a class="truthemes-a" href="https://truthemes.com" target="_blank" rel="noopener">TruThemes.com</a></b></p>';
    echo '</div>';
}

// Register our settings group
function regblocker_register_settings() {
    register_setting('regblocker_options_group', 'regblocker_blocked_strings', 'regblocker_sanitize_input');
}
add_action('admin_init', 'regblocker_register_settings');

// Sanitize input
function regblocker_sanitize_input($input) {
    return sanitize_textarea_field($input);
}

// Function to check the registration form
function regblocker_validate_registration($errors, $sanitized_user_login, $user_email) {
    $blocked_strings = explode("\n", get_option('regblocker_blocked_strings'));
    $blocked_strings = array_map('trim', $blocked_strings);

    foreach ($blocked_strings as $string) {
        if (regblocker_string_matches($sanitized_user_login, $string) || regblocker_string_matches($user_email, $string)) {
            $errors->add('regblocker_error', __('<strong>ERROR</strong>: This username or email is in violation of our user policy.', 'regblocker'));
            break;
        }
    }

    return $errors;
}
add_filter('registration_errors', 'regblocker_validate_registration', 10, 3);

// Match string against a pattern with wildcards
function regblocker_string_matches($string, $pattern) {
    $pattern = str_replace(
        array("\*", "\?", "\#"),
        array(".*", ".", "[0-9]"),
        preg_quote($pattern, '/')
    );
  
    return (bool) preg_match("/$pattern/i", $string);
}
