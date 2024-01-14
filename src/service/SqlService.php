<?php

namespace xjryanse\sql\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Debug;
/**
 *
 */
class SqlService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\sql\\model\\Sql';

}
