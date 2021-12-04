<?php
/**
 * Plugin Name: 清除粉絲頁商品工具
 * Plugin URI:
 * Description: Facebook for WooCommerce 延伸外掛，清除粉絲頁商品工具。解決 Facebook 自家廢物外掛同步問題
 * Version: 1.0.0
 * Author: Chun
 * Author URI: https://www.mxp.tw/contact/
 * License: MIT
 */

if (!defined('WPINC')) {
    die;
}

function mxp_rfps_add_submenu_pages() {
    add_submenu_page('woocommerce', '清除粉絲頁商品', '清除粉絲頁商品', 'manage_woocommerce', 'mxp-reset-fbpage-products', 'mxp_reset_fbpage_products_page');
}
add_action('admin_menu', 'mxp_rfps_add_submenu_pages');

function mxp_reset_fbpage_products_page() {
    if (!function_exists('facebook_for_woocommerce')) {
        echo "請先安裝 <strong><a href='https://tw.wordpress.org/plugins/facebook-for-woocommerce/' target='_blank'>Facebook for WooCommerce(v2.6.7以上版本)</a> 並授權連結粉絲頁<strong>";
        return;
    }
    wp_register_script('mxp-reset-fbpage-products-custom-js', plugin_dir_url(__FILE__) . 'js/main.js', array('jquery'), '1.0', false);
    wp_localize_script('mxp-reset-fbpage-products-custom-js', 'MXP_RFPS', array(
        'ajaxurl'      => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('wp_rest'),
        'catalog_id'   => facebook_for_woocommerce()->get_integration()->get_product_catalog_id(),
        'access_token' => facebook_for_woocommerce()->get_connection_handler()->get_access_token(),
    ));
    wp_enqueue_script('mxp-reset-fbpage-products-custom-js');

    echo '第一步驟：<button type="button" id="mxp_go_get_products_btn">取得粉絲頁商品資訊</button><br>';
    echo '<div id="show_items"></div>';

}

function mxp_ajax_get_fbpage_products() {
    if (!isset($_POST['data']['nonce']) || !wp_verify_nonce($_POST['data']['nonce'], 'wp_rest') || !is_admin()) {
        wp_send_json_error(array('msg' => '授權有問題，你想幹嘛？'));
    }
    $catalog_id   = $_POST['data']['catalog_id'];
    $access_token = $_POST['data']['access_token'];
    $entry_api    = 'https://graph.facebook.com/v12.0/' . $catalog_id . '/products?access_token=' . $access_token . '&limit=3000';
    $data         = mxp_fbapi_recursive_get($entry_api, array());
    if (empty($data) || !isset($data['data']) || count($data['data']) == 0) {
        $error = isset($data['error']) ? $data['error'] : '';
        wp_send_json_error(array('msg' => '無回傳資料，請稍後再試！', 'error' => $error, 'raw' => $data));
    } else {
        wp_send_json_success($data['data']);
    }
}
add_action('wp_ajax_mxp_ajax_get_fbpage_products_action', 'mxp_ajax_get_fbpage_products');

function mxp_ajax_del_fbpage_products() {
    if (!isset($_POST['data']['nonce']) || !wp_verify_nonce($_POST['data']['nonce'], 'wp_rest') || !is_admin()) {
        wp_send_json_error(array('msg' => '授權有問題，你想幹嘛？'));
    }
    $id           = $_POST['data']['id'];
    $access_token = $_POST['data']['access_token'];
    $args         = array(
        'timeout'     => 20,
        'redirection' => 5,
        'httpversion' => '1.1',
        'user-agent'  => 'WooCommerce',
        'method'      => 'DELETE',
    );
    $response = wp_remote_request('https://graph.facebook.com/v12.0/' . $id . '?access_token=' . $access_token, $args);
    $body     = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($body['success'])) {
        wp_send_json_error(array('msg' => $response));
    } else {
        wp_send_json_success($id);
    }
}
add_action('wp_ajax_mxp_ajax_del_fbpage_products_action', 'mxp_ajax_del_fbpage_products');

//遞回撈API資料回來
function mxp_fbapi_recursive_get($url = '', $data = array()) {
    $args = array(
        'timeout'     => 20,
        'redirection' => 5,
        'httpversion' => '1.1',
        // 'user-agent'  => 'WooCommerce',
        'cookies'     => array(),
    );
    $response = wp_remote_get($url, $args);
    if (is_array($response) && !is_wp_error($response)) {
        $headers = $response['headers']; // array of http header lines
        $body    = json_decode($response['body'], true); // use the content
        switch (json_last_error()) {
        case JSON_ERROR_NONE:
            if (isset($body['data']) && count($body['data']) != 0 && isset($body['paging']['next'])) {
                if (isset($data['data'])) {
                    $data['data'] = array_merge($data['data'], $body['data']);
                } else {
                    $data['data'] = $body['data'];
                }
                return mxp_fbapi_recursive_get($body['paging']['next'], $data);
            } else {
                $data['raw'] = $response;
                return $data;
            }
            break;
        case JSON_ERROR_DEPTH:
        case JSON_ERROR_STATE_MISMATCH:
        case JSON_ERROR_CTRL_CHAR:
        case JSON_ERROR_SYNTAX:
        case JSON_ERROR_UTF8:
        default:
            $data['raw']   = $response;
            $data['error'] = 'JSON_ERROR';
            return $data;
            break;
        }
    } else {
        $data['error'] = 'REMOTE_GET_ERROR';
        return $data;
    }
}
