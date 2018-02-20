<?php
namespace app\admin\controller;

use think\Db;

class Index
{


    private function isLogin(){
        return true;
    }

    //拉取数据库列表
    public function getDbList(){
        if (!$this->isLogin())return;

        $sql = "select * from information_schema.tables where table_schema='dbapi';";

        $res = Db::query($sql);

        $count = sizeof($res);
        $array = array(
            "code" => 0,
            'msg' => "",
            "count" => $count,
            "data" => $res
        );
        echo json_encode($array);

    }

    //删除数据库
    public function delDb(){
        if (!$this->isLogin())return;

        $sql = "DROP TABLE ".input("name");

        $res = Db::query($sql);

        echo 1;

    }

    //清空数据库
    public function qkDb(){
        if (!$this->isLogin())return;
        $sql = "TRUNCATE ".input("name");

        $res = Db::query($sql);

        echo 1;
    }

    //创建数据库
    public function createDb(){
        if (!$this->isLogin())return;

        $sql = "CREATE TABLE IF NOT EXISTS `".input("dbname")."` (";
        $pkey = '';
        for ($i=0;$i<input("length");$i++){
            $name = input("line_".$i."_name");
            $bz = input("line_".$i."_bz");
            $type = input("line_".$i."_type");
            $length = input("line_".$i."_length");
            if ($i!=0){
                $sql .= ",";
            }

            if ($type=="id"){
                $pkey = ",PRIMARY KEY (".$name.")";
                $sql .= "`".$name."` int NOT NULL AUTO_INCREMENT COMMENT '".$bz."'";
            }else{
                $sql .= "`".$name."` ".$type."(".$length.") COMMENT '".$bz."'";
            }
        }

        $sql .= $pkey;

        $sql .= ")ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=UTF8 COMMENT='".input("dbbz")."';";
        //echo $sql;
        $res = Db::query($sql);
        echo 1;
        //print_r($res);
    }


}
