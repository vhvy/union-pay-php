<?php

require_once "./union.php";

$appid = "123";
$appkey = "123";
$secert = "123";
$mid = "123";
$tid = "123";
$srcId = "123";

$union_pay = new UnionPay($appid, $appkey, $secert, $mid, $tid, $srcId);

#### ---------生成支付地址------- ####

$orderId = "114514";
// 商户内部订单号

$merOrderId = $union_pay->createMerOrderId($orderId);
// 商户交易号

$attachedData = [
    'order_type' => "vip"
];
// 传递给银联的商户附加数据，在银联向商户服务器发起通知时会携带上。

$fee = 1;
// 商品金额，单位为分，如1即为0.01元。
// 可以用bcmul进行计算，如金额18.24元，bcmul("18.24", "100", 0);

$noticeUrl = "";
// 传递给银联的回调URL，支付成功后银联将以POST + form的形式向商户此地址发送支付结果

$returnUrl = "";
// 传递给银联的跳转URL，在微信浏览器内支付成功后，银联将先跳转到微信的支付结果页面(点金计划)，微信的支付结果页面将使用iframe加载此URL，加载时会将支付结果用URL参数的形式拼接在URL后面
// 银联在使用测试接口时，支付完毕将直接关闭页面，不会跳转到支付结果页面，只有生产接口才会跳转到支付结果页面。

$expireTime = 600;
// 支付链接过期时间

$jumpUrl = $union_pay->generatePayUrl($attachedData, $merOrderId, $fee, $noticeUrl, $returnUrl, $expireTime);
// 传递给前端用来跳转的地址


#### --------商户服务端接收支付结果通知---------- ####
// 这里是$noticeUrl被调用的地方

$noticeParams = $_POST;
// 银联传递过来的通知信息

$res = $union_pay->validMsg($noticeParams);
// 通知信息是否正确



#### ---------商户支付结果页面验签和显示结果----------- ####
// 这里是$returnUrl被支付结果页加载的地方

$excludeField = [];
// 如果$returnUrl，即传递给银联的回调地址中含有query参数，将query字段放在excludeField中即可，getValidReturnUrl函数将过滤掉原有的query参数。当然，参数不能和银联的回调参数冲突。
// 如果是没有任何url参数，$noticeParams直接传递$_GET即可

$noticeParams = getValidReturnUrl($_GET, $excludeField);
// 银联通过URL参数传递上来的通知消息

$res = $union_pay->validMsg($noticeParams);
// 通知信息是否正确

$totalAmount = "18.8";
// 订单金额

$jumpUrl = "https://qq.com";
// 支付结果页面点击按钮将要跳转到的地址

$is_success = $_GET['status'] == 'TRADE_SUCCESS';

// 然后在这里输出 `jsPayGoldPlanResult.html` 的内容即可




#### -----申请退款------- ####
// 这里将会用到AccessToken，建议使用redis或其他工具将toekn缓存起来，避免重复请求。

$merOrderId = "123123123123";
$fee = 1;
$orderId = "123";

$res = $union_pay->requestRefund($merOrderId, $fee, $orderId);

if ($res->errCode == "SUCCESS") {
    echo "退款成功";
} else {
    echo $res->errMsg;
}
