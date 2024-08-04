<?php

namespace xjryanse\sql\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\sql\service\SqlService;
use xjryanse\logic\DbOperate;
/**
 *
 */
class SqlTableService extends Base implements MainModelInterface {

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
    protected static $mainModelClass = '\\xjryanse\\sql\\model\\SqlTable';

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    foreach($lists as &$v){
                        // 1:系统原始表；2:查询sql;
                        $v['tableType']  = DbOperate::isTableExist($v['table_name']) 
                                ? 1 
                                : (SqlService::keyToId($v['table_name']) ? 2 : '');
                        
                        $v['sqlId'] = SqlService::keyToId($v['table_name']);
                    }
            
                    return $lists;
                },true);
    }
    
}
