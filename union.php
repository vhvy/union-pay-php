<?php

require_once "./util.php";

class UnionPay
{
    protected $appid;
    protected $appkey;
    protected $gateway;
    protected $mid;
    protected $tid;
    protected $srcId;
    protected $secert;
    protected $cacheKey = "union_access_token";

    protected $gateway_prod = "https://api-mop.chinaums.com/v1/netpay/webpay/pay";

    protected $gateway_dev = "https://test-api-open.chinaums.com/v1/netpay/webpay/pay";


    /**
     * 初始化
     *
     * @param string $appid
     * @param string $appkey
     * @param boolean $is_dev
     * @Description
     * @example
     * @author vhvy
     */
    public function __construct($appid, $appkey, $secert, $mid, $tid, $srcId, $is_dev = false)
    {
        $this->appid = $appid;
        $this->appkey = $appkey;
        $this->secert = $secert;
        $this->mid = $mid;
        $this->tid = $tid;
        $this->srcId = $srcId;
        $this->gateway = $is_dev ? $this->gateway_dev : $this->gateway_prod;
    }

    /**
     * 生成前端用来跳转到银联页面拉起微信支付的URL
     *
     * @param array $attachedData
     * @param string $merOrderId
     * @param integre $fee
     * @param string $noticeUrl
     * @param string $returnUrl
     * @param integer $expire_time
     * @return string
     * @Description
     * @example
     * @author vhvy
     */
    public function generatePayUrl($attachedData, $merOrderId, $fee, $noticeUrl, $returnUrl, $expireTime = 600)
    {
        $body = [
            'requestTimestamp' => date("Y-m-d H:i:s", time()),
            'mid' => $this->mid,
            'tid' => $this->tid,
            'attachedData' => $attachedData,
            'merOrderId' => $merOrderId,
            'instMid' => 'YUEDANDEFAULT',
            'totalAmount' => $fee,
            'expireTime' => date("Y-m-d H:i:s", time() + $expireTime),
            'notifyUrl' => $noticeUrl,
            'returnUrl' => $returnUrl,
        ];

        $body = json_encode($body);

        $url = $this->gateway . '/v1/netpay/webpay/pay';
        $timestamp = date("YmdHis", time());
        $nonce = md5(uniqid(microtime(true), true));
        $signature = urlencode(getSignature($this->appid, $this->appkey, $timestamp, $nonce, $body));
        $reqUrl = $url . "?timestamp=" . $timestamp . "&authorization=OPEN-FORM-PARAM&appId=" . $this->appid . "&nonce=" .
            $nonce . "&content=" . urlencode($body) . "&signature=" . $signature;
        return $reqUrl;
    }

    /**
     * 生成商户单号
     *
     * @param string $id
     * @return string
     * @Description 单号长度 - 时间长度 - 来源ID长度
     * @example
     * @author vhvy
     */
    public function createMerOrderId($id)
    {
        $orderIdLen = 32;
        $length = $orderIdLen - 14 - strlen($this->srcId);

        return $this->srcId .  date("YmdHis", time()) . fillZero($id, $length);
    }

    /**
     * 生成退款单号
     *
     * @param string $merOrderId
     * @param string $orderId
     * @return string
     * @Description
     * @example
     * @author vhvy
     */
    public function createRefundOrder($merOrderId, $orderId)
    {
        $timeLength = 14;
        $refundOrderIdLength = 28;

        $len = strlen($this->srcId) + $timeLength;
        $prefix = substr($merOrderId, 0, $len);
        $fillLength = $refundOrderIdLength - $len;

        $refundOrderId = $prefix . fillZero($orderId, $fillLength);
        return $refundOrderId;
    }

