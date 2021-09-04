# 银联公众号H5支付 PHP

> DEMO里包含了`生成回调地址`、`服务端接收支付通知`、`支付页处理`、`申请退款`及部分，接口其他部分由于没有用到也就没有去实现，不过都大同小异，参照文档实现起来并不困难。

## 所需参数

- appId
- appKey
- 密钥(secert)
- 商户号(mid)
- 终端号(tid)
- 来源编号(srcId)
  
## Tips

- 商户公众号页面 -> 下面称前端
- 商户服务器     -> 下面称后端
- 银联接口       -> 下面称银联
- 文中生成商户交易号(merOrderId)的形式比较粗糙，传递的order_id一般是四五位的数字，请按实际情况对生成方法进行修改
- 参考银联开发文档

## 支付流程

- 下单
  - 用户在前端将下单请求发送给后端，后端生成支付地址，前端拿到支付地址后进行跳转
  - 生成支付地址时可以传递回调地址(noticeUrl)和跳转地址(returnUrl)到银联
  - 银联将以POST + FORM表单的形式向后端回调地址通知支付结果

- 回调
  - 前端支付如果成功，将跳转到微信的支付结果页面(点金计划)，并用`iframe`加载returnUrl，支付结果将用URL参数的形式传递给后端
  - 如果支付失败或取消支付，将直接跳转到returnUrl

- 退款
  - 提供商户交易号、退款金额、order_id即可

## 参考资料

- [银联开发文档 1](https://open.chinaums.com/resources/?code=501548146515780&url=780ccda1-5566-40b9-81d6-9317a841e901)
- [银联开发文档 2](https://res-mop.chinaums.com/upload_doc/%E9%97%A8%E6%88%B7%E6%96%87%E6%A1%A3/%E6%94%AF%E4%BB%98%E6%96%87%E6%A1%A3/75d722d1826b6ac84d78641dbb29c1bb17cc3a3dc78d166e1f9c272d02938325.pdf)
- [微信点金计划开发文档](https://wx.gtimg.com/pay/download/goldplan/goldplan_product_description_v2.pdf)
- 还有一些部分(如计算签名)是从各个网站上收集来的，感谢诸位无私的分享。