<?php

namespace xjryanse\sql\service\field;

/**
 * 
 */
trait ListTraits{
    /*
     * page_id维度列表
     */
    public static function listBySqlId($sqlId){
        $con    = [];
        $con[]  = ['sql_id','in',$sqlId];
        return self::staticConList($con);
    }
}
