<?php
class WorkWechat{
    private $agentId = '';
    private $secret = '-EM';
    private $requestUrl = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=ACCESS_TOKEN';
    private $corpid = '';
    private $dataArr = [];
    private $reData = [];
    private $token = '';
    private $access_token;
    private $aesKey = '';
    private $aes;

    public function notify(){
        $xml = file_get_contents("php://input");
        $baseArr = $this->xmlToArray($xml);
        $msg = $this->decrypt($baseArr['Encrypt']);
        $this->dataArr = $this->xmlToArray($msg);
        $this->reData['ToUserName'] = $this->dataArr['FromUserName'];
        $this->reData['FromUserName'] = $this->dataArr['ToUserName'];
        $this->reData['CreateTime'] = time();
        $this->reData['MsgType'] = 'text';
        $this->reData['Content'] = '回复了:'.$this->dataArr['Content'];
        $this->reply();
    }

    /**
     * 获取xml中的数据，并解密，处理成array
     * @param $xml
     * @return array
     */
    public function getDataArray($xml){
        $baseArr = $this->xmlToArray($xml);
        $msg = $this->decrypt($baseArr['Encrypt']);
        return $this->xmlToArray($msg);
    }

    /**
     * 设置要回复的xml数据
     * @param $arr
     * @return string
     */
    public function setReturnXml($arr){
        $xmlStr = $this->arrayToXml($arr);
        $reData['TimeStamp'] = time();
        $reData['Nonce'] = $reData['TimeStamp'];
        $reData['Encrypt'] = $this->encrypt($xmlStr);
        $reData['MsgSignature'] = $this->getSHA1($reData['TimeStamp'],$reData['Nonce'],$reData['Encrypt']);
        return $this->arrayToXml($reData);
    }

    /**
     * 解密
     *
     * @param $str
     * @return string $reStr
     */
    private function decrypt($str){
        $aes = $this->getAES();
        $re = $aes->decrypt($str);
        $re = $this->decode($re);
        $re = substr($re,16);
        $len_list = unpack('N', substr($re, 0, 4));
        $xml_len = $len_list[1];
        $reStr = substr($re,4,$xml_len);
        $reid = substr($re,$xml_len + 4);
        return $reStr;
    }

    /**
     * 加密
     *
     * @param $text
     * @param $receiveId
     * @return string
     */
    private function encrypt($text){
        //拼接
        $text = $this->getRandomStr() . pack('N', strlen($text)) . $text . $this->corpid;
        //添加PKCS#7填充
        $text = $this->encode($text);
        //加密
        $aes = $this->getAES();
        $re = $aes->encrypt($text);
        return $re;
    }


