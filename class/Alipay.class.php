<?php
class Alipay{
    private $requestUrl = "https://openapi.alipay.com/gateway.do";
    private $sysArr = [];
    private $cusArr = [];
    private $appID = '';
    //我是直接把文件内容赋值给priKey和pubKey了，惊不惊喜意不意外？如果不直接赋值，那么需要读取文件内容然后赋值
    private $priKey = '';
    private $pubKey = '';
    public function __construct(){
        $this->sysArr['app_id'] = $this->appID;
        $this->sysArr['format'] = 'JSON';
        $this->sysArr['charset'] = 'UTF-8';
        $this->sysArr['sign_type'] = 'RSA2';
        $this->sysArr['timestamp'] = date('Y-m-d H:i:s');
        $this->sysArr['version'] = '1.0';
        $this->sysArr['notify_url'] = 'http://xxx.com/api.php?c=Notify&a=alipay';
        $this->sysArr['return_url'] = 'http://xxx.com/paySuccess.php';
    }

    /**
     * 回调校验
     * 并没有写完，请勿用
     */
    public function pcStatus(){
        $sign = $_GET['sign'];
        unset($_GET['sign']);
        unset($_GET['sign_type']);
        print_r($_GET);
        echo $sign.PHP_EOL.PHP_EOL;
        echo $this->sign($_GET).PHP_EOL.PHP_EOL;
        if($this->verify($_GET,$sign)){
            echo 'success';
        } else {
            echo 'failed';
        }
    }

    /**
     * 资金清算
     * @param $uuid 流水号
     * @param $account 账号
     * @param $amount 金额
     * @param string $realName 用户真实姓名
     * @return mixed
     * @throws Exception
     */
    public function payOut($uuid,$account,$amount,$realName=''){
        //https://docs.open.alipay.com/309/106237/
        $this->sysArr['method'] = 'alipay.fund.trans.toaccount.transfer';
        $this->cusArr['out_biz_no'] = $uuid;
        $this->cusArr['payee_type'] = 'ALIPAY_LOGONID';
        $this->cusArr['payee_account'] = $account;
        $this->cusArr['amount'] = $amount;
        $this->cusArr['payer_show_name'] = '名字';
        if($realName){
            $this->cusArr['payee_real_name'] = $realName;
        }
        $this->cusArr['remark'] = '标签';
        $postData['biz_content'] = json_encode($this->cusArr,JSON_UNESCAPED_UNICODE);
        $data = array_merge($this->sysArr, $postData);
        $this->sysArr['sign'] = $this->sign($data);
        $this->sysArr['biz_content'] = json_encode($this->cusArr,JSON_UNESCAPED_UNICODE);
//        echo $this->sysArr['sign'];
        $requestUrl = $this->requestUrl . "?";
        foreach ($this->sysArr as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }
        $requestUrl = substr($requestUrl, 0, -1);
        return $this->curl($requestUrl);
    }

    /*
     * 电脑支付
     */
    public function payPc(){
        //https://docs.open.alipay.com/api_1/alipay.trade.page.pay
        $this->sysArr['method'] = 'alipay.trade.page.pay';
        $this->cusArr['body'] = '对一笔交易的具体描述信息';
        $this->cusArr['subject'] = '测试';
        $this->cusArr['out_trade_no'] = '5';
        $this->cusArr['timeout_express'] = '90m';
        $this->cusArr['total_amount'] = '0.01';
        $this->cusArr['product_code'] = 'FAST_INSTANT_TRADE_PAY';
        $this->cusArr['goods_type'] = '1';
        $postData['biz_content'] = json_encode($this->cusArr,JSON_UNESCAPED_UNICODE);
        $data = array_merge($this->sysArr, $postData);
        $this->sysArr['sign'] = $this->sign($data);
        $this->sysArr['biz_content'] = json_encode($this->cusArr,JSON_UNESCAPED_UNICODE);
//        echo $this->sysArr['sign'];
        $requestUrl = $this->requestUrl . "?";
        foreach ($this->sysArr as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }
        $requestUrl = substr($requestUrl, 0, -1);
        header("Location: ".$requestUrl);
    }


    /*
     * 查询转账订单接口
     */
    public function payOutStatus($uuid){
        //https://docs.open.alipay.com/api_28/alipay.fund.trans.order.query
        //{"alipay_trade_query_response":{"code":"10000","msg":"Success","buyer_logon_id":"680***@qq.com","buyer_pay_amount":"0.00","buyer_user_id":"2088202466210971","invoice_amount":"0.00","out_trade_no":"1","point_amount":"0.00","receipt_amount":"0.00","send_pay_date":"2017-09-30 11:03:03","total_amount":"0.01","trade_no":"2017093021001004970285383970","trade_status":"TRADE_SUCCESS"},"sign":"LX/GeUuM8/EOY5yT6YN0YiNRErFargjdPxysqaA3sNqmgpc3oNOqbsrmoOBTH1FhOfCjX0DknauAjJqNPbI7Ni+dSPkRWBueU9c/FmOICXY5yOyW7FA6nNAj7pzb7Og5hsJDJTizUZvGWcraE0z99lX68st20FQDIw31Kqpcf1vW1QBZ7lgZHi0PgiNa1olrfE2Q1yYsTW85MB135kr0xy8GccuD+0D+GmJym35lGUamUXknTtTknPF/SYoy+MlsxqMmALwAjY4bRDC16pZUfA4SwvbsFGj4qC8jA2TyoQfO/30VwEAXAgFJtanogJriuuh9wV/kT/hLmFvSr7keMw=="}Array
        //
        $this->sysArr['method'] = 'alipay.fund.trans.order.query';
        $this->cusArr['out_biz_no'] = $uuid;
        $postData['biz_content'] = json_encode($this->cusArr,JSON_UNESCAPED_UNICODE);
        $data = array_merge($this->sysArr, $postData);
        $this->sysArr['sign'] = $this->sign($data);
        $this->sysArr['biz_content'] = json_encode($this->cusArr,JSON_UNESCAPED_UNICODE);
//        echo $this->sysArr['sign'];
        $requestUrl = $this->requestUrl . "?";
        foreach ($this->sysArr as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }
        $requestUrl = substr($requestUrl, 0, -1);
        return $this->curl($requestUrl);
    }

