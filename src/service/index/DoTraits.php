<?php

namespace xjryanse\sql\service\index;

use xjryanse\logic\Arrays;
/**
 * 
 */
trait DoTraits{
    /**
     * 复制页面
     * @param type $param
     */
    public static function doCopySql($param){
        // 当前sqlkey
        $sqlKey        = Arrays::value($param, 'sqlKey');
        // 目标sqlkey
        $targetSqlKey  = Arrays::value($param, 'targetSqlKey');
        
        $sqlId         = self::keyToId($sqlKey);
        
        $res = self::getInstance($sqlId)->copySql($targetSqlKey);
        return $res;
    }
}
