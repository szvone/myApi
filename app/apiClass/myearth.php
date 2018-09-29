<?php
namespace app\apiClass;

//我的地球API

class myearth{
    public static function Get(){


        $url = "http://himawari8-dl.nict.go.jp/himawari8/img/D531106/latest.json?uid=";


        $res = myearth::curl_get($url,false);


        $data = json_decode($res);
        $date = str_replace(" ","",$data->date);
        $date = str_replace("-","",$date);
        $date = str_replace(":","",$date);
        $url = "http://himawari8-dl.nict.go.jp/himawari8/img/D531106/thumbnail/550/".substr($date,0,4)."/".substr($date,4,2)."/".substr($date,6,2)."/".substr($date,8,6)."_0_0.png";
        if (sizeof($data)!=14){
            return array("code"=>"-1");
        }else{
            return array(
                "code"=>"1",
                "img"=>$url
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