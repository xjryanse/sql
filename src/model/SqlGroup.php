<?php
namespace xjryanse\sql\model;

/**
 * sql聚合字段
 */
class SqlGroup extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'sql_id',
            'uni_name'  =>'sql',
            'uni_field' =>'id',
            'del_check' => true
        ],
    ];


}