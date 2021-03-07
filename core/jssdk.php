<?php
class JSSDK {
  private $appId;
  private $appSecret;

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
  }

  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();

    // 注意 URL 一定要动态获取，不能 hardcode.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }

  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  private function getJsApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
	$d = $GLOBALS['core']->sqlSelect('mod_wxtoken', 'service', 'jsapi_ticket');
	if ($d) $GLOBALS['jsapi_ticket'] = $data = array('expire_time'=>strtotime($d[0]['updated']) + $d[0]['exipre'], 'jsapi_ticket'=>$d[0]['token']);

    if (!$data || $data['expire_time'] < time()) {
      $accessToken = $this->getAccessToken();
      // 如果是企业号用以下 URL 获取 ticket
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
      if ($ticket) {
        $data['expire_time'] = time() + 7000;
		$data['jsapi_ticket'] = $ticket;
        $GLOBALS['jsapi_ticket'] = $data;
		$GLOBALS['core']->sqlUpdate(array('token'=>$ticket, 'exipre'=>7000, 'updated'=>date('Y-m-d H:i:s'), 'used'=>time()), 'mod_wxtoken', 'service', 'jsapi_ticket');
      }
    }
	else $ticket = $data['jsapi_ticket'];

    return $ticket;
  }

  private function getAccessToken() {
require_once (PHPWS_SOURCE_DIR . "mod/weixin/class/supply.php");
require_once (PHPWS_SOURCE_DIR . "mod/weixin/class/weixin.class.php");
require_once (PHPWS_SOURCE_DIR . "mod/weixin/class/Weixin.php");
	if (!$GLOBALS['access_token']) {
		  $w = new PHPWS_Weixin();
		  $w->checktoken();
	}
	return $GLOBALS['access_token'];

    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = json_decode($this->get_php_file("access_token.php"));
    if ($data->expire_time < time()) {
      // 如果是企业号用以下URL获取access_token
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
      $res = json_decode($this->httpGet($url));
      $access_token = $res->access_token;
      if ($access_token) {
        $data->expire_time = time() + 7000;
        $data->access_token = $access_token;
//        $this->set_php_file("access_token.php", json_encode($data));
      }
    } else {
      $access_token = $data->access_token;
    }
    return $access_token;
  }

  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
    // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }
}

