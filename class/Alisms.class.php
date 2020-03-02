<?php
class Alisms{
	private $appKey = "";
	private $appSecret = "";
	private $SignName = '';
	private $requestHost = "http://sms.market.alicloudapi.com";
	private $requestUri = '/singleSendSms';
	private $requestMethod = "GET";
	/**
	 * 错误信息
	 * @var String
	 */
	public $debugInfo;
	
	/**
	 * 发送验证码
	 * @param $phone 号码
	 * @param $pin 验证码
     * @return string
	 */
	public function sendPin($phone,$pin){
		$ip = $this->get_client_ip();
		$count = M('sms')->where(['ip'=>$ip,'state'=>'1','addtime'=>['GT',time()-86400]])->count();
		if($count>5){//同ip一天不能发送超过10条短信
			$this->debugInfo = '24小时内不能发送超过5条短信';
			return false;
		}
		$count = M('sms')->where(['ip'=>$ip,'state'=>'1','addtime'=>['GT',time()-120]])->count();
		if($count){//同ip120秒内不能发送多条短信
			$this->debugInfo = '120秒内不能发送多条短信，请稍后再试';
			return false;
		}
		$count = M('sms')->where(['phone'=>$phone,'state'=>'1','addtime'=>['GT',time()-120]])->count();
		if($count){//同号码120秒内不能发送多条短信
			$this->debugInfo = '120秒内不能发送多条短信，请稍后再试';
			return false;
		}
		$result =  $this->pin($pin, $phone);
		if($result){
			$data = [];
			$data['phone'] = $phone;
			$data['ip'] = $ip;
			$data['type'] = '1';
			$data['pin'] = $pin;
			$data['data'] = json_encode(['pin'=>$pin]);
			$data['addtime'] = time();
			$data['state'] = '1';
			M('sms')->add($data);
		}
		return $result;
	}
	
	/**
	 * 从数据库中读取最后一条未超期的验证码
	 * @param $phone 手机号码
     * @return int
	 */
	public function getPin($phone){
		$re = M('sms')->where(['phone'=>$phone,'state'=>'1','addtime'=>['GT',time()-600]])->order('id desc')->find();
		if($re){
			return $re['pin'];
		} else {
			return 0;
		}
	}
	
	/**
	 * 发送验证码
	 * @param 验证码 $pin
	 * @param 手机号 $phone
	 */
	private function pin($pin,$phone){
		$request_paras = array(
			'ParamString' => '{"pin":"'.$pin.'"}',
			'RecNum' => $phone,
			'SignName' => $this->SignName,
			'TemplateCode' => 'SMS_68080085'
		);
		$reJson = $this->request($request_paras);
		$arr = json_decode($reJson,true);
		if(isset($arr['success']) && $arr['success']){
			$this->debugInfo = 'success send!';
			return true;
		} else {
			if(isset($arr['message'])){
				$this->debugInfo = 'Err:'.$arr['message'];
			} else {
				$this->debugInfo = '未知错误';
			}
			return false;
		}
	}
	
	/**
	 * 加密并，请求并返回json数据
	 * @param array() $request_paras
	 */
	private function request($request_paras) {
		ksort($request_paras);
		$request_header_accept = "application/json;charset=utf-8";
		$content_type = "";
		$headers = array(
				'X-Ca-Key' => $this->appKey,
				'Accept' => $request_header_accept,
		);
		ksort($headers);
		$header_str = "";
		$header_ignore_list = array('X-CA-SIGNATURE', 'X-CA-SIGNATURE-HEADERS', 'ACCEPT', 'CONTENT-MD5', 'CONTENT-TYPE', 'DATE');
		$sig_header = array();
		foreach($headers as $k => $v) {
			if(in_array(strtoupper($k), $header_ignore_list)) {
				continue;
			}
			$header_str .= $k . ':' . $v . "\n";
			array_push($sig_header, $k);
		}
		$url_str = $this->requestUri;
		$para_array = array();
		foreach($request_paras as $k => $v) {
			array_push($para_array, $k .'='. $v);
		}
		if(!empty($para_array)) {
			$url_str .= '?' . join('&', $para_array);
		}
		$content_md5 = "";
		$date = "";
		$sign_str = "";
		$sign_str .= $this->requestMethod ."\n";
		$sign_str .= $request_header_accept."\n";
		$sign_str .= $content_md5."\n";
		$sign_str .= "\n";
		$sign_str .= $date."\n";
		$sign_str .= $header_str;
		$sign_str .= $url_str;
	
		$sign = base64_encode(hash_hmac('sha256', $sign_str, $this->appSecret, true));
		$headers['X-Ca-Signature'] = $sign;
		$headers['X-Ca-Signature-Headers'] = join(',', $sig_header);
		$request_header = array();
		foreach($headers as $k => $v) {
			array_push($request_header, $k .': ' . $v);
		}
// 		print_r($this->requestHost . $url_str);
// 		print_r($request_header);
// 		exit();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->requestHost . $url_str);
		//curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}

    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @return mixed
     */
    private function get_client_ip($type = 0) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
}