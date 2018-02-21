<?php
namespace app\admin\controller;

use think\Db;

class Index
{
    public function test(){
    }

    private function isLogin(){
        return true;
    }

    //拉取数据库列表
    public function getDbList(){
        if (!$this->isLogin())return;

        $sql = "select * from information_schema.tables where table_schema='".config("database")['database']."';";

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

    //获取数据库表列
    public function getDbColumn(){
        if (!$this->isLogin())return;

        $sql = "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT FROM information_schema.columns WHERE TABLE_SCHEMA = '".config("database")['database']."' and table_name = '".input("dbname")."'";

        $res = Db::query($sql);

        echo json_encode($res);

    }

    //获取数据库表数据
    public function getDbData(){
        if (!$this->isLogin())return;

        $res = Db::name(input("dbname"))
            ->limit((input("page")-1)*input("limit").','.input("limit"))
            ->select();

        $count = sizeof($res);
        $array = array(
            "code" => 0,
            'msg' => "",
            "count" => $count,
            "data" => $res
        );
        echo json_encode($array);

    }

}