    /*
     * 支付状态查询
     */
    public function payStatus($uuid){
        //https://docs.open.alipay.com/api_1/alipay.trade.query
        //{"alipay_trade_query_response":{"code":"10000","msg":"Success","buyer_logon_id":"680***@qq.com","buyer_pay_amount":"0.00","buyer_user_id":"2088202466210971","invoice_amount":"0.00","out_trade_no":"1","point_amount":"0.00","receipt_amount":"0.00","send_pay_date":"2017-09-30 11:03:03","total_amount":"0.01","trade_no":"2017093021001004970285383970","trade_status":"TRADE_SUCCESS"},"sign":"LX/GeUuM8/EOY5yT6YN0YiNRErFargjdPxysqaA3sNqmgpc3oNOqbsrmoOBTH1FhOfCjX0DknauAjJqNPbI7Ni+dSPkRWBueU9c/FmOICXY5yOyW7FA6nNAj7pzb7Og5hsJDJTizUZvGWcraE0z99lX68st20FQDIw31Kqpcf1vW1QBZ7lgZHi0PgiNa1olrfE2Q1yYsTW85MB135kr0xy8GccuD+0D+GmJym35lGUamUXknTtTknPF/SYoy+MlsxqMmALwAjY4bRDC16pZUfA4SwvbsFGj4qC8jA2TyoQfO/30VwEAXAgFJtanogJriuuh9wV/kT/hLmFvSr7keMw=="}Array
        //
        $this->sysArr['method'] = 'alipay.trade.query';
        $this->cusArr['out_trade_no'] = $uuid;
        $postData['biz_content'] = json_encode($this->cusArr,JSON_UNESCAPED_UNICODE);
        $data = array_merge($this->sysArr, $postData);
        $this->sysArr['sign'] = $this->sign($data);
        $this->sysArr['biz_content'] = json_encode($this->cusArr,JSON_UNESCAPED_UNICODE);
//        echo $this->sysArr['sign'];
        $requestUrl = $this->requestUrl . "?";
        foreach ($this->sysArr as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }
        $requestUrl = substr($requestUrl, 0, -1);
        return $this->curl($requestUrl);
    }

    /**
     * 手机支付
     */
    public function payMobile(){
        //https://docs.open.alipay.com/203/107090/
        $this->sysArr['method'] = 'alipay.trade.wap.pay';
        $this->cusArr['body'] = '对一笔交易的具体描述信息';
        $this->cusArr['subject'] = '测试';
        $this->cusArr['out_trade_no'] = '1';
        $this->cusArr['timeout_express'] = '90m';
        $this->cusArr['total_amount'] = '0.01';
        $this->cusArr['product_code'] = 'QUICK_WAP_WAY';
        $this->cusArr['goods_type'] = '1';
        $postData['biz_content'] = json_encode($this->cusArr,JSON_UNESCAPED_UNICODE);
        $data = array_merge($this->sysArr, $postData);
        $this->sysArr['sign'] = $this->sign($data);
        $this->sysArr['biz_content'] = json_encode($this->cusArr,JSON_UNESCAPED_UNICODE);
//        echo $this->sysArr['sign'];
        $requestUrl = $this->requestUrl . "?";
        foreach ($this->sysArr as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }
        $requestUrl = substr($requestUrl, 0, -1);
        header("Location: ".$requestUrl);
    }

    /**
     * 私钥签名
     * @param $dataArr
     * @return string
     */
    private function sign($dataArr){
        $data = $this->getSignContent($dataArr);
        $res = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($this->priKey, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";
        openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }

    /**
     * 公钥校验
     * @param $data
     * @param $sign
     * @return bool
     */
    function verify($data,$sign) {
        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($this->pubKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        return $result;
    }

    /**
     * 签名方法
     * @param $params
     * @return string
     */
    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
//        echo $stringToBeSigned.PHP_EOL;
        return $stringToBeSigned;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    /**
     * http请求
     * @param $url
     * @param null $postFields
     * @return mixed
     * @throws Exception
     */
    private function curl($url, $postFields = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $postBodyString = "";
        $encodeArray = Array();
        $postMultipart = false;
        if (is_array($postFields) && 0 < count($postFields)) {
            foreach ($postFields as $k => $v) {
                if ("@" != substr($v, 0, 1)) //判断是不是文件上传
                {
                    $postBodyString .= "$k=" . urlencode($v) . "&";
                    $encodeArray[$k] = $v;
                } else //文件上传用multipart/form-data，否则用www-form-urlencoded
                {
                    $postMultipart = true;
                    $encodeArray[$k] = new \CURLFile(substr($v, 1));
                }
            }
            unset ($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }
        if ($postMultipart) {
            $headers = array('content-type: multipart/form-data;charset=UTF-8;boundary=' . $this->getMillisecond());
        } else {
            $headers = array('content-type: application/x-www-form-urlencoded;charset=UTF-8');
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $reponse = curl_exec($ch);
        curl_close($ch);
        return $reponse;
    }

    /**
     * 获取毫秒
     * @return float
     */
    private function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }
}