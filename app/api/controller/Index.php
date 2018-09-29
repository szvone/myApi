<?php
namespace app\api\controller;

use app\apiClass\myearth;
use app\apiClass\weather;
use think\Db;
use think\Request;
use app\apiClass\imageUpload;
class Index
{
    //api主入口
    public function index(){
    	// 指定允许其他域名访问    
		header("Access-Control-Allow-Origin: *");

        $key = input("key");
        if ($key==""){
            echo json_encode(array("state"=>400,"msg"=>"请传入授权Key"));
            return;
        }
//        echo $key;
//        $key = str_replace("#","/",$key);
//        echo $key;

        $dec = $this->encrypt($key,"D","vone@#");
        $tmp = explode("@",$dec);
        if (sizeof($tmp)!=2){
            echo json_encode(array("state"=>400,"msg"=>"授权Key错误"));
            return;
        }

        $user = $tmp[0];
        $apiid = $tmp[1];

        Db::name("apikey")->where("user",$user)->where("apiid",$apiid)->setInc("all");

//        $res = Db::name("apikey")->where("user",$user)->where("apiid",$apiid)->find();
//        if (date("d",$res['last']) != date("d",time())){
//            Db::name("apikey")->where("user",$user)->where("apiid",$apiid)->update(array("today"=>0));
//        }

        Db::name("apikey")->where("user",$user)->where("apiid",$apiid)->setInc("today");
        Db::name("apikey")->where("user",$user)->where("apiid",$apiid)->update(array("last"=>time()));
        $request = Request::instance();
        $ip = $request->ip();
        $data = array("user"=>$user,"apiid"=>$apiid,"time"=>time(),"ip"=>$ip);
        Db::name("apihistory")->insert($data);


        switch ($apiid){
            case 1:
                $apires = imageUpload::Upload();
                break;
            case 2:
                $apires = weather::get();
                break;
            case 3:
                $apires = myearth::get();
                break;
        }

        echo json_encode(array("state"=>200,"msg"=>"调用成功","api_res"=>$apires));
        return;

    }

    public function wxapi(){
        //微信token验证
        $echostr = input("echostr");
        if ($echostr!=null && false){
            echo $echostr;
            return;
        }


        //提取微信信息
        $ec = "";
        $file_in = file_get_contents("php://input"); //接收post数据
        $xml = simplexml_load_string($file_in);//转换post数据为simplexml对象
        if ($xml==null){
            echo "by:vone";
            return;
        }
        $xmlarray = array();
        foreach($xml->children() as $child){
            $xmlarray[$child->getName()]=$child;
            $ec = $ec . $child->getName()."->".$child."\n";
        }

        $msgtype = $xmlarray['MsgType'];
        $openid = $xmlarray['FromUserName'];
        $out = "";

        if ($msgtype=="event"){
            if ($xmlarray['Event']=="subscribe"){
                $out = "您好，欢迎关注vone软件！";
            }
        }else if($msgtype=="text"){
            $msg = $xmlarray['Content'];
            if (sizeof(explode("@",$msg))==2){
                $user = str_replace("@","",$msg);
                $out = $this->bdwx($user,$openid);
            }

            if (strstr($msg,"http")!==false){
                $out = $this->jx($msg);
            }

        }


        if($out == "")
            $out = "您好，激活账号请回复英文状态的 @加您的账号，如 @vone ！";

        if (false)
            $out.="\n\n调试信息：\n".$ec;


        echo "<xml>
            <ToUserName>".$xmlarray['FromUserName']."</ToUserName>
            <FromUserName>".$xmlarray['ToUserName']."</FromUserName>
            <CreateTime>".$xmlarray['CreateTime']."</CreateTime>
            <MsgType>"."text"."</MsgType>
            <Content><![CDATA[".$out."]]></Content>
            </xml>";

    }

    private function bdwx($user,$openid){
        $res = Db::name("user")->where("wx_openid",$openid)->find();
        if ($res!=null){
            return "您的微信已经绑定账号：".$res['user']."啦，一个微信只能绑定一个账号哦！";
        }
        $res = Db::name("user")->where("user",$user)->find();
        if ($res==null){
            return "您要绑定账号：".$user." 不存在，请检查账号是否正确！";
        }
        if ($res['wx_openid']!=""){
            return "您要绑定账号：".$user." 已经被其他微信绑定了，请直接登录！";
        }
        Db::name("user")->where("user",$user)->update(array("wx_openid"=>$openid));
        return "您已成功绑定账号：".$res['user']."，账号激活成功！";

    }


    //'加密:'.encrypt($str, 'E', $key); '解密：'.encrypt($str, 'D', $key);
    private function encrypt($string,$operation,$key=''){
        //$string = str_replace("#","/",$string);
        $key=md5($key);
        $key_length=strlen($key);
        $string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string;
        $string_length=strlen($string);
        $rndkey=$box=array();
        $result='';
        for($i=0;$i<=255;$i++){
            $rndkey[$i]=ord($key[$i%$key_length]);
            $box[$i]=$i;
        }
        for($j=$i=0;$i<256;$i++){
            $j=($j+$box[$i]+$rndkey[$i])%256;
            $tmp=$box[$i];
            $box[$i]=$box[$j];
            $box[$j]=$tmp;
        }
        for($a=$j=$i=0;$i<$string_length;$i++){
            $a=($a+1)%256;
            $j=($j+$box[$a])%256;
            $tmp=$box[$a];
            $box[$a]=$box[$j];
            $box[$j]=$tmp;
            $result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
        }
        if($operation=='D'){
            if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8)){
                return substr($result,8);
            }else{
                return'';
            }
        }else{
            $str = str_replace('=','',base64_encode($result));
            //$str = str_replace("/","#",$str);
            return $str;
        }
    }

    public static function curl_get($url, $gzip=false){
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        if($gzip) curl_setopt($curl, CURLOPT_ENCODING, "gzip"); // 关键在这里
        $content = curl_exec($curl);
        curl_close($curl);
        return $content;
    }
    /*
    取文本中间
     */
    function getSubstr($str, $leftStr, $rightStr){
        $left = strpos($str, $leftStr);
        //echo '左边:'.$left;
        if ($left=="") {
            return "";
        }
        $right = strpos($str, $rightStr,$left);
        //echo '<br>右边:'.$right;
        if($left < 0 or $right < $left) return '';
        //return substr($str, $left + strlen($leftStr), $right-$left-strlen($leftStr));
        return substr($str, $left + strlen($leftStr), $right-$left-strlen($leftStr));
    }
}
