<?php
/*
Plugin Name: 微信公众号文章同步助手
Plugin URI: https://lqin2333.gitee.io/blog/2021/08/01/xync/
Version: 1.0.1
Author: 好势发生科技
Author URI: https://lqin2333.gitee.io/blog/
Description: 微信公众号同步助手
*/
function sync_wechat_showMenu()
{
    add_menu_page('微信公众号同步助手', '微信同步', 8, __FILE__, 'sync_wechat_showDashboard');
}

function sync_wechat_showDashboard()
{
    include('dashboard.php');
}

if (is_admin()) {
    add_action('admin_menu', 'sync_wechat_showMenu');
}


if (get_option('sync_wechat_options') == null) {
    add_option('sync_wechat_options', array('app_id' => 'app_id', 'app_secret' => 'app_secret'));
}

require_once 'sync-helper.php';
scriptsRegister();




