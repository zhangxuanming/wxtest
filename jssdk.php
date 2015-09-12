<?php
class JSSDK {
  private $appId;
  private $appSecret;
	private $debugCurlAccessToken;
	private $debugCurlJSApiTicket;
	private $debugFileAccessToken;
	private $debugFileJSApiTicket;
	private $debugRequestUrl;

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
  }

  public function getSignPackage() {
	  $jsapiTicket = $this->getJsApiTicket();
       // 注意 URL 一定要动态获取，不能 hardcode.
	  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	  $this->debugRequestUrl = $url;
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

	  $this->log('call.log',[
		  'signature'=>$signature,
		  'time'=>time(),
	  ]);
	  return $signPackage;
  }

	public function getDebugOutPut(){
		$arr = [
			"curlAccessToken" => $this->debugCurlAccessToken,
			"curlJSApiTicket" => $this->debugCurlJSApiTicket,
			"fileAccessToken" => $this->debugFileAccessToken,
			"fileJSApiTicket" => $this->debugFileJSApiTicket,
			"currentRequestUrl" => $this->debugRequestUrl
		];
		return $arr;
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
	  $data = json_decode(file_get_contents("jsapi_ticket.json"));
	  if ($data->expire_time < time()) {
		  $accessToken = $this->getAccessToken();
          // 如果是企业号用以下 URL 获取 ticket
          // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
		  $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
		  $res = json_decode($this->httpGet($url));
		  $this->debugCurlJSApiTicket = $res;
		  $this->log('curlJSApi.log',[
			  'res'=>$res,
			  'time'=>time(),
		  ]);

		  $ticket = $res->ticket;
		  if ($ticket) {
			  $data->expire_time = time() + 7000;
			  $data->jsapi_ticket = $ticket;
			  $fp = fopen("jsapi_ticket.json", "w");
			  fwrite($fp, json_encode($data));
			  fclose($fp);
		  }
    } else {
        $ticket = $data->jsapi_ticket;
	    $this->debugFileJSApiTicket = $data;
    }

    return $ticket;
  }

  private function getAccessToken() {
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = json_decode(file_get_contents("access_token.json"));
    if ($data->expire_time < time()) {
      // 如果是企业号用以下URL获取access_token
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
      //https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
      $res = json_decode($this->httpGet($url));

	    $this->debugCurlAccessToken = $res;
	    $this->log('curlToken.log',[
		    'res'=>$res,
		    'time'=>time(),
	    ]);

      $access_token = $res->access_token;
      if ($access_token) {
        $data->expire_time = time() + 7000;
        $data->access_token = $access_token;
        $fp = fopen("access_token.json", "w");
        fwrite($fp, json_encode($data));
        fclose($fp);
      }
    } else {
      $access_token = $data->access_token;
	    $this->debugFileAccessToken = $data;
    }
    return $access_token;
  }

	//log到文件
	private function log($filename,$content){
		$f = file_put_contents($filename,json_encode($content)."\n",FILE_APPEND);
		return $f;
	}

  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);
    return $res;
  }
}

