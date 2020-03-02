<?php
class AESMcrypt {
    public $iv = null;
    public $key = null;
    public function __construct($key, $iv) {
        $this->key = $key;
        $this->iv = $iv;

    }
    public function encrypt($data) {
        return openssl_encrypt($data, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
    }

    public function decrypt($data) {
        return openssl_decrypt($data, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
    }
}