    /**
     * 验证银联通知的正确性
     *
     * @param array | string $params
     * @return boolean
     * @Description
     * @example
     * @author vhvy
     */
    public function validMsg($params)
    {
        $arraylist = null;
        if (is_string($params)) {
            $decode_url = urldecode($params);
            $arraylist = explode("&", $decode_url);
        } else {
            $arraylist = [];
            foreach ($params as $k => $v) {
                $arraylist[] = urldecode($k . '=' . $v);
            }
        }

        sort($arraylist);
        $signbf = ""; //银联通知中的sign
        $calSign = "";
        $strForSign = []; //待签串
        $signType = "MD5";

        for ($i = 0; $i < count($arraylist); $i++) {
            $arr = explode("=", $arraylist[$i], 2);
            if ($arr[0] == 'signType') {
                $signType = $arr[1];
            }

            if ($arr[0] == 'sign') {
                $signbf = $arr[1];
            } else {
                $strForSign[] = $arraylist[$i];
            }
        }

        $strForSign = implode("&", $strForSign);

        $strForSign .= $this->secert;

        if ($signType == 'MD5') {
            $calSign = strtoupper(md5($strForSign));
        } else {
            $calSign = strtoupper(bin2hex(hash('sha256', $strForSign, true)));
        }

        return $calSign == $signbf;
    }

    /**
     * 刷新Token
     *
     * @return object
     * @Description
     * @example
     * @author vhvy
     */
    public function flushUnionAccessToken()
    {
        $timestamp = date("YmdHis", time());
        $nonce = md5(uniqid(microtime(true), true));
        $str = $this->appid . $timestamp . $nonce . $this->appkey;
        $signature = bin2hex(hash('sha256', $str, true));

        $body = json_encode([
            'appId' => $this->appid,
            'timestamp' => $timestamp,
            'nonce' =>  $nonce,
            'signMethod' => "SHA256",
            'signature' => $signature
        ]);

        $url = $this->gateway . '/v1/token/access';
        $res = curlpost($url, $body);

        return json_decode($res);
    }


    /**
     * 获取AccessToken
     *
     * @return string
     * @Description
     * @example
     * @author vhvy
     */
    public function getAccessToken()
    {
        $tokenInfo = cache_get($this->cacheKey);
        if (!$tokenInfo || time() + 30 < $tokenInfo['expiresIn']) {
            // 缓存里不存在任何配置信息或者已经过期，则刷新Token
            $info = $this->flushUnionAccessToken();
            $tokenInfo = [
                'token' => $info->accessToken,
                'expiresIn' => $info->expiresIn + time()
            ];
            cache_set($this->cacheKey, $tokenInfo);
        }

        return $tokenInfo['token'];
    }

    /**
     * 发起退款请求
     *
     * @param string $merOrderId
     * @param interge $fee
     * @param string $orderId
     * @return object
     * @Description
     * @example
     * @author vhvy
     */
    public function requestRefund($merOrderId, $fee, $orderId)
    {
        $time = time();

        $refundOrderId = $this->createRefundOrder($merOrderId, $orderId);
        $body = json_encode([
            'requestTimestamp' => date("Y-m-d H:i:s", $time),
            'merOrderId' => $merOrderId,
            'instMid' => 'YUEDANDEFAULT',
            'mid' => $this->mid,
            'tid' => $this->id,
            'refundAmount' => $fee,
            'refundOrderId' => $refundOrderId
        ]);

        $token = $this->getAccessToken();

        $url = $this->gateway . '/v1/netpay/refund';

        $header = array(
            'Authorization: OPEN-ACCESS-TOKEN AccessToken="' . $token . '"'
        );

        $res = curlpost($url, $body, $header);

        $res = json_decode($res);

        if ($res->errCode == "SUCCESS") {
            $msg = array(
                'errCode' => $res->errCode,
                'status' => true,
                'refundOrderId' => $res->refundOrderId
            );
        } else {
            $msg = array(
                'errCode' => $res->errCode,
                'status' => false,
                'errMsg' => $res->errMsg
            );
        }
        return $msg;
    }
}
