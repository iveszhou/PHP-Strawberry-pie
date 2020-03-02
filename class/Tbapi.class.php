<?php
class Tbapi{
    //用户id，网站id，广告区域id
    private $requestUrl = "http://gw.api.taobao.com/router/rest?";
    public $adZoneId;
    public $webID;
    private $appkey;
    private $secretKey;
    private $sysParams;
    private $apiParams;
    private $percent = 0;
    public function __construct($appkey='',$secretKey='',$webID='',$adZoneId='')
    {
        $this->appkey = $appkey;
        $this->secretKey = $secretKey;
        $this->webID = $webID;
        $this->adZoneId = $adZoneId;
        $this->sysParams = ['v'=>'2.0','format'=>'json','app_key'=>$this->appkey,'sign_method'=>'md5'];

    }

    /**
     * 从淘口令中获取商品信息
     * @param $content
     * @return string
     */
    public function tpwdQuery($content){
        //*****重要，有用
        //taobao.wireless.share.tpwd.query (淘口令查询)
        //http://open.taobao.com/doc2/apiDetail.htm?apiId=32461&scopeId=11998
        $method = 'taobao.wireless.share.tpwd.query';
        $arr['password_content'] = $content;
//        $a['tpwd_param'] = json_encode($arr);
        return $this->getData($method,$arr);
    }

//    /**
//     * 聚划算搜索
//     * @param string $q
//     * @param int $page
//     * @return array
//     */
//    public function juItemsSearch($q='',$page=1){
//        //taobao.ju.items.search (聚划算商品搜索接口)
//        //http://open.taobao.com/doc2/apiDetail.htm?apiId=28762&scopeId=11655
//        $method = 'taobao.ju.items.search';
//        $arr['current_page'] = $page;//页码,必传
//        $arr['page_size'] = 20;//一页大小,必传
//        $arr['pid'] = 'mm_'.$this->pubId.'_'.$this->webId.'_'.$this->adZoneId;//媒体pid,必传
//        $arr['postage'] = null;//是否包邮,可不传
//        $arr['status'] = null;//状态，预热：1，正在进行中：2,可不传
//        $arr['taobao_category_id'] = null;//淘宝类目id,可不传
//        $arr['word'] = $q;//搜索关键词,可不传
//        $a['param_top_item_query'] = json_encode($arr);
//        return $this->getData($method,$a);
//    }

    /**
     * 创建淘口令
     * @param $img
     * @param $url
     * @param $text
     * @return string
     */
    public function tpwdCreate($img,$url,$text){
        //taobao.tbk.tpwd.create (淘宝客淘口令)
        //http://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.uqjPp6&apiId=31127
        $method = 'taobao.tbk.tpwd.create';
        $arr['ext'] = '{}';
        $arr['logo'] = $img;
        $arr['url'] = $url;
        $arr['text'] = $text;
        $arr['user_id'] = '125602975';
        return $this->getData($method,$arr);
    }

    /**
     * 搜索商品
     * @param $q
     * @param $size
     * @param $page
     * @return string
     */
    public function search($q,$size=100,$page=1){
        //http://open.taobao.com/api.htm?docId=35896&docType=2
        //taobao.tbk.dg.material.optional
        $method = 'taobao.tbk.dg.material.optional';
//        $arr['platform'] = 2;
        $arr['material_id'] = 6707;
        $arr['adzone_id'] = $this->adZoneId;
        $arr['page_size'] = $size;
        $arr['page_no'] = $page;
        $arr['q'] = $q;
        return $this->getData($method,$arr);
    }

    /**
     * @param $q
     * @param $cat
     * @param int $size
     * @param int $page
     * @return string
     */
    public function searchList($q=0,$cat=0,$size=100,$page=1){
        //http://open.taobao.com/api.htm?docId=35896&docType=2
        //taobao.tbk.dg.material.optional
        $method = 'taobao.tbk.dg.material.optional';
//        $arr['platform'] = 2;
        $arr['material_id'] = 6707;
        $arr['adzone_id'] = $this->adZoneId;
        $arr['page_size'] = $size;
        $arr['page_no'] = $page;
        if($q){
            $arr['q'] = $q;
        }
        if($cat){
            $arr['cat'] = 16;
        }
        if(!$q && !$cat){//如果都不成立
            $arr['cat'] = '16';
        }
        return $this->getData($method,$arr);
    }


