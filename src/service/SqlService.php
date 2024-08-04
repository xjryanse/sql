<?php

namespace xjryanse\sql\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\sql\service\SqlTableService;
use xjryanse\sql\service\SqlFieldService;
use xjryanse\sql\service\SqlGroupService;
use xjryanse\sql\service\SqlWhereService;
use xjryanse\sql\service\SqlHavingService;
use xjryanse\universal\service\UniversalItemTableService;
use xjryanse\universal\service\UniversalStructureService;
use xjryanse\universal\service\UniversalPageItemService;
use xjryanse\logic\DbOperate;
use xjryanse\logic\Debug;
use xjryanse\logic\ModelQueryCon;
use xjryanse\logic\Datetime;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use think\facade\Request;
use think\Db;
use Exception;
/**
 *
 */
class SqlService extends Base implements MainModelInterface {

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
    protected static $mainModelClass = '\\xjryanse\\sql\\model\\Sql';
    
    use \xjryanse\sql\service\index\DoTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    foreach($lists as &$v){
                        // 最终生成的sql结果
                        $v['finalSql'] = self::keyBaseSql($v['sql_key']);
                    }
            
                    return $lists;
                },true);
    }

    public static function keyBaseSql($key, $param = []){
        $id = self::keyToId($key);
        return self::getInstance($id)->baseSql($param);
    }

    public static function keySqlQueryData($key, $con = []){
        $id = self::keyToId($key);
        return self::getInstance($id)->sqlQueryData( $con );
    }

    public static function keyToId($key){
        $con[] = ['sql_key','=',$key];
        $info = self::staticConFind($con);
        return $info ? $info['id'] : '';
    }

    /**
     * 生成基础sql
     */
    public function baseSql($uparam = []){
        //Debug::dump($this->uuid);
        //Debug::dump($uparam);
        
        $con    = [['sql_id','=',$this->uuid]];
        $con[]  = ['status', '=', 1];
        $arr    = SqlTableService::staticConList($con,'','sort');

        foreach($arr as &$v){
            $tableName = $v['table_name'];
            // 嵌套子表
            if(self::keyToId($tableName)){
                $v['table_name'] = self::keyBaseSql($tableName,$uparam);
            } else {
                // 20240124关联分表
                $service    = DbOperate::getService($tableName);
                if($service && class_exists($service) && property_exists($service::mainModel(), 'isSeprate') && $service::mainModel()::$isSeprate){
                    $v['table_name'] = $service::mainModel()->sepConSql();
                }
            }
        }
        
        $fieldArr       = SqlFieldService::staticConList($con);
        // 聚合字段
        $groupFields    = SqlGroupService::staticConColumn('group_field',$con);
        // where 字段:一组条件数组，and连接

        // $whereNStr      = implode(' and ',$whereFields);
        $havingFields   = SqlHavingService::staticConColumn('having_con',$con);        

        $timeField      = $this->fYearmonthField();
        
        if($timeField){
            if(isset($uparam[$timeField]) && Arrays::value($uparam, $timeField)){
                $havingFields[]  = $timeField . '>= "' .$uparam[$timeField][0].'"';
                $havingFields[]  = $timeField . '<= "' . $uparam[$timeField][1].'"';
            }
            // 20240326:时间字段，写入
            $scopeTimeArr   = Datetime::paramScopeTime($uparam);
            if($scopeTimeArr){
                $havingFields[]  = $timeField . '>= "' .$scopeTimeArr[0].'"';
                $havingFields[]  = $timeField . '<= "' . $scopeTimeArr[1].'"';
            }
        }
        // 20240702;
        $whereDownCon = $this->whereDownFields($uparam);
        if($whereDownCon){
            foreach($whereDownCon as $ve){
                $havingFields[] = implode('',$ve);
            }
        }

        $info = $this->get();
        if(Arrays::value($info, 'sql_type') == 'union'){
            $whereList  = SqlWhereService::staticConList($con,'','sort');
            $groupList  = SqlGroupService::staticConList($con,'','sort');
            // 20240511优化
            $sql = self::unionBaseSql($this->uuid,$arr, $fieldArr, $whereList, $groupList );
        } else {
            $fields     = [];
            foreach($fieldArr as &$v){
                $tmp = $v['field_name'];
                if($v['field_as']){
                    $tmp .= ' as '.$v['field_as'];
                }
                $fields[] = $tmp;
            }

            $fieldsN        = array_unique(array_merge($groupFields, $fields));
            $whereFields    = SqlWhereService::staticConColumn('where_con',$con);
            $sql            = DbOperate::generateJoinSql($fieldsN,$arr,$groupFields,[],'',$whereFields, $havingFields);
        }

        return '('.$sql.')';
    }
    /**
     * 20240511:union的base表
     * @param type $sqlId
     * @param type $fieldList   w_sql_field 表
     * @param type $whereList   w_sql_where 表
     * @param type $groupList   w_sql_group 表
     */
    protected static function unionBaseSql($sqlId, $tableList, $fieldList, $whereList=[], $groupList = []){
        $tables = [];
        foreach($tableList as $ve){
            $tables[$ve['alias']] = $ve['table_name'];
        }
        $fields = [];
        foreach($fieldList as $vi){
            $fields[$vi['field_as']][$vi['alias']] = $vi['field_name'];
        }
        $whereArr = [];
        foreach($whereList as $w){
            // 字符串的条件(json 解析格式化)
            $whereArr[$w['alias']][] = json_decode($w['where_con']);
        }
        
        $groupArr = [];
        foreach($groupList as $g){
            // 字符串的条件(json 解析格式化)
            $groupArr[$g['alias']][] = $g['group_field'];
        }

        $sql = DbOperate::generateUnionSql($tables, $fields, $whereArr, $groupArr);
        return $sql;
    }
    
    /**
     * sql语句的查询结果
     */
    public function sqlQueryData($con = []){
        $sql = $this->baseSql();
        if($con){
            $arr   = Db::table($sql.' as mainTable')->where($con)->select();
        } else {
            $arr   = Db::query($sql);
        }
        
        return $arr;
    }
    
    /**
     * 分页查询数据
     * @param type $sqlKey
     * @param type $con
     * @param type $order
     * @param type $perPage
     * @param type $having
     * @param type $field
     * @param type $withSum
     * @param type $staticsGroupField
     * @param type $uparam  :源始入参参数
     * @return type
     */
    public static function sqlPaginateData($sqlKey, $con=[], $order='', $perPage=50, $having='', $field='', $withSum=0, $staticsGroupField = '', $uparam = []){
        // 20240123：不带动态数据
        $pgLists = self::sqlPaginateDataRaw($sqlKey, $con, $order, $perPage, $having, $field, $withSum, $staticsGroupField, $uparam);

        $pgLists['dynDataList'] = [];
        // table 表格
        $uTableId   = Request::param('uTableId');
        if($uTableId){
            $pgLists['dynDataList'] = UniversalItemTableService::getDynDataListByPageItemIdAndData($uTableId, $pgLists['data']) ;
        }
        // list 列表
        $uListId   = Request::param('uListId');
        if($uListId){
            $pgLists['dynDataList'] = UniversalStructureService::getDynDataListByPageItemIdAndData($uListId, $pgLists['data']) ;
        }
        $uPageId   = Request::param('uPageId');
        if($uPageId){
            $pgLists['dynDataList'] = UniversalPageItemService::getDynDataListByPageIdAndData($uPageId, $pgLists['data']);
        }
        // Debug::dump($pgLists);
        return $pgLists;
    }
    
    /**
     * 分页查询数据（不带动态数据）
     * @param type $sqlKey
     * @param type $con
     * @param type $order
     * @param type $perPage
     * @param type $having
     * @param type $field
     * @param type $withSum
     * @return type
     * @throws Exception
     */
    public static function sqlPaginateDataRaw($sqlKey, $con=[], $order='', $perPage=50, $having='', $field='', $withSum=0, $staticsGroupField = '', $uparam = []){
        $sql        = self::keyBaseSql($sqlKey, $uparam);
        $sqlId      = self::keyToId($sqlKey);
        if(!self::getInstance($sqlId)->fStatus()){
            throw new Exception('查询未启用'.$sqlKey);
        }
        $orderBy    = $order ? : self::getInstance($sqlId)->fOrderBy();
        $inst       = Db::table($sql.' as mainTable')->where($con);
        // Debug::dump(Db::table($sql.' as mainTable')->where($con)->buildSql());
        $sqlDebug   = Db::table($sql.' as mainTable')->where($con)->buildSql();
        
        if($orderBy){
            $inst->order($orderBy);
        }
        // Debug::dump($sql);
        $res = $inst->paginate($perPage);
        $pgLists = $res ? $res->toArray() : [];
        $pgLists['$sqlDebug'] = $sqlDebug;        
        // 20231221：处理一些按比例的数据
        // UniversalItemTableService::arrListSumAddCalc($uTableId, $pgLists);
        if($withSum){
            $sumFields  = SqlFieldService::sumFields($sqlId);
            if(!$sumFields){
                throw new Exception('数据表未配置合计字段，请联系您的软件服务商'.$sqlKey);
            }
            $fieldStr   = DbOperate::sumFieldStr($sumFields);
            $sum        = Db::table($sql.' as mainTable')->where($con)->field($fieldStr)->find();
            $pgLists['sumData'] = $sum;
            $pgLists['withSum'] = 1;
        }
        // 20240317：单图多图转换
        $imageFields    = SqlFieldService::uplimageFields($sqlId);
        if($imageFields){
            $pgLists['data'] = Arrays2d::picFieldCov($pgLists['data'], $imageFields);
        }
        $multiimgFields = SqlFieldService::multiImgFields($sqlId);
        if($multiimgFields){
            $pgLists['data'] = Arrays2d::multiPicFieldCov($pgLists['data'], $multiimgFields);
        }
        // 20240318
        if($staticsGroupField){
            $pgLists['statics'] = self::qStatics($sql.' as mainTable', $staticsGroupField, $con);
        }
        
        // $pgLists['$fSql'] = $sql;

        return $pgLists;
    }
    /**
     * 20240318
     */
    public static function qStatics( $tableSql, $staticsGroupField, $con = []){
        // Debug::dump($tableSql);
        foreach($con as $k=>&$v){
            if($v[0] == $staticsGroupField){
                unset($con[$k]);
            }
        }
        // 20240324:发现首个key不是0在TP中有bug
        $conN = array_values($con);
        
        return Db::table($tableSql)->where($conN)->group($staticsGroupField)->column('count(1) as num',$staticsGroupField);
    }
    
    /**
     * 20240125:组装where 查询条件
     */
    public function whereFields($uparam){
        $sqlId          = $this->uuid;
        $whereFields    = SqlFieldService::getSearchFieldsBySqlId($sqlId);
        $where          = ModelQueryCon::queryCon($uparam, $whereFields);
        // 20240115：时间切割的条件
        $scopeTimeArr   = Datetime::paramScopeTime($uparam);
        // Debug::dump($scopeTimeArr);
        $timeField      = $this->fYearmonthField();

        if($timeField && $scopeTimeArr){
            $where[]  = [$timeField,'>=',$scopeTimeArr[0]];
            $where[]  = [$timeField,'<=',$scopeTimeArr[1]];
        }
        return $where;
    }
    /**
     * 20240702：whereDownFields
     * @param type $uparam
     * @return type
     */
    public function whereDownFields($uparam){
        $sqlId          = $this->uuid;
        $con[]          = ['is_down_search','=',1];
        $whereFields    = SqlFieldService::getSearchFieldsBySqlId($sqlId, $con);
        if(!$whereFields){
            return [];
        }
        $where          = ModelQueryCon::queryCon($uparam, $whereFields);
        return $where;
    }
    /**
     * 20240403：复制sql
     * @param type $targetSqlKey
     * @return type
     * @throws Exception
     */
    public function copySql($targetSqlKey = ''){
        $infoRaw            = $this->get();
        $info               = is_array($infoRaw) ? $infoRaw : $infoRaw->toArray();
        //【1】保存页面
        $newSqlId           = self::mainModel()->newId();
        $info['id']         = $newSqlId;
        $info['sql_key']    = $targetSqlKey ? : $infoRaw['sql_key'].'Copy';
        // dump($info);
        $res                = self::saveRam($info);

        $arr    = [];
        $arr[]  = ['table'=>'sql_field' ,'attrKey'=>'sqlField'  ,'sqlField'=>'sql_id', 'slashFields' =>['field_name']];
        $arr[]  = ['table'=>'sql_group' ,'attrKey'=>'sqlGroup'  ,'sqlField'=>'sql_id', 'slashFields' =>['group_field']];
        $arr[]  = ['table'=>'sql_having','attrKey'=>'sqlHaving' ,'sqlField'=>'sql_id', 'slashFields' =>[]];
        $arr[]  = ['table'=>'sql_table' ,'attrKey'=>'sqlTable'  ,'sqlField'=>'sql_id', 'slashFields' =>[]];
        $arr[]  = ['table'=>'sql_where' ,'attrKey'=>'sqlWhere'  ,'sqlField'=>'sql_id', 'slashFields' =>['where_con']];

        foreach($arr as $v){
            //【2】字段
            $sList = $this->objAttrsList($v['attrKey']);
            foreach($sList as $item){
                $item['id']             = self::mainModel()->newId();
                $item[$v['sqlField']]   = $newSqlId;
                
                $tableName      = config('database.prefix').$v['table'];                
                $service        = DbOperate::getService($tableName);
                $slashFields    = Arrays::value($v, 'slashFields') ? : [];
                foreach($slashFields as $sK){
                    $item[$sK] = addslashes($item[$sK]);
                }
                
                $service::saveRam($item);
            }
        }

        return $res;
    }
    
    public function fYearmonthField() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    public function fOrderBy() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fStatus() {
        return $this->getFFieldValue(__FUNCTION__);
    }
}
