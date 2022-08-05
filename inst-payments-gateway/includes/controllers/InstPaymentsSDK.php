<?php

class InstPaymentsSDK {

    public function api_v1_payment($post_data, $url, $key, $secret, $passphrase) {
        $timestamp = $this->getMillisecond();
        $method = 'POST';
        $requestPath = '/api/v1/payment';
        $url = $url . $requestPath;

        $sign = $this->sign($timestamp, $method, $requestPath, '', $key, $secret, $post_data);
        $authorization = 'Inst:' . $key . ':' . $timestamp . ':' . $sign;
        return $this->send_post($url, json_encode($post_data), $authorization, $passphrase);
    }

    private function sign($timestamp, $method, $requestPath, $queryString, $apiKey, $apiSecret, $body) {
        $preHash = $this->preHash($timestamp, $method, $requestPath, $queryString, $apiKey, $body);
        $sign = hash_hmac('sha256', utf8_encode($preHash) , utf8_encode($apiSecret), true);
        return base64_encode($sign);
    }

    private function preHash($timestamp, $method, $requestPath, $queryString, $apiKey, $body) {
        $preHash = $timestamp . $method . $apiKey . $requestPath;
        if (!empty($queryString)) {
            $preHash = $preHash . '?' . urldecode($queryString);
        }

        $postStr = '';
        if (!empty($body)){
            foreach ($body as $key => $value) {
                if (is_array($value)) {
                    $postStr .= $key.'=' .json_encode($value).'&';
                } else {
                    $postStr .= $key.'=' .$value.'&';
                }
            }
            $postStr = substr($postStr ,0, -1);
        }
        return $preHash . $postStr;
    }

    private function send_post( $url , $post_data , $authorization, $access_Passphrase) {

        $curl = curl_init($url);

        curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt ($curl, CURLOPT_POST, true);
        curl_setopt ($curl, CURLOPT_POSTFIELDS, ($post_data) );
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = array(
            "Content-Type: application/json; charset=utf-8",
            "Accept: application/json",
            "Authorization:" . $authorization,
            "Access-Passphrase:" . $access_Passphrase,
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $responseText = curl_exec($curl);
        if (!$responseText) {
            echo('CURL_ERROR: ' . var_export(curl_error($curl)));
        }
        curl_close($curl);

        return $responseText;
    }


    private function getMillisecond() {
        list($s1,$s2)=explode(' ',microtime());
        return (float)sprintf('%.0f',(floatval($s1)+floatval($s2))*1000);
    }

    public function formatArray($array) {
        if (is_array($array)) {
            ksort($array);
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->formatArray($value);
                }
            }
        }
        return $array;
    }
}
