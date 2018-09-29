<?php
namespace app\apiClass;

//图床API

class weather{
    public static function Get(){
        $city = input("city");
        if ($city=="") {
            return array("code"=>"-1");
        }

        $url = "http://wthrcdn.etouch.cn/weather_mini?city=".$city;


        $res = weather::curl_get($url,true);


        $res = str_replace("<![CDATA[<","",$res);
        $res = str_replace("]]>","",$res);

        $res = json_decode($res);
        if ($res->status!=1000){
            return array("code"=>"0");
        }else{
            return array(
                "code"=>"1",
                "now"=>array(
                    "date"=>$res->data->forecast[0]->date,
                    "fengli"=>$res->data->forecast[0]->fengli,
                    "fengxiang"=>$res->data->forecast[0]->fengxiang,
                    "type"=>$res->data->forecast[0]->type,
                    "wendu"=>$res->data->wendu,
                    "msg"=>$res->data->ganmao
                ),
                "5day"=>$res->data->forecast
            );
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


}