    public function orderGet(){
        //https://open.taobao.com/api.htm?docId=24527&docType=2
        $method = 'taobao.tbk.order.get';
        $arr['fields'] = 'tb_trade_parent_id,tb_trade_id,num_iid,item_title,item_num,price,pay_price,seller_nick,seller_shop_title,commission,commission_rate,unid,create_time,earning_time,tk3rd_pub_id,tk3rd_site_id,tk3rd_adzone_id,relation_id,tb_trade_parent_id,tb_trade_id,num_iid,item_title,item_num,price,pay_price,seller_nick,seller_shop_title,commission,commission_rate,unid,create_time,earning_time,tk3rd_pub_id,tk3rd_site_id,tk3rd_adzone_id,special_id,click_time';
        $arr['start_time'] = '2019-06-17 18:56:30';
        $arr['order_query_type'] = 'create_time';
        return $this->getData($method,$arr);
    }

    /**
     * 淘宝客维权退款订单查询
     * @param $startTime
     * @param $type
     * @param int $duringTime
     * @return string
     */
    public function orderRelationRefund($startTime,$type='1',$duringTime=1200){
        //https://open.taobao.com/api.htm?docId=40121&docType=2&scopeId=16175
        $startTime -= 100;
        $method = 'taobao.tbk.relation.refund';
        $arr['page_size'] = 20;//页大小，默认20，1~100
        $arr['search_type'] = $type;//1-维权发起时间，2-订单结算时间（正向订单），3-维权完成时间，4-订单创建时间
        $arr['refund_type'] = '1';//1 表示2方，2表示3方
        $arr['start_time'] = date('Y-m-d H:i:s',$startTime);//开始时间
        $endTime = $startTime+$duringTime;
        $arr['end_time'] = date('Y-m-d H:i:s',$endTime);
        $arr['page_no'] = 1;//第几页，默认1，1~100
        $arr['biz_type'] = 2;//1代表渠道关系id，2代表会员关系id
        print_r($arr);
        $search['search_option'] = json_encode($arr);
        return $this->getData($method,$search);
    }

    /**
     * 通过指定的时间，查询20分钟内的订单
     * @param int $startTime 开始时间 必须是unix时间戳
     * @param int $type 查询类型 可选值 1,2,3
     * @param int $duringTime 时间区间，可选值1200到10800
     * @return string
     */
    public function orderDetailsGet($startTime,$type=1,$duringTime=1200){
        $endTime = $startTime+$duringTime;
        //https://open.taobao.com/api.htm?docId=43328&docType=2&scopeId=16175
        $method = 'taobao.tbk.order.details.get';
        $arr['query_type'] = $type;//查询时间类型，1：按照订单淘客创建时间查询，2:按照订单淘客付款时间查询，3:按照订单淘客结算时间查询
//        $arr['position_index'] = '';//位点，除第一页之外，都需要传递；前端原样返回。
        $arr['page_size'] = 100;//页大小，默认20，1~100
//        $arr['member_type'] = 2;//推广者角色类型,2:二方，3:三方，不传，表示所有角色
//        $arr['tk_status'] = 12;//淘客订单状态，12-付款，13-关闭，14-确认收货，3-结算成功;不传，表示所有状态
        $arr['start_time'] = date('Y-m-d H:i:s',$startTime);//订单查询结束时间，订单开始时间至订单结束时间，中间时间段日常要求不超过3个小时，但如618、双11、年货节等大促期间预估时间段不可超过20分钟，超过会提示错误，调用时请务必注意时间段的选择，以保证亲能正常调用！
        $arr['end_time'] = date('Y-m-d H:i:s',$endTime);
        $arr['jump_type'] = '-1';//跳转类型，当向前或者向后翻页必须提供,-1: 向前翻页,1：向后翻页
        $arr['page_no'] = '1';//第几页，默认1，1~100
        $arr['order_scene'] = '1';//场景订单场景类型，1:常规订单，2:渠道订单，3:会员运营订单，默认为1
        return $this->getData($method,$arr);
    }

