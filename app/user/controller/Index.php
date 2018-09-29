<?php
namespace app\user\controller;


use think\Db;
use think\Session;
use think\helper\Time;
class Index
{
    public function test(){
        //Route::bind('index/Blog');
        dump(Time::dayToNow(7));
        echo 1;
    }

    private function getKey($user,$apiid){
        $key = $this->encrypt($user."@".$apiid,"E","vone@#");
        return $key;
    }

    //拉取Api调用历史列表
    public function getApiHistoryList(){
        if (!Session::has("state") || Session::get("state")!=1)return;

        $key = input("key");
        if ($key==""){
            $array = array(
                "code" => -1,
                'msg' => "参数错误",
                "count" => 0,
                "data" => null
            );
            echo json_encode($array);
            return;
        }
        $dec = $this->encrypt($key,"D","vone@#");
        $tmp = explode("@",$dec);
        if (sizeof($tmp)!=2){
            $array = array(
                "code" => -1,
                'msg' => "参数错误",
                "count" => 0,
                "data" => null
            );
            echo json_encode($array);
            return;
        }

        $user = $tmp[0];
        $apiid = $tmp[1];


        $array = array();

        $res = Db::name("apihistory")
            ->where("apiid",$apiid)
            ->where("user",$user)
            ->limit((input("page")-1)*input("limit").','.input("limit"))
            ->order("id","desc")
            ->select();
        $array = array();
        foreach ($res as $re){
            $array[] = array("time"=>date("Y-m-d H:i:s",$re['time']),"ip"=>$re['ip']);
        }

        $count = Db::name("apihistory")->where("apiid",$apiid)
            ->where("user",$user)->count();
        $array = array(
            "code" => 0,
            'msg' => "",
            "count" => $count,
            "data" => $array
        );
        echo json_encode($array);
    }

    //拉取Api列表
    public function getApiList(){
        if (!Session::has("state") || Session::get("state")!=1)return;
        $user = Session::get("user");

        $array = array();

        $res = Db::name("apilist")->select();
        foreach ($res as $re){
            $tmp = Db::name("apikey")->where("user",$user)->where("apiid",$re['id'])->find();
            $arr = array();
            if ($tmp!=null){
                $arr['name']=$re['name'];
                $arr['key'] = $tmp['key'];
                $arr['today'] = $tmp['today'];

                if ($tmp['last']==""){
                    $arr['last']="从未调用";
                }else{
                    $arr['last'] = date("Y-m-d H:i:s",$tmp['last']);
                    list($start, $end) = Time::today();

                    $count = Db::name("apihistory")->where("user",$user)->where("apiid",$re['id'])->where("time>=".$start)->where("time<=".$end)->count();

                    Db::name("apikey")->where("user",$user)->where("apiid",$re['id'])->update(array("today"=>$count));
                    $arr['today'] = $count;
                }

                $arr['all'] = $tmp['all'];
                $arr['id'] = $re['id'];
            }else{
                $key = $this->getKey($user,$re['id']);
                $data = array("apiid"=>$re['id'],"user"=>$user,"key"=>$key);
                Db::name("apikey")->insert($data);

                $arr['name']=$re['name'];
                $arr['key'] = $key;
                $arr['last']="从未调用";
                $arr['today'] = 0;
                $arr['all'] = 0;
                $arr['id'] = $re['id'];
            }

            $array[] = $arr;
        }
        $count = sizeof($array);
        $array = array(
            "code" => 0,
            'msg' => "",
            "count" => $count,
            "data" => $array
        );
        echo json_encode($array);
    }

