<?php
namespace xjryanse\sql\model;

/**
 * sql语句
 */
class SqlWhere extends Base
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