<?php
namespace app\apiClass;

//图床API

class imageUpload{
    public static function Upload(){
        if (isset($_POST['img'])) {
            $img = $_POST['img'];
        }else if (isset($_FILES['file'])) {
            $fp = fopen($_FILES["file"]["tmp_name"],"r");
            $img = base64_encode(fread($fp,$_FILES["file"]["size"]));
        }else{
            return array("code"=>"-1","img_url"=>null);
        }



        $img = base64_decode($img);


        $data = base64_decode("LS0tLS0tV2ViS2l0Rm9ybUJvdW5kYXJ5R0xmR0IwSGdVTnRwVFQxaw0KQ29udGVudC1EaXNwb3NpdGlvbjogZm9ybS1kYXRhOyBuYW1lPSJwaWNfcGF0aCI7IGZpbGVuYW1lPSIxMS5wbmciDQpDb250ZW50LVR5cGU6IGltYWdlL3BuZw0KDQo=").$img.base64_decode("DQotLS0tLS1XZWJLaXRGb3JtQm91bmRhcnlHTGZHQjBIZ1VOdHBUVDFrLS0NCg==");

        $url = "http://pic.sogou.com/pic/upload_pic.jsp";


        $ch = curl_init();
        $headers=array(
            "Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryGLfGB0HgUNtpTT1k",
            "Content-Length: ".strlen($data)
        );

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (false) {
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1");
            curl_setopt($ch, CURLOPT_PROXYPORT, 8888);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        }

        $result=curl_exec($ch);
        curl_close($ch);
        return array("code"=>"0","img_url"=>$result);

    }
}