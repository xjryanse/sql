<?php

namespace xjryanse\sql\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Debug;
/**
 *
 */
class SqlFieldService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\StaticModelTrait;
    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\sql\\model\\SqlField';
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
    /**
     * 获取搜索字段
     */
    public static function getSearchFieldsBySqlId($sqlId, $con = []) {
        $con[] = ['sql_id', '=', $sqlId];
        $con[] = ['status', '=', 1];
        $lists = self::staticConList($con);
//        Debug::dump('getSearchFieldsBySqlId');
//        Debug::dump($con);
        $searchFields = [];
        foreach ($lists as $v) {
            $fieldArr   = explode('.', $v['field_name']);
            $tmp        = array_pop($fieldArr);
            $fieldName  = $v['field_as'] ? $v['field_as'] : $tmp;
            $searchFields[$v['search_type']][] = $fieldName;
        }
        return $searchFields;
    }
    /**
     * 20240115:求和字段
     * @param type $sqlId
     * @return type
     */
    public static function sumFields($sqlId) {
        $con[] = ['sql_id', '=', $sqlId];
        $con[] = ['is_sum', '=', 1];
        $con[] = ['status', '=', 1];
        $lists = self::staticConList($con);

        $sumFields = [];
        foreach ($lists as $v) {
            $fieldArr   = explode('.', $v['field_name']);
            $tmp        = array_pop($fieldArr);
            $fieldName  = $v['field_as'] ? $v['field_as'] : $tmp;
            $sumFields[] = $fieldName;
        }
        $res =  array_unique($sumFields);
        return $res;
        // dump($res);
    }
    
    /**
     * 20240126:表单动态字段
     * @param type $sqlId
     * @return string
     */
    public static function fDynFields($sqlId) {
        $con[]  = ['sql_id', '=', $sqlId];
        $con[]  = ['status', '=', 1];
        $lists  = self::staticConList($con);
        $arr    = [];
        foreach ($lists as $v) {
            $fieldArr   = explode('.', $v['field_name']);
            $tmp        = array_pop($fieldArr);
            $fieldName  = $v['field_as'] ? $v['field_as'] : $tmp;

            $arr[] = ['id'=>$v['id'],'name'=>$fieldName,'label'=>$v['title'],'type'=>'text'];
        }
        
        return $arr;
    }
    
    /**
     * 20240317:多图字段
     */
    public static function multiImgFields($sqlId){
        $con    = [];
        $con[]  = ['field_type','=','multiimg'];
        $con[]  = ['sql_id','=',$sqlId];
        $lists = self::staticConList($con);
        return Arrays2d::uniqueColumn($lists, 'field_as');
    }

    /**
     * 20240317:单图字段
     */
    public static function uplimageFields($sqlId){
        $con    = [];
        $con[]  = ['field_type','=','uplimage'];
        $con[]  = ['sql_id','=',$sqlId];
        $lists = self::staticConList($con);
        return Arrays2d::uniqueColumn($lists, 'field_as');
    }

    
}