    /**
     * 根据品类选择商品
     * @param $mateid
     * @param int $page
     * @param int $size
     * @param string $device
     * @param string $deviceData
     * @return array
     */
    public function couponCat($mateid,$page=1,$size=20,$device='',$deviceData=''){
        $list = [];
        //http://open.taobao.com/api.htm?docId=33947&docType=2
        //taobao.tbk.dg.optimus.material
        //mid详情数据
        //好券
        //品牌券
        //聚划算拼团
        //母婴主题
        //有好货
        //潮流范
        //特惠
        //https://tbk.bbs.taobao.com/detail.html?appId=45301&postId=8576096
        $method = 'taobao.tbk.dg.optimus.material';
        $arr['page_size'] = $size;
        $arr['adzone_id'] = $this->adZoneId;
        $arr['page_no'] = $page;
        $arr['material_id'] = $mateid;
        if($device && $deviceData){
            $arr['device_value'] = md5($deviceData);
            $arr['device_encrypt'] = 'MD5';
            $arr['device_type'] = $device;
        }
        $re = $this->getData($method,$arr);
        $arr = json_decode($re,true);
        if($arr && isset($arr['tbk_dg_optimus_material_response']['result_list']['map_data'])){
            foreach ($arr['tbk_dg_optimus_material_response']['result_list']['map_data'] as $row){
                $temp = [];
                $temp['coupon_click_url'] = 'https:'.$row['coupon_click_url'];
                $temp['num_iid'] = $row['item_id'];
                $temp['shop_title'] = $row['shop_title'];
                $temp['pict_url'] = $row['pict_url'];
                $temp['title'] = $row['title'];
                $temp['volume'] = $row['volume'];
                $temp['coupon_info'] = ($row['coupon_start_fee']+0)==0?$row['coupon_amount'].'元无条件券':'满'.$row['coupon_start_fee'].'元减'.$row['coupon_amount'].'元';
                $temp['realPrice'] = getRealPrice($row['zk_final_price'],$temp['coupon_info']);
                $temp['zk_final_price'] = $row['zk_final_price'];
                $temp['fanli'] = fanLi($temp['realPrice'],$row['commission_rate']);
                $temp['fanPrice'] = $temp['realPrice'] - $temp['fanli'];
                $list[] = $temp;
            }
        }
        return $list;
    }

    /**
     * 获取商品信息
     * @param $ids string 商品ID串，用,分割
     * @param $platform string 链接形式：1：PC，2：无线，默认：１
     * @return string
     */
    public function infoGet($ids,$platform){
        //taobao.tbk.item.info.get (淘宝客商品详情（简版）)
        //http://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.ZL3ZRT&apiId=24518
        $method = 'taobao.tbk.item.info.get';
        $arr['fields'] = 'num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,volume';
        $arr['num_iids'] = $ids;
        $arr['platform'] = $platform;
        return $this->getData($method,$arr);
    }

    public function favoritesItemGet(){
        //taobao.tbk.uatm.favorites.item.get (获取淘宝联盟选品库的宝贝信息)
        //http://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.DTiWQ1&apiId=26619
        $method = 'taobao.tbk.uatm.favorites.item.get';
        $arr['platform'] = '1';
        $arr['page_size'] = '100';
        $arr['adzone_id'] = $this->adZoneId;
        $arr['favorites_id'] = '17366185';
        $arr['fields'] = 'num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick,shop_title,zk_final_price_wap,event_start_time,event_end_time,tk_rate,status,type,coupon_click_url,coupon_info,click_url';
        return $this->getData($method,$arr);
    }

    public function guessLike(){
        //taobao.tbk.item.guess.like
        //http://open.taobao.com/api.htm?docId=29528&docType=2
        $method = 'taobao.tbk.item.guess.like';
        $arr['adzone_id'] = $this->adZoneId;
        $arr['os'] = 'android';
        $arr['ip'] = '183.14.28.14';
        $arr['ua'] = 'Mozilla/5.0';
        $arr['net'] = 'wifi';
        $arr['user_nick'] = 'njhupo';
        return $this->getData($method,$arr);
    }

    public function favoritesGet(){
        //taobao.tbk.uatm.favorites.get (获取淘宝联盟选品库列表)
        //http://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.xT1qNT&apiId=26620
        $method = 'taobao.tbk.uatm.favorites.get';
        $arr['fields'] = 'favorites_title,favorites_id,type';
        $arr['type'] = 1;
        return $this->getData($method,$arr);

    }

