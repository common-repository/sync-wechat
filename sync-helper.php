<?php
require('XyncManager.php');
function sync_wechat_get_ajax_url($query = [])
{
    $scheme = defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN ? 'https' : 'admin';

    $current_url = sync_wechat_get_current_page_url();
    $ajax_url    = admin_url('admin-ajax.php', $scheme);

    if (preg_match('/^https/', $current_url) && !preg_match('/^https/', $ajax_url)) {
        $ajax_url = preg_replace('/^http/', 'https', $ajax_url);
    }

    if (!empty($query)) {
        $ajax_url = add_query_arg($query, $ajax_url);
    }

    return apply_filters('sync_wechat_ajax_url_ajax_url', $ajax_url);
}
function sync_wechat_get_current_page_url()
{
    global $wp;
    if (get_option('permalink_structure')) {
        $base = trailingslashit(home_url($wp->request));
    } else {
        $base = add_query_arg($wp->query_string, '', trailingslashit(home_url($wp->request)));
        $base = remove_query_arg(['post_type', 'name'], $base);
    }

    $scheme      = is_ssl() ? 'https' : 'http';
    $current_uri = set_url_scheme($base, $scheme);

    if (is_front_page()) {
        $current_uri = home_url('/');
    }
    return apply_filters('sync_wechat_get_current_page_url', $current_uri);
}

function sync_wechat_check_validation_callback()
{
    $appId =  sanitize_text_field($_POST["app_id"]);
    $appSecret = sanitize_text_field($_POST["app_secret"]);
    $endTimestamp = sanitize_text_field($_POST["end_timestamp"]);

    $manager = new XyncManager();
    $token = $manager->getToken($appId, $appSecret);

    if ($token != "token not found") {
        update_option('sync_wechat_options', array('app_id' => $appId, 'app_secret' => $appSecret));
        // offset
        $manager->getOffset($token, $endTimestamp);
    } else {
        echo json_encode(array(
            'code' => 404,
            'msg' => 'not article found'
        ));
    }
}

function sync_wechat_core_sync_process_callback()
{
    $appId = sanitize_text_field($_POST["app_id"]);
    $appSecret = sanitize_text_field($_POST["app_secret"]);
    $postType =  sanitize_text_field($_POST["post_type"]);
    $offset =  sanitize_text_field($_POST["offset"]);
    $startTimestamp = sanitize_text_field($_POST["start_timestamp"]);
    $endTimestamp = sanitize_text_field($_POST["end_timestamp"]);
    $manager = new XyncManager();
    $token = $manager->getToken($appId, $appSecret);
    $manager->saveArticle($token, $postType, $offset, $startTimestamp, $endTimestamp);
}

function scriptsRegister()
{
    wp_register_script('daterangepicker', plugins_url( 'js/daterangepicker.min.js', __FILE__ ), array('moment'), '3.14.1', true);
    wp_register_script('moment', plugins_url( 'moment.min.js', __FILE__ ), array('jquery'), '2.18.1', true);

    wp_enqueue_script('daterangepicker');
    wp_enqueue_style('sync-style', plugins_url( 'css/sync-style.css', __FILE__ ), array(), '1.0.0'); 
    wp_enqueue_style('daterangepicker-css', plugins_url( 'css/daterangepicker.css', __FILE__ ), array(), '1.0.0');

    add_action('wp_ajax_sync_wechat_check_validation', 'sync_wechat_check_validation_callback');
    add_action('wp_ajax_nopriv_sync_wechat_check_validation', 'sync_wechat_check_validation_callback');
    add_action('wp_ajax_sync_wechat_core_sync_process', 'sync_wechat_core_sync_process_callback');
    add_action('wp_ajax_nopriv_sync_wechat_core_sync_process', 'sync_wechat_core_sync_process_callback');
    $localize_sync_wechat_vars = apply_filters(
        'sync_wechat_global_script_vars',
        ['ajaxurl' => sync_wechat_get_ajax_url()]
    );
    wp_localize_script('sync_wechat', 'sync_wechat_global_vars', $localize_sync_wechat_vars);
}
