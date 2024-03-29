<?php

use Inst\Gateways\Inst_Crypto_Gateway;
use Inst\Gateways\Inst_Gateway;
use Inst\Gateways\Inst_Mastercard_Gateway;
use Inst\Gateways\Inst_Visa_Gateway;

class InstPaymentController {

    /**
     * @throws Exception
     */
    public function payment($gateway, $payType) {
        $orderId = (int)WC()->session->get('inst_order');
//        echo $orderId . "\n";

        $order = wc_get_order($orderId);
        if (empty($order)) {
            throw new Exception('Order not found: ' . $orderId);
        }

        $sdk = new InstPaymentsSDK();
        $url = $gateway->domain . '';
        $key = $gateway->api_key . '';
        $secret = $gateway->api_secret . '';
        $passphrase = $gateway->api_password . '';

        $customer = array(
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'country' => $order->get_billing_country(),
            'state' => $order->get_billing_state(),
            'city' => $order->get_billing_city(),
            'address' => $order->get_billing_address_1() . $order->get_billing_address_2(),
            'zipcode' => $order->get_billing_postcode(),
        );

//        // todo 哪里获取商品信息？
//        $product_info = array(
//            'name' => 'name'
//        );
//
//        $shipping_info = array(
//            'phone' => $order->get_shipping_phone(),
//            'first_name' => $order->get_shipping_first_name(),
//            'last_name' => $order->get_shipping_last_name(),
//            'country' => $order->get_shipping_country(),
//            'state' => $order->get_shipping_state(),
//            'city' => $order->get_shipping_city(),
//            'address' => $order->get_shipping_address_1() . $order->get_shipping_address_2(),
//            'zipcode' => $order->get_shipping_postcode(),
//            'company' => $order->get_shipping_company(),
//        );

        $post_data = array(
            'currency' => $order->get_currency(),
            'amount' => $order->get_total(),
            'cust_order_id' => 'woo' . substr($key, 0 ,5) . date("YmdHis",time()) . $orderId,
            'customer' => $customer,
//            'product_info' => $product_info,
//            'shipping_info' => $shipping_info,
//            'return_url' => $order->get_view_order_url(),
            'network' => $payType,
        );

        $post_data = $sdk->formatArray($post_data);

        $result = $sdk->api_v1_payment($post_data, $url, $key, $secret, $passphrase);
//        echo $result . "\n";

        $result = json_decode($result, true);
        if ( $result['code'] === 0 ) {

            // 给客户的一些备注（用false代替true使其变为私有）
            $order->add_order_note( 'Payment is processing on ' . $result['result']['redirect_url'], true );

            // 空购物车
            WC()->cart->empty_cart();

//            echo $result['result']['redirect_url'] . "\n";
            // 重定向，根据配置决定跳转到receipt_page(iframe)还是inst支付页面
//            echo 'iframe?:' . $gateway->iframe . "\n";
            if ($gateway->iframe === 'yes') {
//                echo 'iframe:' . $result['result']['redirect_url'] . "\n";
                update_post_meta($orderId, 'inst_url', $result['result']['redirect_url']);
                return array(
                    'result' => 'success',
                    'redirect' => $order->get_checkout_payment_url(true),
                    'payment_url' => $result['result']['redirect_url'],
                );
            } else {
//                echo 'redirect:' . $result['result']['redirect_url'] . "\n";
                return array(
                    'result' => 'success',
                    'redirect' => $result['result']['redirect_url'],
                );
            }
        } else if ($result['code'] === 117008) {
            wc_add_notice('Transaction already exist. Please check in order-view page.', 'error' );
            return array(
                'result' => 'error',
            );
        } else {
            wc_add_notice(  'Please try again.', 'error' );
            return array(
                'result' => 'error',
            );
        }
    }

    public function webhook() { // todo 起一个service去做

        http_response_code(200);
        header('Content-Type: application/json');

        $gateway = new Inst_Gateway;
        $enabled = $gateway->api_webhook;
        if ($enabled === 'no') {
            echo json_encode([
                'code' => 1,
                'msg'  => 'REFUSE',
            ]);
            die;
        }

        $this->webhook_internal();
    }

    public function webhook_master() { // todo 起一个service去做

        http_response_code(200);
        header('Content-Type: application/json');

        $gateway = new Inst_Mastercard_Gateway;
        $enabled = $gateway->api_webhook;
        if ($enabled === 'no') {
            echo json_encode([
                'code' => 1,
                'msg'  => 'REFUSE',
            ]);
            die;
        }

        $this->webhook_internal();
    }

    public function webhook_visa() { // todo 起一个service去做

        http_response_code(200);
        header('Content-Type: application/json');

        $gateway = new Inst_Visa_Gateway;
        $enabled = $gateway->api_webhook;
        if ($enabled === 'no') {
            echo json_encode([
                'code' => 1,
                'msg'  => 'REFUSE',
            ]);
            die;
        }

        $this->webhook_internal();
    }

    public function webhook_crypto() { // todo 起一个service去做

        http_response_code(200);
        header('Content-Type: application/json');

        $gateway = new Inst_Crypto_Gateway;
        $enabled = $gateway->api_webhook;
        if ($enabled === 'no') {
            echo json_encode([
                'code' => 1,
                'msg'  => 'REFUSE',
            ]);
            die;
        }

        $this->webhook_internal();
    }

    private function webhook_internal() {
        http_response_code(200);
        header('Content-Type: application/json');

        // todo 验签
        $result = true;

        if ($result) { //check succeed
            $tmpData = strval(file_get_contents("php://input"));
            $dataArray = json_decode($tmpData, true);

            if (strcmp($dataArray['action'], 'order_result') == 0) {
                foreach ($dataArray['events'] as $val) {
                    $value = json_decode($val, true);
                    $order_id = substr($value['params']['cust_order_id'], 22);
                    $order = wc_get_order($order_id);
                    if (empty($order)) {
                        continue;
                    }

                    $status = $value['params']['status'];
                    if ($status == 1) { // 成功
                        $order->payment_complete();
                        $order->add_order_note( 'Payment is completed. (Inst Webhook)', true);
                    } elseif ($status == 4) { // 失败
                        $order->update_status('failed', 'Failed. (Inst Webhook)');
                    } elseif ($status == 5) { // 取消
                        $order->update_status('cancelled', 'Cancelled. (Inst Webhook)');
                    } elseif ($status == 6) { // 过期
                        $order->update_status('failed', 'Expired. (Inst Webhook)');
                    }
                    // todo 其他订单状态可自行添加
                }
            } // todo 是否需要接收其他推送action？
            echo json_encode([
                'code' => 0,
                'msg'  => 'SUCCESS',
            ]);
            die;
        } else {
            echo json_encode([
                'code' => 3,
                'msg'  => 'VERIFY SIG FAIL',
            ]);
            die;
        }
    }
}