    /**
     * 对需要加密的明文进行填充补位
     * @param $text 需要进行填充补位操作的明文
     * @return 补齐明文字符串
     */
    private function encode($text){
        $block_size = 32;
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = 32 - ($text_length % 32);
        if ($amount_to_pad == 0) {
            $amount_to_pad = 32;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }

    /**
     * 对解密后的明文进行补位删除
     * @param decrypted 解密后的明文
     * @return 删除填充补位后的明文
     */
    private function decode($text){
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

    public function getAccessToken(){
        if($this->access_token){
            return $this->access_token;
        } else {
            $token = M('token')->where(['id'=>10])->find();
            if($token){
                if($token['expires']>time()+3600){//无需更新
                    $this->access_token = $token['token'];
                    return $this->access_token;
                }
            }
            //需要更新
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid='.$this->corpid.'&corpsecret='.urlencode($this->secret);
            $re = http_query($url);
            if($re['state']==200){
                $arr = json_decode($re['data'],true);
                if(is_array($arr) && isset($arr['access_token'])){
                    $this->access_token = $arr['access_token'];
                    $data['token'] = $arr['access_token'];
                    $data['expires'] = time()+$arr['expires_in'];
                    if($token){
                        M('token')->where(['id'=>10])->save($data);
                    } else {
                        $data['id'] = 10;
                        M('token')->add($data);
                    }
                    return $this->access_token;
                } else {//解析json失败，或者没有返回token
                    return;
                }
            } else {
                return;
            }

        }
    }

    /**
     * 解析xml到数组
     * @param $xmlSrc xml字符串
     * @return array
     */
    protected function xmlToArray($xmlSrc){
        if(empty($xmlSrc)){
            return [];
        }
        $array = [];
        $xml = simplexml_load_string($xmlSrc);
        if($xml && $xml->children()) {
            foreach ($xml->children() as $node){
                //有子节点
                if($node->children()) {
                    $k = $node->getName();
                    $nodeXml = $node->asXML();
                    $v = substr($nodeXml, strlen($k)+2, strlen($nodeXml)-2*strlen($k)-5);
                } else {
                    $k = $node->getName();
                    $v = (string)$node;
                }
                $array[$k] = $v;
            }
        }
        return $array;
    }

    /**
     * 是否向服务器应答,true应答，false不应答
     * @param unknown $do
     */
    protected function reply(){
//        errorLogDB('aa',htmlspecialchars($this->arrayToXml($this->reData)));
        $xmlStr = $this->arrayToXml($this->reData);
        $reData['TimeStamp'] = time();
        $reData['Nonce'] = $reData['TimeStamp'];
//        errorLogDB('xml_str',($xmlStr));
        $reData['Encrypt'] = $this->encrypt($xmlStr);
//        errorLogDB('sha1',$reData['TimeStamp'].'---'.$reData['Nonce'].'---'.$reData['Encrypt']);
        $reData['MsgSignature'] = $this->getSHA1($reData['TimeStamp'],$reData['Nonce'],$reData['Encrypt']);
        $re = $this->arrayToXml($reData);
//        errorLogDB('re_xml',($re));
        exit($re);
    }

    /**
     * 将数组转为XML
     */
    protected function arrayToXml($array){
        $xml = '<xml>';
        forEach($array as $k=>$v){
            if(is_numeric($v) && $k!='Nonce'){
                $xml.='<'.$k.'>'.$v.'</'.$k.'>';
            } else if(is_array($v)) {
                $xml .= $this->arrToXml($k,$v);
            } else {
                $xml.='<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
            }
        }
        $xml.='</xml>';
        return $xml;
    }

    protected function arrToXml($k,$v){
        $str = '';
        $useNum = false;
        forEach($v as $k1=>$v1){
            if(is_numeric($k1)){
                $useNum = true;
                $k1 = $k;
            }
            if(is_numeric($v1)){
                $str .='<'.$k1.'>'.$v1.'</'.$k1.'>';
            } else if(is_array($v1)){
                $str .= $this->arrToXml($k1,$v1);
            } else {
                $str .='<'.$k1.'><![CDATA['.$v1.']]></'.$k1.'>';
            }
        }
        if(!$useNum){
            $str = '<'.$k.'>'.$str.'</'.$k.'>';
        }
        return $str;
    }

    /**
     * 生成随机字符串
     *
     * @return string
     */
    private function getRandomStr()
    {
        $str = '';
        $str_pol = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyl';
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }

    /**
     * 请用getAES方法
     * @return AESMcrypt
     */
    private function getAES(){
        if(!$this->aes){
            $key = base64_decode($this->aesKey);
            $this->aes = new AESMcrypt($key,substr($key,0,16));
        }
        return $this->aes;
    }

    /**
     * 用SHA1算法生成安全签名
     * @param string $timestamp 时间戳
     * @param string $nonce 随机字符串
     * @param string $encrypt_msg 密文消息
     * @return string str
     */
    private function getSHA1($timestamp, $nonce, $encrypt_msg){
        //排序
        $array = array($encrypt_msg, $this->token, $timestamp, $nonce);
        sort($array, SORT_STRING);
        $str = implode($array);
        return sha1($str);
    }

}