    public function tqgGet($page=1){
        //taobao.tbk.ju.tqg.get (淘抢购api)
        //http://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.DLCunu&apiId=27543
        $method = 'taobao.tbk.ju.tqg.get';
        $arr['adzone_id'] = $this->adZoneId;
        $arr['fields'] = 'click_url,pic_url,reserve_price,zk_final_price,total_amount,sold_num,title,category_name,start_time,end_time';
        $startDayTime = strtotime(date('Y-m-d'));
        $arr['start_time'] = date('Y-m-d H:i:s',$startDayTime);
        $arr['end_time'] = date('Y-m-d H:i:s',$startDayTime+86400);
        $arr['page_no'] = $page;
        $arr['page_size'] = 20;
        return $this->getData($method,$arr);
    }

    public function spreadGet($url){
        //长链接转短连接
        //taobao.tbk.spread.get (物料传播方式获取)
        //http://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.AwxIX4&apiId=27832
        $method = 'taobao.tbk.spread.get';
        $arr['url'] = $url;
        $a['requests'] = json_encode($arr);
        return $this->getData($method,$a);
    }

    /**
     * 获取优惠券
     * @param string $q
     * @param string $cat
     * @param int $page
     * @param int $pagesize
     * @return string
     */
    public function dgCouponGet($q='',$cat='', $page=1,$pagesize=20){
        //taobao.tbk.dg.item.coupon.get (好券清单API【导购】)
        //http://open.taobao.com/docs/api.htm?spm=a219a.7395905.0.0.fJ59mB&apiId=29821
        $method = 'taobao.tbk.dg.item.coupon.get';
        $arr['adzone_id'] = $this->adZoneId;
        if($q){
            $arr['q'] = $q;
        } else {
            $arr['cat'] = $cat;
        }
        $arr['page_no'] = $page;
        $arr['page_size'] = $pagesize;
        $arr['platform'] = 2;
        return $this->getData($method,$arr);
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
        $stringToBeSigned = $this->secretKey;
         foreach ($params as $k => $v)
        {
            if(!is_array($v))
            {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $this->secretKey;
        return strtoupper(md5($stringToBeSigned));
    }

    /**
     * 从喵口令url中获取id
     * @param $url
     * @return int|string
     */
    private function getTmallID($url){
        $id = 0;
        $re = http_query($url);
        preg_match("/itemId\":(\d*?),/i", $re['data'], $matches);
        if (is_numeric($matches[1]) && $matches[1] > 0) {
            $id = $matches[1];
        }
        return $id;
    }

    /**
     * 从淘宝url中获取商品id
     * @param $url
     * @return int|string
     */
    private function getIDFromTBUrl($url){
        $temp = explode('?', $url);
        $id = 0;
        if (count($temp) > 1) {
            $temp1 = explode('&', $temp[1]);
            foreach ($temp1 as $row) {
                $temp2 = explode('=', $row);
                if (count($temp2) > 1 && $temp2[0] == 'id') {
                    $id = $temp2[1];
                }
            }
        }
        if ($id == 0) {
            preg_match("/i(\d*?)\.htm/i", $url, $matches);
            if (is_numeric($matches[1]) && $matches[1] > 0) {
                $id = $matches[1];
            }
        }
        return $id;
    }

    /**
     * http请求
     * @param $url
     * @param null $postFields
     * @return string
     */
    public function curl($url, $postFields = null)
    {
        $header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0");
        curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
        //https 请求
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
        $this->sysParams["timestamp"] = date("Y-m-d H:i:s");
        $this->sysParams["method"] = $method;
        $this->apiParams = [];
        if(isset($this->sysParams["sign"])){
            unset($this->sysParams["sign"]);
        }
        foreach ($argsArr as $k=>$v){
            $this->setParams($k,$v);
        }
        $this->sysParams["sign"] = $this->sign(array_merge($this->apiParams, $this->sysParams));
        $reqUrl = $this->requestUrl;
        foreach ($this->sysParams as $sysParamKey => $sysParamValue)
        {
            $reqUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }
        $reqUrl = substr($reqUrl, 0, -1);
        return $this->curl($reqUrl, $this->apiParams);
    }
}