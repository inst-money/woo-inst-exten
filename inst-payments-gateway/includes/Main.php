<?php

namespace Inst;

use Inst\Gateways\Inst_Gateway;
use Inst\Gateways\Inst_Mastercard_Gateway;
use Inst\Gateways\Inst_Visa_Gateway;
use InstPaymentController;

class Main
{
    const ROUTE_WEBHOOK = 'inst_webhook';
    const ROUTE_MASTERCARD_WEBHOOK = 'inst_mastercard_webhook';
    const ROUTE_VISA_WEBHOOK = 'inst_visa_webhook';

    public static $instance;
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        $this->registerEvents();
    }

    public function registerEvents()
    {
        add_filter('woocommerce_payment_gateways', [$this, 'addPaymentGateways']);
        add_action('woocommerce_api_' . self::ROUTE_WEBHOOK, [new InstPaymentController, 'webhook']);
        add_action('woocommerce_api_' . self::ROUTE_MASTERCARD_WEBHOOK, [new InstPaymentController, 'webhook_master']);
        add_action('woocommerce_api_' . self::ROUTE_VISA_WEBHOOK, [new InstPaymentController, 'webhook_visa']);
    }

    /**
     * woocommerce_payment_gateways, 将我们的PHP类注册为WooCommerce支付网关
     */
    public function addPaymentGateways($gateways)
    {
        $gateways[] = Inst_Gateway::class;
        $gateways[] = Inst_Mastercard_Gateway::class;
        $gateways[] = Inst_Visa_Gateway::class;
        return $gateways;
    }
}
