<?php

/**
 * 字符串左补0
 *
 * @param string $str
 * @param int $length
 * @return string
 * @Description
 * @example
 * @author vhvy
 */
function fillZero($str, $length)
{
    return str_pad((string)$str, $length, '0', STR_PAD_LEFT);
}

function getSignature($appid, $appkey, $timestamp, $nonce, $body)
{
    $str = bin2hex(hash('sha256', $body, true));
    $signature = base64_encode(hash_hmac('sha256', "$appid$timestamp$nonce$str", $appkey, true));
    return $signature;
}

/**
 * CURL请求
 *
 * @param string $url
 * @param string $body
 * @param array $header
 * @return string json
 * @Description
 * @example
 * @author vhvy
 */
function curlpost($url, $body, $header = array())
{

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $headers = array_merge(array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($body),
    ), $header);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}


/**
 * 过滤通知链接中原有的query参数
 *
 * @param array $params
 * @param array $excludeField
 * @return array
 * @Description
 * @example
 * @author vhvy
 */
function getValidReturnUrl($params, $excludeField = [])
{

    $include_field = [];

    foreach ($params as $k => $v) {
        if (!in_array($k, $excludeField)) {
            $include_field[$k] = $v;
        }
    }

    return $include_field;
}