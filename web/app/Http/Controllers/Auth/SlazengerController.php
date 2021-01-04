<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;
use App\Models\Token;
use App\User;

class SlazengerController extends Controller
{
    //
//	private $appid = "wx9c40fbf6795ec301";
//	private $appsecret = "0361625a7e9b76c27e8c325be19fd69e";
	private $appid = "wx1bc16788d06859a8";
	private $appsecret = "c4d35bfd84f048c920e3f8f2819b2c08";

	public function valid() {

		define("TOKEN", "ljx");
		$wechatObj = new wechatCallbackapiTest();
		$wechatObj->valid();

	}

	public function message(Request $req) {

		$attr = $req->getContent();
		$attr = preg_replace('/> *</', "><", $attr);
		$attr = preg_replace('/ *!\[/', "![", $attr);
		$attr = preg_replace('/\] */', "]", $attr);
//		libxml_disable_entity_loader(true);
		$xml = simplexml_load_string($attr, 'SimpleXMLElement', LIBXML_NOCDATA);

		$openid = $xml->FromUserName;
		$me = $xml->ToUserName;
		$create_time = $xml->CreateTime;
		$m_type = $xml->MsgType;
		$event_type = isset($xml->Event) ? $xml->Event : "";
		$event_key = isset($xml->EventKey) ? $xml->EventKey : "";
		$content = isset($xml->Content) ? $xml->Content : "";
		$content = clear_content($content);

		$openid = trim($openid);
		$wxid = BKDRHash($openid);

		if (!in_array($event_type, array("", "subscribe", "unsubscribe", "CLICK"))){
			$ret_content = "操作错误";
			return return_content($openid, $me, $create_time, $ret_content);
		}

		if ($event_type == "subscribe") {
			$ret_content = "欢迎关注网球MAG";
			return return_content($openid, $me, $create_time, $ret_content);
		}

		if ($event_type == "unsubscribe") {
			$ret_content = "感谢关注网球MAG";
			return return_content($openid, $me, $create_time, $ret_content);
		}

		if (($m_type == "event" && $event_type == "CLICK") || strpos($content, "竞猜") !== false || strpos($content, "中奖") !== false) {

			if ($event_key == "begin_dc" || strpos($content, "竞猜") !== false) {

				$token = $this->get_token();

				$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$token."&openid=$openid&lang=zh_CN";
				$json_profile = json_decode(file_get_contents($url), true);
				if (!$json_profile) {
					$ret_content = "暂时不能提供服务，请重试";
					echo return_content($openid, $me, $create_time, $ret_content);
					exit;
				}

//				$ret_content = json_encode($json_profile);
//				return return_content($openid, $me, $create_time, $ret_content);
				$sex = ($json_profile["sex"] == 1) ? 1 : 2;
				$image = $json_profile["headimgurl"];
				$nickname = $json_profile["nickname"];

				$user = User::where(['uid' => $wxid, 'method' => Config::get('const.USERTYPE_WX_WANGQIU')])->first();

				if (!$user) {
					$user = new User;
					$user->uid = $wxid;
					$user->password = "";
					$user->method = Config::get('const.USERTYPE_WX_WANGQIU');
					$user->name = $user->method . "_" . $user->uid;
					$user->oriname = $nickname;
					$user->gender = $sex;
					$user->avatar = $image;
					$user->bigavatar = $image;
					$user->ip = getIP();
					$user->save();
				}

				$retid = $user->id;
				if (!$retid) {
					$ret_content = "暂时不能提供服务，请重试";
					echo return_content($openid, $me, $create_time, $ret_content);
					exit;
				}

				$crypt_str = urlencode(phpencrypt(join("\t", [$retid, $wxid, $nickname, Config::get('const.USERTYPE_WX_WANGQIU')])));
				$ret_content = "请点击下面的链接参与(点击链接后请务必长按页面并选择“在浏览器打开”，或者把链接复制到浏览器里打开)：
<a href=\"https://www.rank-tennis.com/login/wangqiu/callback?code=$crypt_str&redirect_uri=/zh/dc/UO/2019/MS\">美网男单</a> 
<a href=\"https://www.rank-tennis.com/login/wangqiu/callback?code=$crypt_str&redirect_uri=/zh/dc/UO/2019/WS\">美网女单</a>
";

				return return_content($openid, $me, $create_time, $ret_content);
			}
		}
	}

	private function get_token() {

		$one = Token::where('object', 'slazenger')->first();

		if (!$one) {
			$one = new Token;
			$one->object = 'slazenger';
			$token = json_decode(file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appid&secret=$this->appsecret"), true);
			$token = $token["access_token"];
			$one->token = $token;
			$one->save();
		} else {
			if (time(NULL) - strtotime($one->updated_at) >= 5400) {
				$token = json_decode(file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appid&secret=$this->appsecret"), true);
				$token = $token["access_token"];
				$one->token = $token;
				$one->save();
			}
		}
		return $one->token;
	}
}

class wechatCallbackapiTest
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    public function responseMsg()
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

      	//extract post data
		if (!empty($postStr)){
                libxml_disable_entity_loader(true);
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $time = time();
                $textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";             
				if(!empty( $keyword ))
                {
              		$msgType = "text";
                	$contentStr = "Welcome to wechat world!";
                	$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                	echo $resultStr;
                }else{
                	echo "Input something...";
                }

        }else {
        	echo "";
        	exit;
        }
    }
		
	private function checkSignature()
	{
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
        
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