    //用户注册
    public function register(){
        $user = input("user");
        $pass = input("pass");
        $email = input("email");
        if ($user == "" || $pass == "" || $email == ""){
            echo json_encode(array("state"=>404,"msg"=>"请填写所有内容！"));
            return;
        }
        $res = Db::name("user")->where("user",$user)->find();
        if ($res !=null){
            echo json_encode(array("state"=>400,"msg"=>"用户名已存在，请更换用户名重试！"));
            return;
        }
        $data = array("user"=>$user,"pass"=>$pass,"email"=>$email);
        $res = Db::name("user")->insertGetId($data);
        if ($res>0){
            echo json_encode(array("state"=>200,"msg"=>"注册成功！"));
            Session::set("state",0);
            Session::set("user",$user);
            Session::set("id",$res);
            return;
        }else{
            echo json_encode(array("state"=>400,"msg"=>"注册失败，请稍后重试！"));
            return;
        }
    }

    //用户二次认证
    public function register2(){
        if(!Session::has("state")){
            echo json_encode(array("state"=>404,"msg"=>"未登录！"));
            return;
        }
        $user = Session::get("user");
        $res = Db::name("user")->where("user",$user)->find();

        if ($res['wx_openid']!=""){
            Session::set("state",1);
            echo json_encode(array("state"=>200,"msg"=>"已完成认证！"));
        }else{
            echo json_encode(array("state"=>300,"msg"=>"请按照步骤发送账号至公众号哦！"));
        }
    }

    //用户登录
    public function login(){
        $user = input("user");
        $pass = input("pass");
        if ($user == "" || $pass == ""){
            echo json_encode(array("state"=>404,"msg"=>"请填写所有内容！"));
            return;
        }
        $res = Db::name("user")->where("user",$user)->find();
        if ($res == null){
            echo json_encode(array("state"=>400,"msg"=>"用户不存在！"));
            return;
        }
        if ($res['pass']!=$pass){
            echo json_encode(array("state"=>400,"msg"=>"密码错误！"));
            return;
        }
        if ($res['wx_openid']!=""){
            $state = 1;
            echo json_encode(array("state"=>200,"msg"=>"登录成功！"));
        }else{
            $state = 0;
            echo json_encode(array("state"=>300,"msg"=>"需要微信认证！"));
        }
        Session::set("state",$state);
        Session::set("user",$user);
        Session::set("id",$res);

    }

    public function getWelcome(){
        if (!Session::has("state") || Session::get("state")!=1)return;
        $apisize = Db::name("apilist")->count();

        $today = Db::name("apikey")->where("user",Session::get("user"))->sum("today");
        $all = Db::name("apikey")->where("user",Session::get("user"))->sum("`all`");

        $out = array("user"=>Session::get("user"),"today"=>$today,"all"=>$all,"apisize"=>$apisize);
        echo json_encode($out);
    }

    public function get7day(){
        if (!Session::has("state") || Session::get("state")!=1)return;
        $time = Time::dayToNow(7)[0];
        $x = array();
        $y = array();

        for ($i = 0;$i <7;$i++){
            $start = $time+86400*$i;
            $end = $time+86400*($i+1);

            $y[] = Db::name("apihistory")->where("time>=".$start)->where("time<=".$end)->where("user",Session::get("user"))->count();
            $x[] = date("m-d",$start);
        }
        echo json_encode(array("x"=>$x,"y"=>$y));
    }

    //api调用比例
    public function getApiBl(){
        if (!Session::has("state") || Session::get("state")!=1)return;
        $apilist = Db::name("apikey")->where("user",Session::get("user"))->order("`all`","desc")->select();
        $array = array();
        for ($i=0;$i<sizeof($apilist);$i++){

            $name = Db::name("apilist")->where("id",$apilist[$i]['apiid'])->find();
            $name = $name["name"];
            $count = $apilist[$i]['all'];
            $array[] = array("name"=>$name,"value"=>$count);
        }
        echo json_encode($array);
    }

    //退出登录
    public function loginout(){
        Session::clear();

    }

    //'加密:'.encrypt($str, 'E', $key); '解密：'.encrypt($str, 'D', $key);
    private function encrypt($string,$operation,$key=''){
        $string = str_replace("#","/",$string);
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

}
