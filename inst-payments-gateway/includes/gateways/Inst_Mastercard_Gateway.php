<?php

namespace Inst\Gateways;

use Exception;
use WC_Payment_Gateway;
use InstPaymentController;

if (!defined('ABSPATH')) {
    exit;
}

class Inst_Mastercard_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'instpay_mastercard'; // 支付网关插件ID
        $this->icon = ''; // todo 将显示在结帐页面上您的网关名称附近的图标的URL
        $this->has_fields = true; // todo 如果需要自定义信用卡形式
        $this->method_title = 'Inst Mastercard Payments Gateway';
        $this->method_description = 'Take Mastercard payments on your store.'; // 将显示在选项页面上

        // 网关可以支持订阅，退款，保存付款方式，
        // 这里仅支持支付功能
        $this->supports = array(
            'products'
        );

        // 具有所有选项字段的方法
        $this->init_form_fields();

        // 加载设置。
        $this->init_settings();
        $this->enabled = $this->get_option( 'enabled' );
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );

        $this->domain = $this->get_option( 'domain' );
        $this->api_key = $this->get_option( 'api_key' );
        $this->api_secret = $this->get_option( 'api_secret' );
        $this->api_password = $this->get_option( 'api_password' );
        $this->api_webhook = $this->get_option( 'api_webhook' );
        $this->iframe = $this->get_option( 'iframe' );

        // 这个action hook保存设置
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);

        $this->controller = new InstPaymentController;
    }

    /**
     * 插件设置选项
     */
    public function init_form_fields(){
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Inst Mastercard Payment Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Master Card',
//                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pay with your mastercard via our super-cool payment gateway.',
            ),
            'domain' => array (
                'title'       => 'Domain',
                'type'        => 'text',
                'default'     => 'https://api.firstchampion.io',
            ),
            'api_key' => array (
                'title'       => 'API Key',
                'type'        => 'text',
            ),
            'api_secret' => array (
                'title'       => 'API Secret',
                'type'        => 'text',
            ),
            'api_password' => array (
                'title'       => 'API Password',
                'type'        => 'text',
            ),
            'api_webhook' => array (
                'title'       => 'Webhook',
                'label'       => 'Enable Inst Mastercard Payment Webhook',
                'type'        => 'checkbox',
                'description' => 'url : http(s)://{host}?wc-api=inst_mastercard_webhook',
                'default'     => 'no',
            ),
            'iframe' => array (
                'title'       => 'Iframe',
                'label'       => 'Enable Inst Payment Iframe',
                'type'        => 'checkbox',
                'description' => 'If use iframe, inst_pay page will be displayed as an iframe on the receipt_page of woo, otherwise it will jump directly to the inst_pay page.',
                'default'     => 'no',
            ),
        );
    }

    /**
     * 字段验证
     */
    public function validate_fields() {
    }

    /**
     * 处理付款
     * @throws Exception
     */
    public function process_payment( $order_id ) {
        WC()->session->set('inst_order', $order_id);
        WC()->session->set('inst_network', 1);
        return $this->controller->payment($this);
    }

    public function receipt_page($order_id)
    {
        $inst_url = get_post_meta($order_id, 'inst_url', true);

        ?>

        <iframe
            src='<?= $inst_url; ?>' height='795' width=100% frameBorder='0' id="new_iframe">
        </iframe>

        <?php
    }

//    /**
//     * 自定义信用卡表格
//     */
//    public function payment_fields() {
//
//        ...
//
//    }
//
//    /*
//     * 自定义CSS和JS，在大多数情况下，仅在使用自定义信用卡表格时才需要
//     */
//    public function payment_scripts() {
//
//        ...
//
//    }
//
//
//
}
