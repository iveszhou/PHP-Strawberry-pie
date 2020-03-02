<?php
class Pddapi{
    private $requestUrl = "https://gw-api.pinduoduo.com/api/router";
    private $sysParams;
    private $apiParams;
    private $clientId = '';
    private $clientSecret = '';
    private $ownerId = '';
    public function __construct($clientId='',$clientSecret='',$ownerId='')
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->ownerId = $ownerId;
        $this->sysParams = ['client_id'=>$this->clientId,'access_token'=>'','timestamp'=>time(),'data_type'=>'JSON'];
    }

    public function search($url){
        $item = [];
        $id = $this->getIdFromPddUrl($url);
        $item['id'] = $id;
        if($id){
            $re = $this->goodsDetail($id);
            $arr = json_decode($re,true);
            if(isset($arr['goods_detail_response']['goods_details'][0])){
                $item = $arr['goods_detail_response']['goods_details'][0];
                $re1 = $this->urlGen($url);
                $itemUrl = json_decode($re1,true);
                $item['couponUrl'] = $itemUrl['goods_zs_unit_generate_response']['mobile_short_url'];
            } else {
                $item = $arr;
            }
        }
        return $item;
    }

    private function getIdFromPddUrl($url){
        $temp = explode('?', $url);
        $id = 0;
        if (count($temp) > 1) {
            $temp1 = explode('&', $temp[1]);
            foreach ($temp1 as $row) {
                $temp2 = explode('=', $row);
                if (count($temp2) > 1 && $temp2[0] == 'goods_id') {
                    $id = $temp2[1];
                }
            }
        }
        return $id;
    }

    /**
     * 获取商品信息
     * @param $id int 商品ID
     * @param $cusPar string
     * @return string
     */
    public function goodsDetail($id,$cusPar='zz'){
        //pdd.ddk.goods.detail （多多进宝商品详情查询）
        //https://open.pinduoduo.com/#/apidocument/port?id=27
        $method = 'pdd.ddk.goods.detail';
        $arr['goods_id_list'] = [$id];
        $arr['pid'] = '8216701_50176032';
        $arr['custom_parameters'] = $cusPar;
        $arr['zs_duo_id'] = $this->ownerId;
        return $this->getData($method,$arr);
    }

    public function catsGet(){
        //pdd.goods.cats.get（商品标准类目接口）
        //https://open.pinduoduo.com/#/apidocument/port?id=17
        $method = 'pdd.goods.cats.get';
        $arr['parent_cat_id'] = 0;
        return $this->getData($method,$arr);
    }

    public function optGet(){
        //pdd.goods.opt.get（商品标准类目接口）
        //https://open.pinduoduo.com/#/apidocument/port?id=17
        $method = 'pdd.goods.opt.get';
        $arr['parent_opt_id'] = 0;
        return $this->getData($method,$arr);
    }

    /**
     * 获取商品列表
     * @param $q
     * @param int $oid
     * @param int $cid
     * @param null $idList
     * @param int $page
     * @param int $row
     * @return string
     */
    public function goodsSearch($q,$oid=0,$cid=0,$idList=null,$page=1,$row=20){
        //pdd.ddk.goods.search（多多进宝商品查询）
        //https://open.pinduoduo.com/#/apidocument/port?id=27
        $method = 'pdd.ddk.goods.search';
        $arr = [];
        if($q){
            $arr['keyword'] = $q;
        }
        if($oid){
            $arr['opt_id'] = $oid;//opt_id    LONG    非必填     商品标签类目ID，使用pdd.goods.opt.get获取
        }
        $arr['page'] = $page;
        $arr['page_size'] = $row;
        $arr['sort_type'] = 0;
        $arr['with_coupon'] = 'false';
        if($cid){
            $arr['cat_id'] = $cid;
        }
        if($idList){
            $arr['goods_id_list'] = $idList;
        }
        $arr['zs_duo_id'] = $this->ownerId;//招商多多客ID
        $arr['pid'] = '8216701_50176032';//推广位id
        $arr['custom_parameters'] = 'zz';
        return $this->getData($method,$arr);

    }

    /**
     * 获取推广链接
     * @param $id
     * @return string
     */
    public function urlGenerate($id){
        //pdd.ddk.goods.promotion.url.generate（多多进宝推广链接生成）
        $method = 'pdd.ddk.goods.promotion.url.generate';
        $arr = [];
        $arr['p_id'] = '8216701_50176032';
        $arr['goods_id_list'] = [$id];
        $arr['generate_short_url'] = 'true';
        $arr['multi_group'] = 'false';
        $arr['generate_weapp_webview'] = 'false';//是否生成唤起微信客户端链接
        $arr['generate_we_app'] = 'false';//是否生成小程序推广
        $arr['zs_duo_id'] = $this->ownerId;//招商多多客ID
        $arr['pid'] = '8216701_50176032';//推广位id
        $arr['custom_parameters'] = 'zz';
        return $this->getData($method,$arr);
    }

    /**
     * 查询订单
     * @return string
     */
    public function incrementGet(){
        //pdd.ddk.order.list.increment.get（最后更新时间段增量同步推广订单信息）
        $method = 'pdd.ddk.order.list.increment.get';
        $arr = [];
        $arr['start_update_time'] = time()-86400;
        $arr['end_update_time'] = time();
        $arr['page_size'] = 100;
        $arr['page'] = 1;
        $arr['return_count'] = 'true';
        return $this->getData($method,$arr);

    }

    /**
     * 获取红包推广链接
     * @param $id
     * @return string
     */
    public function redUrlGenerate($id){
        //pdd.ddk.rp.prom.url.generate（生成红包推广链接）
        $method = 'pdd.ddk.rp.prom.url.generate';
        $arr = [];
        $arr['p_id_list'] = ['8216701_50176032'];
        $arr['goods_id_list'] = [$id];
        $arr['generate_short_url'] = 'true';
        $arr['multi_group'] = 'false';
        $arr['generate_weapp_webview'] = 'false';//是否生成唤起微信客户端链接
        $arr['generate_we_app'] = 'false';//是否生成小程序推广
        $arr['zs_duo_id'] = $this->ownerId;//招商多多客ID
        $arr['pid'] = '8216701_50176032';//推广位id
        $arr['custom_parameters'] = 'zz';
        return $this->getData($method,$arr);
    }

    public function promUrlGenerate($type){
        //pdd.ddk.cms.prom.url.generate（生成商城-频道推广链接）
        $method = 'pdd.ddk.cms.prom.url.generate';
        $arr = [];
        $arr['generate_short_url'] = 'true';
        $arr['p_id_list'] = ['8216701_50176032'];
        $arr['generate_mobile'] = 'true';
        $arr['channel_type'] = $type;//0, "1.9包邮"；1, "今日爆款"； 2, "品牌清仓"； 4,"PC端专属商城"；5, "养宝宝兑现金"；不传值为默认商城
        $arr['multi_group'] = 'false';
        $arr['generate_weapp_webview'] = 'false';//是否生成唤起微信客户端链接
        $arr['generate_we_app'] = 'false';//是否生成小程序推广
        $arr['zs_duo_id'] = $this->ownerId;//招商多多客ID
        $arr['pid'] = '8216701_50176032';//推广位id
        $arr['custom_parameters'] = 'zz';
        return $this->getData($method,$arr);
    }

    public function listGet(){
        //pdd.ddk.theme.list.get（多多进宝主题列表查询）
        $method = 'pdd.ddk.theme.list.get';
        $arr = [];
        $arr['page_size'] = 100;
        $arr['page'] = 10;
        return $this->getData($method,$arr);
    }

    public function urlGen($url){
        //pdd.ddk.goods.zs.unit.url.gen（多多进宝转链接口）
        $method = 'pdd.ddk.goods.zs.unit.url.gen';
        $arr = [];
        $arr['source_url'] = $url;
        $arr['pid'] = '8216701_50176032';
        return $this->getData($method,$arr);
    }

    public function listQuery(){
        //pdd.ddk.top.goods.list.query（多多客获取爆款排行商品接口）
        $method = 'pdd.ddk.top.goods.list.query';
        $arr = [];
        $arr['pid'] = '8216701_50176032';
        $arr['offset'] = 0;
        $arr['sort_type'] = 1;
        $arr['limit'] = 200;
        return $this->getData($method,$arr);
    }

    public function phraseGenerate($id){
        //pdd.ddk.phrase.generate（生成多多口令接口）
        $method = 'pdd.ddk.phrase.generate';
        $arr = [];
        $arr['p_id'] = '8216701_50176032';
        $arr['goods_id_list'] = [$id];
//        $arr['multi_group'] = 'false';
        $arr['custom_parameters'] = 'zz';
        $arr['zs_duo_id'] = $this->ownerId;
//        $arr['style'] = 1;
        return $this->getData($method,$arr);
    }


    /**
     * 刷新access_token
     * @param $refresh_token
     */
    public function refresh_token($refresh_token){
        $arr = [];
        $arr['client_id'] = $this->clientId;
        $arr['refresh_token'] = $refresh_token;
        $arr['grant_type'] = 'refresh_token';
        $arr['client_secret'] = $this->clientSecret;
        $url = 'http://open-api.pinduoduo.com/oauth/token';
        $json = json_encode($arr);
        $headers = array("Content-Type: application/json",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
        );
        $re = $this->curl($url,$json,$headers);
        print_r($re);
    }

    /**
     * 步骤1，请求此页面，手工登陆获取code
     * http://jinbao.pinduoduo.com/open.html?client_id=18984f5473f54aa0aba56f9184c2c838&response_type=code&redirect_uri=http://a.84ci.com/Notify/pdd.html&state=1234
     * 输入code获取access_token和refresh_token
     * 通过refresh_token刷新access_token和refresh_token
     * @param $code
     */
    public function get_access_token($code){
        $arr = [];
        $arr['client_id'] = $this->clientId;
        $arr['code'] = $code;
        $arr['grant_type'] = 'authorization_code';
        $arr['client_secret'] = $this->clientSecret;
        $url = 'http://open-api.pinduoduo.com/oauth/token';
        $json = json_encode($arr);
        $headers = array("Content-Type: application/json",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
        );
        $re = $this->curl($url,$json,$headers);
        print_r($re);
    }

    /**
     * 检查列表数据的单项
     * @param $arr
     */
    private function chkArrayItem($arr){

    }

    /**
     * 查询单个商品的数据
     */
    private function chkItem($arr){

    }

    /**
     * 单个商品的优惠券信息
     */
    private function chkCoupon($arr){

    }

    private function setParams($k,$v){
        $this->apiParams[$k] = $v;
    }

    /**
     * 签名算法
     * @param $params
     * @return string
     */
    private function sign($params)
    {
        ksort($params);
        $stringToBeSigned = $this->clientSecret;
        foreach ($params as $k => $v) {
            if(is_array($v)){
                $stringToBeSigned .= $k.json_encode($v);
            } else {
                $stringToBeSigned .= $k.$v;
            }
        }
        $stringToBeSigned .= $this->clientSecret;
        return strtoupper(md5($stringToBeSigned));
    }

    /**
     * http请求
     * @param $url string
     * @param $postFields string|array
     * @param $header array
     * @return string
     */
    public function curl($url, $postFields = [],$header=[])
    {
        $reData = [];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0");
        if(is_array($header) && !empty($header)){
            curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
        }
        if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (is_array($postFields) && 0 < count($postFields)){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));//POST数据
        } else if($postFields){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);//POST数据
        }
        $reData = curl_exec($ch);
        curl_close($ch);
        return $reData;
    }


    private function getData($method,$argsArr)
    {
        $this->sysParams["type"] = $method;
        $this->apiParams = [];
        if(isset($this->sysParams["sign"])){
            unset($this->sysParams["sign"]);
        }
        foreach ($argsArr as $k=>$v){
            $this->setParams($k,$v);
        }
        $arr = array_merge($this->apiParams, $this->sysParams);
        $arr["sign"] = $this->sign($arr);
        $str = '';
        foreach ($arr as $k => $v){
            if(is_array($v)){
                $str .= $k.'='.urlencode(json_encode($v)).'&';
            } else {
                $str .= $k.'='.urlencode($v).'&';
            }
        }
        return $this->curl($this->requestUrl, $str);
    }
}