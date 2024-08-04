<?php

namespace xjryanse\sql\service;

use xjryanse\system\interfaces\MainModelInterface;
/**
 *
 */
class SqlGroupService extends Base implements MainModelInterface {

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
    protected static $mainModelClass = '\\xjryanse\\sql\\model\\SqlGroup';

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
    
}
