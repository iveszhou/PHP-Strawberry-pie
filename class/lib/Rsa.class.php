<?php
class Rsa{
	private $privateKey='';//私钥（用于用户加密）
	private $publicKey='';//公钥（用于服务端数据解密）

	/**
	 * 构造函数，获取公钥和私钥
	 */
	public function __construct($privateKey,$publicKey){
		if($privateKey){
			$this->privateKey = openssl_pkey_get_private($privateKey);
		}
		if($publicKey){
			$this->publicKey = openssl_pkey_get_public($publicKey);
		}
	}
	
	public function crateRsaKey(){
		$config = array(
				"private_key_bits" => 1024,
				"private_key_type" => OPENSSL_KEYTYPE_RSA,
		);
		$res = openssl_pkey_new($config);
		if(!$res){
			return false;
		}
		$privKey = '';
		openssl_pkey_export($res, $privKey);//获取私钥
		$pubKey = openssl_pkey_get_details($res);
		$pubKey = $pubKey["key"];
		$re['pri'] = $privKey;
		$re['pub'] = $pubKey;
		return $re;		
	}
	
	/**
	 * 私钥加密
	 * @param 原始数据 $data
	 * @return 密文结果 string
	 */
	public function encryptByPrivateKey($data) {
		$len = strlen($data);
		$i=0;
		$result = '';
		while($len-$i>0){
			$encrypted = '';
			if($len-$i>117){
				openssl_private_encrypt(substr($data, $i,117),$encrypted,$this->privateKey,OPENSSL_PKCS1_PADDING);
			} else {
				openssl_private_encrypt(substr($data, $i,$len-$i),$encrypted,$this->privateKey,OPENSSL_PKCS1_PADDING);
			}
			$result .= $encrypted;
			$i += 117;
		}
		
		return base64_encode($result);
	}
	
	/**
	 * 私钥解密
	 * @param 密文数据 $data
	 * @return 原文数据结果 string
	 */
	public function decryptByPrivateKey($data){
		$data = base64_decode($data);
		$len = strlen($data);
		$i=0;
		$result = '';
		while($len-$i>0){
			$encrypted = '';
			if($len-$i>128){
				openssl_private_decrypt(substr($data, $i,128),$encrypted,$this->privateKey,OPENSSL_PKCS1_PADDING);
			} else {
				openssl_private_decrypt(substr($data, $i,$len-$i),$encrypted,$this->privateKey,OPENSSL_PKCS1_PADDING);
			}
			$result .= $encrypted;
			$i += 128;
		}
		
		return $result;
	}
	
	/**
	 * 私钥签名
	 * @param unknown $data
	 */
	public function signByPrivateKey($data){
		openssl_sign($data, $signature, $this->privateKey);
		$encrypted = base64_encode($signature);
		return $encrypted;
	}
	
	
	/**
	 * 公钥加密
	 * @param 原文数据 $data
	 * @return 加密结果 string
	 */
	public function encryptByPublicKey($data) {
		$len = strlen($data);
		$i=0;
		$result = '';
		while($len-$i>0){
			$encrypted = '';
			if($len-$i>117){
				openssl_public_encrypt(substr($data, $i,117),$encrypted,$this->publicKey,OPENSSL_PKCS1_PADDING);
			} else {
				openssl_public_encrypt(substr($data, $i,$len-$i),$encrypted,$this->publicKey,OPENSSL_PKCS1_PADDING);
			}
			$result .= $encrypted;
			$i += 117;
		}
		return base64_encode($result);
	}
	
	/**
	 * 公钥解密
	 * @param 密文数据 $data
	 * @return 原文结果 string
	 */
	public function decryptByPublicKey($data) {
		$data = base64_decode($data);
		$len = strlen($data);
		$i=0;
		$result = '';
		while($len-$i>0){
			$encrypted = '';
			if($len-$i>128){
				openssl_public_decrypt(substr($data, $i,128),$encrypted,$this->publicKey,OPENSSL_PKCS1_PADDING);
			} else {
				openssl_public_decrypt(substr($data, $i,$len-$i),$encrypted,$this->publicKey,OPENSSL_PKCS1_PADDING);
			}
			$result .= $encrypted;
			$i += 128;
		}
		
		return $result;
	}
	
	/**
	 * 公钥验签
	 * @param unknown $data
	 * @param unknown $sign
	 */
	public function verifyByPublicKey($data,$sign){
		$sign = base64_decode($sign);
		return openssl_verify($data, $sign, $this->publicKey);
	}
	
	/**
	 * 构析函数，用来释放公钥和私钥
	 */
	public function __destruct(){
		openssl_free_key($this->privateKey);
		openssl_free_key($this->publicKey);
	}
	
	public function test(){
		header("Content-type: text/html; charset=utf-8");
		$str = '我要加密这段文字1。我要加密这段文字2。我要加密这段文字3。我要加密这段文字4。我要加密这段文字5。我要加密这段文字6。我要加密这段文字7。我要加密这段文字8。我要加密这段文字9。我要加密这段文字10。我要加密这段文字11。我要加密这段文字12。';
		echo '原文:'.$str.'</br>';
		$crypt = $this->encryptByPrivateKey($str);
		echo '私钥加密密文:'.$crypt.'</br>';
		$now = $this->decryptByPublicKey($crypt);
		echo '公钥解密原文:'.$now.'</br>';
		echo '------------'.'</br>';

		echo '原文:'.$str.'</br>';
		$crypt = $this->encryptByPublicKey($str);
		echo '公钥加密密文:'.$crypt.'</br>';
		$now = $this->decryptByPrivateKey($crypt);
		echo '私钥解密原文:'.$now.'</br>';
		echo '------------'.'</br>';
		
		$str = '我要签名这段文字。我要签名这段文字。我要签名这段文字。我要签名这段文字。我要签名这段文字。我要签名这段文字。我要签名这段文字。我要签名这段文字。我要签名这段文字。我要签名这段文字。我要签名这段文字。我要签名这段文字。';
		echo '原文:'.$str.'</br>';
		$crypt = $this->signByPrivateKey($str);
		echo '签名密文:'.$crypt.'</br>';
		if($this->verifyByPublicKey($str,$crypt)){
			echo 'true';
		} else {
			echo 'false';
		}
		
	}
}