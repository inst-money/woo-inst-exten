<?php
/**
 * Plugin Name: WooCommerce Inst Payment Gateway
 * Plugin URI: https://www.inst.money/
 * Description: Take Credit/Debit Card payments on your store.
 * Author: Inst payment
 * Author URI: https://www.inst.money/
 * Version: 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Required minimums and constants
 */
define('INST_PLUGIN_PATH', __DIR__ . '/');
define('INST_PLUGIN_NAME', 'inst-payments-gateway');


add_action('plugins_loaded', 'inst_init');
function inst_init() {

    require_once INST_PLUGIN_PATH . 'includes/Main.php';
    foreach (glob(INST_PLUGIN_PATH . 'includes/*/*.php') as $includeFile) {
        require_once $includeFile;
    }

    $inst = \Inst\Main::getInstance();
    $inst->init();
//    add_action('wp_enqueue_scripts', [$inst, 'addJs']);
}
