<?php


namespace basar911\phpUtil;


use Closure;

class Arr
{
    /**
     * 计算数组最大维数
     * @param array $array
     * @param int $level
     * @return int
     */
    public static function max_level($array, $level = 1){
        if (!is_array($array)) return $level;

        $next_l = $level + 1;

        foreach ($array as $item) {
            if (is_array($item)) {
                $level = max(self::max_level($item, $next_l), $level);
            }
        }

        return $level;
    }

    /**
     * 判断是否是一维数组
     * @param array $array
     * @param array $ignore $array中包含有$ignore里的某一值时，跳过一维检测
     * @return bool
     */
    public static function is_simple($array, $ignore = [])
    {
        if (!is_array($array)) return false;

        foreach ($array as $item) {
            if (!empty($ignore) && in_array($item, $ignore)) continue;

            if (is_array($item)) return false;
        }

        return true;
    }

    /**
     * 二维数组按指定字段找出最大值子项
     * @param $array
     * @param string $count_field
     * @param int $init_min
     * @return array
     */
    public static function find_max($array, string $count_field = '', $init_min = 0){
        $res = [];

        array_walk($array, function ($item) use (&$res, $count_field, $init_min) {
            $cur = $res[$count_field] ?? $init_min;

            if(bccomp($item[$count_field], $cur, 2) !== -1) $res = $item;
        });

        return $res;
    }

    /**
     * 分块处理
     * @param array $array
     * @param int $num
     * @param Closure|null $func
     * @param bool $preserve_keys 分块后是否保留原有键名，否则每个分块重新从0索引
     * @return void
     */
    public static function chunk(array $array, int $num, Closure $func = null, bool $preserve_keys = false)
    {
        $chunks = array_chunk($array, $num, $preserve_keys);

        foreach ($chunks as $chunk){
            $func($chunk);
        }
    }

    /**
     * 递归提取多维数组中的所有值
     * @param $array
     * @return array
     */
    public static function flatten_value($array)
    {
        $result = [];

        array_walk_recursive($array, function ($item) use (&$result) {
            $result[] = $item;
        });

        return $result;
    }

    /**
     * 递归提取多维数组中的一维数组
     * @param array $array
     * @param array $ignore $array中包含有$ignore里的某一值时，跳过一维检测
     * @return array
     */
    public static function flatten_simple($array, $ignore = [])
    {
        if (!is_array($array)) return [];

        $result = [];

        foreach ($array as $item) {

            if (self::is_simple($item, $ignore)) {
                $result[] = $item;
                continue;
            }

            $result = array_merge($result, self::flatten_simple($item, $ignore));
        }

        return $result;
    }

    /**
     * 随机选取数组中若干元素
     * @param array $array
     * @param int $num
     * @return array|mixed|null
     */
    public static function select_rand(array $array, int $num = 1)
    {
        if(empty($array)) return null;

        $keys = array_rand($array, $num);

        if(is_array($keys)){
            return array_map(function ($key) use ($array) {
                return $array[$key];
            }, $keys);
        }else{
            return $array[$keys];
        }
    }

    /**
     * 数组键名由驼峰转小写
     * User: root
     * DateTime: 2021/9/1 11:17
     * @param array $array
     * @param int $recursive 是否递归替换  1|0
     * @return array
     */
    public static function key_unstudly($array, $recursive = 1)
    {
        if (empty($array)) {
            return [];
        }

        $result = [];

        foreach ($array as $key => $val) {
            $key = Str::parse_underline($key);

            if ($recursive && is_array($val)) {
                $val = self::key_unstudly($val);
            }

            $result[$key] = $val;
        }

        return $result;
    }

    /**
     * 数组键名由小写转驼峰
     * User: root
     * DateTime: 2021/9/1 11:17
     * @param array $array
     * @param int $recursive 是否递归替换  1|0
     * @return array
     */
    public static function key_studly($array, $recursive = 1)
    {
        if (empty($array)) {
            return [];
        }

        foreach ($array as $key => $val) {
            unset($array[$key]);
            $key = Str::parse_camel($key);

            if ($recursive && is_array($val)) {
                $val = self::key_studly($val);
            }

            $array[$key] = $val;
        }

        return $array;
    }

    /**
     * 计算多个集合的笛卡尔积
     * @param array $array 二维集合数组
     * @param string $type 转换格式 string:字符串    array:数组
     * @param string $separated 字符串分隔符，仅在$type为string时有效
     * @return array
     */
    public static function cross_join($array, $type = 'string', $separated = '')
    {
        $result = array_shift($array);
        $prev_arr = $next_arr = [];

        while ($next_arr = array_shift($array)) {
            $prev_arr = $result;
            $result = [];

            foreach ($prev_arr as $prev) {
                foreach ($next_arr as $next) {
                    if ($type == 'string') {
                        $result[] = $prev . $separated . $next;
                    }

                    if ($type == 'array') {
                        if (!is_array($prev)) {
                            $prev = [$prev];
                        }

                        if (!is_array($next)) {
                            $next = [$next];
                        }

                        $result[] = array_merge_recursive($prev, $next);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 使用array_reduce计算多个集合的笛卡尔积
     * @param array $array 二维集合数组
     * @param string $type 转换格式 string:字符串    array:数组
     * @param string $separated 字符串分隔符，仅在$type为string时有效
     * @return array
     */
    public static function cross_reduce($array, $type = 'string', $separated = ''){
        return array_reduce($array, function($prev_arr, $next_arr) use($type, $separated){
            if(empty($prev_arr) || empty($next_arr)) return $next_arr;

            $result = [];

            foreach ($prev_arr as $prev){
                foreach ($next_arr as $next){
                    if ($type == 'string') {
                        $result[] = $prev . $separated . $next;
                    }

                    if ($type == 'array') {
                        if (!is_array($prev)) {
                            $prev = [$prev];
                        }

                        if (!is_array($next)) {
                            $next = [$next];
                        }

                        $result[] = array_merge_recursive($prev, $next);
                    }
                }
            }

            return $result;
        }, []);
    }

    /**
     * tree无限极分类
     * User: root
     * DateTime: 2022/1/27 14:14
     * @param $data
     * @param string $id 父级id名称
     * @param string $pid 子级中指向父级id的名称
     * @param string $child_name 子类集合数组的名称
     * @return array
     */
    public static function generate_tree($data, $id = 'id', $pid = 'pid', $child_name = 'children'): array
    {
        $items = [];

        foreach ($data as $value){
            $items[$value[$id]] = array_merge($value);
        }

        $tree = [];

        foreach ($items as $k => $item) {
            if (isset($items[$item[$pid]])) {
                $items[$item[$pid]][$child_name][] = &$items[$k];
            } else {
                $tree[] = &$items[$k];
            }
        }

        return $tree;
    }

    /**
     * tree无限极分类
     * User: root
     * DateTime: 2022/1/27 14:14
     * @param $data
     * @param string $id 父级id名称
     * @param string $pid 子级中指向父级id的名称
     * @param string $child_name 子类集合数组的名称
     * @return array
     */
    public static function generate_tree_map($data, $id = 'id', $pid = 'pid', $child_name = 'children'): array
    {
        $items = [];

        foreach ($data as $value){
            $items[$value[$id]] = array_merge($value);
        }

        $tree = [];

        foreach ($items as $k => $item) {
            if (isset($items[$item[$pid]])) {
                $items[$item[$pid]][$child_name][$k] = &$items[$k];
            } else {
                $tree[$k] = &$items[$k];
            }
        }

        return $tree;
    }

    /**
     * 确定tree结构内所有数据的层级
     * @param array $data
     * @param $id_name
     * @param $p_name
     * @param $l_name
     * @param $pid
     * @param $level
     * @return void
     */
    public static function add_tree_level(array &$data, $id_name = 'id', $p_name = 'pid', $l_name = 'level', $pid = 0, $level = 0) {
        $level++; // 增加层级
        $childs = []; // 存储子节点

        foreach ($data as &$item) {
            if ($item[$p_name] == $pid) {
                $item[$l_name] = $level; // 设置当前层级
                $childs[] = &$item; // 将子节点添加到数组中
                unset($item); // 解除引用，防止内存泄露
            }
        }
        unset($item); // 解除最后的引用

        // 递归处理子节点
        foreach ($childs as &$child) {
            self::add_tree_level($data, $id_name, $p_name, $l_name, $child[$id_name], $level);
        }
        unset($child); // 解除最后的引用
    }

    /**
     * 不去重的array_flip(键值反转)
     * @param $array
     * @return array
     */
    public static function flip_multi($array): array
    {
        $result = [];

        array_walk($array, function ($item, $key) use(&$result) {
            $result[$item][] = $key;
        });

        return $result;
    }

    /**
     * 根据cat_id获取其顶层父类
     * @param array $tree_map  树形结构map [id1 => 数据1, id2 => 数据2...]
     * @param $cat_id
     * @param $id
     * @param $pid
     * @return mixed
     */
    public static function find_top_parent($tree_map, $cat_id, $id = 'id', $pid = 'pid') {
        $parent = $tree_map[$tree_map[$cat_id][$pid]] ?? [];

        return empty($parent) ? $tree_map[$cat_id] : self::find_top_parent($tree_map, $parent[$id], $id, $pid);
    }

    /**
     * 根据keys获取对应的值(不保留键)
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function get_by_keys(array $array, array $keys): array
    {
        return array_map(function ($key) use($array){
            return $array[$key] ?? false;
        }, $keys);
    }


    /**
     * 查找指定元素最后出现的位置
     * @param array $array
     * @param int|string $item
     * @return int
     */
    public static function find_last($array, $item): int
    {
        $l = count($array);

        for($i = $l - 1; $i >= 0; $i--){
            if($array[$i] === $item){
                return $i;
            }
        }

        return -1;
    }

    /**
     * 链式调用多个函数（内置函数、自定义函数、闭包、类中的公共方法）并返回结果
     * @param $init_param
     * [
     *   ['explode', [','], 2], // 调用的函数需要额外参数时, [’函数名‘, [...额外参数], 上一个函数结果在此函数中入参的序号(从1开始)]
     *   'array_unique',  // 不需要额外参数，则只传函数名
     *   ['implode', ['-'], 2]
     * ]
     * @param $func_chain
     * @return mixed
     * @example
    class Str{
    public function action_1($str, $ext = 'xxx'){
    return ucfirst($str) . '.' . $ext;
    }

    public static function action_2($str, $ext = 'xxx'){
    return str_replace('f', '#f#', $str) . '.' . $ext;
    }

    public static function action_3($str){
    return str_replace('f', '#f#', $str) . '.end';
    }
    }

    function double($str){
    return $str . $str;
    }

    $str = 'aagsegggsssddefghthjv';

    $func = function($before, $str){
    return $before . $str;
    };

    $func2 = function($str){
    return 'func' . $str;
    };

    $res = call_func_chain($str, [
    'str_split',
    'array_flip',
    'array_flip',  // 不需要额外参数，则只传函数名
    ['implode', [''], 2],  // 调用的函数需要额外参数时, [’函数名‘, [...额外参数], 上一个函数结果在此函数中入参的序号(从1开始)]
    'double',
    [$func, ['==='], 2],
    $func2,
    [[new Str(), 'action_1'], ['txt'], 1],  // 调用类中的动态方法
    [[Str::class, 'action_2'], ['png'], 1],  // 调用类中的静态方法
    [Str::class, 'action_3']  // 类中的方法不需要额外参数
    ]);
    var_export($res);
     */
    function call_func_chain($init_param, $func_chain = [])
    {
        $result = $init_param;
        $func_name = '';
        $params = [];

        while($func = array_shift($func_chain)){

            if(is_array($func)){
                if(count($func) == 3){
                    list($func_name, $params, $init_index) = $func;
                    array_splice($params, $init_index - 1, 0, [$result]);
                }

                if(count($func) == 2){
                    $func_name = $func;
                    $params = [$result];
                }
            }

            if(is_string($func) || is_callable($func)){
                $func_name = $func;
                $params = [$result];
            }

            $result = call_user_func_array($func_name, $params);
        }

        return $result;
    }

    /**
     * 二维数组（或对象集合）多对一关联(垂直分表)
     * User: root
     * DateTime: 2021/9/3 18:00
     * @param array $multi_array  二维数组或对象集合
     * @param string $multi_join   multi_array内的关联字段
     * @param array $one_array    被关联的二维数组或对象
     * @param string $one_join     one_array内的关联字段
     * @param Closure|null $extra_func  额外的处理闭包
     * @return array
     */
    public static function multi_join_one($multi_array, $multi_join, $one_array, $one_join, Closure $extra_func = null)
    {
        $one_array = array_column($one_array, null, $one_join);

        array_walk($multi_array, function (&$item) use ($multi_join, $one_array, $extra_func) {
            $key = $item[$multi_join];
//            $item = isset($one_array[$key]) ? array_merge($one_array[$key], $item) : $item;

            if(isset($one_array[$key])){  // 兼容$item为对象的情况
                foreach($one_array[$key] as $k => $v){
                    $item[$k] = $v;
                }
            }

            is_callable($extra_func) && ($item = $extra_func($item));
        });

        unset($one_array);

        return $multi_array;
    }

    /**
     * 二维数组（或对象集合）一对多关联
     * User: root
     * DateTime: 2021/9/3 17:59
     * @param array $one_array  二维数组或对象集合
     * @param string $one_join   one_array内的关联字段
     * @param array $multi_array  被关联的二维数组或对象
     * @param string $multi_join  multi_array内的关联字段
     * @param string $list_key  关联后的子集合名称
     * @param Closure|null $extra_func  额外的处理闭包
     * @return array
     */
    public static function one_join_multi($one_array, $one_join, $multi_array, $multi_join, string $list_key = 'list', Closure $extra_func = null)
    {
        $multi_array = self::group_by($multi_array, $multi_join);

        array_walk($one_array, function (&$item) use ($one_join, $multi_array, $list_key, $extra_func) {
            $key = $item[$one_join];
            $item[$list_key] = $multi_array[$key] ?? [];
            is_callable($extra_func) && ($item = $extra_func($item));
        });

        unset($multi_array);

        return $one_array;
    }

    /**
     * find_in_set类多对一关联
     * @param array $find_array 主表list数据
     * @param string $find_join 主表关联字段，特殊符号间隔的多个从表关联值
     * @param array $set_array 从表数据
     * @param string $set_join 从表关联字段
     * @param string $join_key 关联后的从表子集合名称
     * @param Closure|null $extra_func
     * @param string[] $separate $separate[0]-$find_join的间隔符号  $separate[1]-$find_field连接的间隔符号,仅在$find_field不为空时有效
     * @param string $find_field 关联后连接的从表字段
     * @return mixed
     */
    public static function find_inset_join(array $find_array, string $find_join, array $set_array, string $set_join, string $find_field = '', \Closure $extra_func = null, array $separate = [',', ';'], string $join_key = 'find_field_content')
    {
        $set_array = array_column($set_array, null, $set_join);

        array_walk($find_array, function (&$item) use ($find_join, $set_array, $find_field, $extra_func, $separate, $join_key) {
            if(empty($item[$find_join])){
                is_callable($extra_func) && ($item = $extra_func($item));
                return;
            }

            $find_values = explode($separate[0], $item[$find_join]);
            $set_values = [];

            foreach($find_values as $mv){
                if(!empty($mv) && isset($set_array[$mv])){
                    $set_values[] = !empty($find_field) ? ($set_array[$mv][$find_field] ?? '') : $set_array[$mv];
                }
            }

            $item[$join_key] = !empty($find_field) ? implode($separate[1], $set_values) : $set_values;

            is_callable($extra_func) && ($item = $extra_func($item));
        });

        unset($set_array);

        return $find_array;
    }

    /**
     * 多字段一对一关联
     * @param array $list
     * @param array $list_fields
     * @param array $join_list
     * @param array $join_fields
     * @param Closure|null $func
     * @return array
     */
    public static function join_one_by_multi_field(array &$list, array $list_fields, array $join_list, array $join_fields, \Closure $func = null)
    {
        $map = self::multi_key_format($join_list, $join_fields);

        array_walk($list, function (&$item) use ($map, $list_fields, $func) {
            $values = self::get_by_keys($item, $list_fields);
            $find   = self::multi_key_find($map, $values);

            if (!is_null($find)) {
                foreach ($find as $k => $v) {
                    $item[$k] = $v;
                }
            }

            is_callable($func) && ($item = $func($item));
        });

        return $list;
    }

    /**
     * 多字段一对多关联
     * @param array $list
     * @param array $list_fields
     * @param array $join_list
     * @param array $join_fields
     * @param string $list_name
     * @param Closure|null $func
     * @return array
     */
    public static function join_multi_by_multi_field(array &$list, array $list_fields, array $join_list, array $join_fields, string $list_name = 'list', \Closure $func = null)
    {
        $map = self::group_by($join_list, ...$join_fields);

        array_walk($list, function (&$item) use ($map, $list_fields, $list_name, $func) {
            $values = self::get_by_keys($item, $list_fields);
            $find   = self::multi_key_find($map, $values);
            $item[$list_name] = is_null($find) ? [] : $find;
            is_callable($func) && ($item = $func($item));
        });

        return $list;
    }

    /**
     * 提取类型是符号分隔的字段的所有值
     * @param $array
     * @param string $field
     * @param string $separate
     * @return false|string[]
     */
    public static function array_inset_column($array, string $field, string $separate = ','){
        return array_unique(explode($separate, implode($separate, array_filter(array_column($array, $field)))));
    }

    /**
     * 二维数组按键值分组
     * User: root
     * DateTime: 2021/9/3 17:59
     * @param $arr
     * @param $key
     * @return array
     * @example $data = [
     * [
     * 'id' => 1,
     * 'city' => '成都',
     * 'name' => '张三',
     * 'group_id' => 1
     * ],
     * [
     * 'id' => 2,
     * 'city' => '成都',
     * 'name' => '李四',
     * 'group_id' => 1
     * ],
     * [
     * 'id' => 1,
     * 'city' => '成都',
     * 'name' => '孙五',
     * 'group_id' => 2
     * ],
     * [
     * 'id' => 2,
     * 'city' => '成都',
     * 'name' => '王八',
     * 'group_id' => 2
     * ],
     * [
     * 'id' => 3,
     * 'city' => '重庆',
     * 'name' => '大明',
     * 'group_id' => 1
     * ],
     * [
     * 'id' => 4,
     * 'city' => '重庆',
     * 'name' => '小红',
     * 'group_id' => 1
     * ],
     * [
     * 'id' => 5,
     * 'city' => '北京',
     * 'name' => '小强',
     * 'group_id' => 1
     * ],
     * [
     * 'id' => 6,
     * 'city' => '北京',
     * 'name' => '赵四',
     * 'group_id' => 1
     * ],
     * [
     * 'id' => 7,
     * 'city' => '武汉',
     * 'name' => '龙七',
     * 'group_id' => 1
     * ],
     * [
     * 'id' => 6,
     * 'city' => '天津',
     * 'name' => '钱孙',
     * 'group_id' => 1
     * ],
     * ];
     *
     * $data = group_by($data, 'city', 'group_id');
     */
    public static function group_by()
    {
        $grouped = [];
        $args = func_get_args();
        list($arr, $key,) = $args;

        foreach ($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }

        // Recursively build a nested grouping if more parameters are supplied
        // Each grouped array value is grouped according to the next sequential key
        if (func_num_args() > 2) {
            $key2 = array_slice($args, 2);

            foreach ($grouped as $key => $value) {
                $parms = array_merge([$value], $key2);
                $grouped[$key] = self::group_by(...$parms);
            }
        }

        return $grouped;
    }

    /**
     * 二维数组按指定字段排序
     * @param $arrays
     * @param $sort_key
     * @param int $sort_order
     * @param int $sort_type
     * @return array
     */
    public static function sort_by_field($array, $sort_key, $sort_order = SORT_ASC, $sort_type = SORT_NUMERIC)
    {
        $key_arrays = array_column($array, $sort_key);
        array_multisort($key_arrays, $sort_order, $sort_type, $array);
        return $array;
    }

    /**
     * 二维数组指定多字段排序
     * @return mixed|null
     * @example $data[] = array('volume' => 67, 'edition' => 2);
     * $data[] = array('volume' => 86, 'edition' => 1);
     * $data[] = array('volume' => 85, 'edition' => 6);
     * $data[] = array('volume' => 98, 'edition' => 2);
     * $data[] = array('volume' => 86, 'edition' => 6);
     * $data[] = array('volume' => 67, 'edition' => 7);
     * // Pass the array, followed by the column names and sort flags
     * $sorted = array_order_by($data, 'volume', SORT_DESC, 'edition', SORT_ASC);
     * print_r($sorted)
     */
    public static function order_by()
    {
        $args = func_get_args();

        if (empty($args)) {
            return null;

        }

        $arr  = array_shift($args);
        $temp = [];

        foreach ($args as $key => $field) {
            if (is_string($field)) {
                $temp       = array_column($arr, $field);
                $args[$key] = $temp;
            }

        }

        $args[] = &$arr;//引用值
        array_multisort(...$args);
        return array_pop($args);
    }

    /**
     * 将url参数解析到数组,注意解析后的参数已经urldecode
     * @param string $url
     * @return array
     */
    public static function get_url_query(string $url)
    {
        $query_str = ltrim(strstr($url, '?'), '?');

        // 解析查询字符串到数组
        parse_str($query_str, $query_params);

        return $query_params;
    }

    /**
     * 按键名过滤数组
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function key_filter(array $array, array $keys): array
    {
        $res = [];

        foreach ($keys as $key){
            if(isset($array[$key])) $res[$key] = $array[$key];
        }

        return $res;
    }

    /**
     * 删除数组中指定键
     * @param array $array
     * @param array $keys
     * @param string $type
     * @return array
     */
    public static function key_unset(array $array, array $keys, string $type = 'two')
    {
        if($type == 'two'){  // 二维数组
            array_walk($array, function (&$item) use($keys) {
                foreach ($keys as $key){
                    if(isset($item[$key])) unset($item[$key]);
                }
            });
        }

        if($type == 'one'){  // 一维数组
            foreach ($keys as $key){
                if(isset($array[$key])) unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * cookie转化为请求header
     * @param array $cookie_data 二维数组 [ 域名1 => [key1 => value1, ... ], ... ]
     * @return array
     */
    public static function cookie_to_header(array $cookie_data)
    {
        $cookie_str = 'Cookie: ';

        array_walk($cookie_data, function($cookie, $domain) use(&$cookie_str) {
            $cookie_str .= http_build_query($cookie);
            $cookie_str = str_replace('&', '; ', $cookie_str);

            if($domain != '/'){
                $cookie_str .= "; domain=$domain" . PHP_EOL;
            }else{
                $cookie_str .= PHP_EOL;
            }

        });

        $header[] = rtrim($cookie_str, PHP_EOL);

        return $header;
    }

    /**
     * 输出json格式
     * User: root
     * DateTime: 2021/9/6 13:45
     * @param array $data
     */
    public static function j($data)
    {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * yield模式循环取读大数组
     * @param array $array
     * @return \Generator
     */
    public static function yield_read(array $array)
    {
        foreach ($array as $key => $value) {
            yield [$key, $value];
        }
    }

    /**
     * 转化数组内的null
     * @param $array
     * @param $default
     * @return array
     */
    public static function format_null($array, $default = '')
    {
        foreach ($array as $key => $item)
        {
            if(is_array($item)) $array[$key] = self::format_null($item, $default);

            if(is_null($item)) $array[$key] = $default;
        }

        return $array;
    }

    /**
     * 删除数组内的空值
     * @param $array
     * @param $default
     * @return array
     */
    public static function del_empty($array, $default = '')
    {
        foreach ($array as $key => $item)
        {
            if(is_array($item)) $array[$key] = self::del_empty($item, $default);

            if($item === $default) unset($array[$key]);
        }

        return $array;
    }

    /**
     * 比较两个一维数组的值是否相同
     * @param array $arr1
     * @param array $arr2
     * @return bool
     */
    public static function comp_values(array $arr1, array $arr2): bool {
        // 先比较数组长度
        if (count($arr1) !== count($arr2)) {
            return false;
        }

        // 排序后比较值
        sort($arr1);
        sort($arr2);

        return $arr1 === $arr2;
    }

    /**
     * 比较两个一维数组
     * @param array $old
     * @param array $new
     * @param array $keys 指定键名
     * @param bool $strict 是否严格比较（值和类型都相同）
     * @return array
     */
    public static function diff_detail(array $old, array $new, array $keys = [], bool $strict = false): array {
        $same = $diff = [];

        // 识别保留元素和修改元素
        foreach ($old as $k => $v) {
            if(!empty($keys) && !in_array($k, $keys)) continue;

            if (isset($new[$k])) {
                if ($strict && $v === $new[$k]) {
                    $same[$k] = $v;  // 相同元素,严格比较
                } elseif (!$strict && $v == $new[$k]){
                    $same[$k] = $v;  // 相同元素‌
                } else {
                    $diff['edit'][$k] = $new[$k];  // 修改元素‌
                }
            } else {
                $diff['del'][$k] = $v;  // 删除元素
            }
        }

        if(empty($keys)){
            // 识别新增元素
            $added = array_diff_key($new, $old);

            if (!empty($added)) {
                $diff['add'] = $added;  // 新增元素
            }
        }

        return [
            'same' => $same,
            'diff' => $diff
        ];
    }

    /**
     * 比较两个二维数组
     * @param array $old_arr 旧数组
     * @param array $new_arr 新数组
     * @param string|array $unique 唯一约束字段，有多个就传数组
     * @return array|array[]
     */
    public static function diff_column(array $old_arr, array $new_arr, $unique = 'id')
    {
        $changes = [
            'added'   => [],
            'removed' => [],
            'updated' => []
        ];

        // 创建旧数组的键值映射
        $old_map = [];

        foreach ($old_arr as $item) {
            $pk = '';

            if (is_string($unique)) {
                $pk = $item[$unique];
            } elseif (is_array($unique)) {
                foreach ($unique as $u) {
                    $pk .= ':' . $item[$u];
                }

                $pk = ltrim($pk, ':');
            }

            $old_map[$pk] = $item;
        }

        // 创建新数组的键值映射
        $new_map = [];

        foreach ($new_arr as $item) {
            $pk = '';

            if (is_string($unique)) {
                $pk = $item[$unique];
            } elseif (is_array($unique)) {
                foreach ($unique as $u) {
                    $pk .= ':' . $item[$u];
                }

                $pk = ltrim($pk, ':');
            }

            if (!isset($old_map[$pk])) {  // 检查新增项
                $changes['added'][] = $item;
            }

            $new_map[$pk] = $item;
        }

        // 检查删除项和更新项
        foreach ($old_map as $id => $item) {
            if (!isset($new_map[$id])) {  // 检查删除项
                $changes['removed'][] = $item;
            } else {
                // 深度比较数组差异
                $diff = self::diff_detail($item, $new_map[$id]);
                $changes['updated'][] = [
                    'old'   => $item,
                    'new'   => $new_map[$id],
                    'same'  => $diff['same'],
                    'diff'  => $diff['diff']
                ];
            }
        }

        return $changes;
    }

    /**
     * 将数组分段随机打乱
     * @param array $array
     * @param int $num
     * @return array
     */
    public static function chunk_shuffle(array $array, int $num = 10)
    {
        if(empty($array)) return [];

        $chunks = array_chunk($array, $num);
        $res = [];

        foreach ($chunks as $chunk){
            shuffle($chunk);
            $res = array_merge($res, $chunk);
        }

        return $res;
    }

    /**
     * 洗牌算法随机打乱数组
     * @param array $array
     * @param int $offset
     * @return array
     */
    public static function i_shuffle(array $array, int $offset = 0){
        if(empty($array)) return [];

        $len = count($array);

        for($i = $offset; $i < $len; $i++){
            $t = mt_rand($i, $len - 1);  // 随机选择 $i 及后边的一个下标
            list($array[$t], $array[$i]) = [$array[$i], $array[$t]];  // 两个下标交换值
        }

        return $array;
    }

    /**
     * @param array $items
     * @param int $total_weight
     * @param string $weight_name
     * @param string $cur_weight_name
     * @return array|null
     */
    public static function weight_round(array $items, int $total_weight, string $weight_name = 'weight', string $cur_weight_name = 'cur_weight') {
        $max_index = -1;
        $max_weight = -PHP_INT_MAX;

        // 遍历所有节点并更新当前权重
        foreach ($items as $index => &$item) {
            $item[$cur_weight_name] += $item[$weight_name]; // 增加权重

            // 寻找当前权重最高的节点
            if ($item[$cur_weight_name] > $max_weight) {
                $max_weight = $item[$cur_weight_name];
                $max_index = $index;
            }
        }
        unset($item); // 清除引用

        if ($max_index === -1) {
            return null; // 没有可用
        }

        // 减少选中节点的权重（减去总权重）
        $items[$max_index][$cur_weight_name] -= $total_weight;

        return [$items[$max_index], $items];
    }

    /**
     * 二维数组按指定的多个字段重组成多维键名数组
     * @param array $array 二维数组
     * @param array $fields 指定的多个字段，层次按先后顺序
     * @param string|null $target 多维键名指向的目标字段，null则指向整条row
     * @return mixed|null
     */
    public static function multi_key_format(array $array, array $fields, string $target = null) {
        $result = [];

        foreach ($array as $row) {
            // 使用引用逐步深入嵌套数组
            $current = &$result;

            // 遍历所有字段构建层级结构
            foreach ($fields as $field) {
                $key = $row[$field];

                // 如果当前层级不存在则初始化
                if (!isset($current[$key])) {
                    $current[$key] = [];
                }

                // 移动指针到下一层级
                $current = &$current[$key];
            }

            // 最后一个层级指向原始行数据或指定字段的值,多维key重复时，后面的row覆盖前面的row
            $current = is_null($target) ? $row : ($row[$target] ?? null);
        }

        return $result;
    }

    /**
     * 多维数组按指定多个键名查找
     * @param array $array   多维数组，键名是一系列具体值
     * @param array $values  指定的多个键名
     * @param mixed $default 未查到返回的默认结果
     * @return array|mixed|null
     */
    public static function multi_key_find(array $array, array $values, $default = null){
        $result = $array;

        foreach ($values as $value) {

            // 如果当前层级不存在则直接返回default
            if (!isset($result[$value])) {
                return $default;
            }

            // 移动指针到下一层级
            $result = $result[$value];
        }

        return $result;
    }

    /**
     * 二维数组行转列（值转字段）
     * @param array $list 二维数组
     * @param string $pivot_field 行转列的字段
     * @param string $group_field 分组字段
     * @param string $agge_field 统计字段
     * @param mixed $default 行转列的默认值
     * @param bool $add_total 是否添加$agge_field的总和字段
     * @param int $total_scale $add_total总和计算精度
     * @return array
     */
    public static function pivot(array $list, string $pivot_field, string $group_field, string $agge_field, $default = null, $without_fields = [], bool $add_total = false, int $total_scale = 2)
    {
        $map = self::multi_key_format($list, [$group_field, $pivot_field]);  // $group_field必须是第一层键
        $all_pivot_values = array_unique(array_column($list, $pivot_field));
        $res = [];

        foreach($map as $item){
            $one = [];

            foreach($all_pivot_values as $value){
                $one[$value] = $default;
            }

            $total = 0;

            foreach($item as $pivot_value => $row){
                $agge_value = $row[$agge_field] ?? 0;
                $one[$pivot_value] = $agge_value;
                $total = bcadd($total, $agge_value, $total_scale);

                unset($row[$pivot_field], $row[$agge_field]);

                $one = array_merge($row, $one);
            }

            if($add_total) $one['total'] = $total;

            if(!empty($without_fields)){
                $keys = array_keys($one);
                $filter_keys = array_diff($keys, $without_fields);
                $one = self::key_filter($one, $filter_keys);
            }

            $res[] = $one;
        }

        return $res;
    }

    /**
     * 二维数组单个列转行（字段转值）
     * @param array $list 二维数组
     * @param array $unpivot_values 转为值的所有二维数组的字段名称
     * @param string $unpivot_field 转换后所属新数组字段名称
     * @param string $agge_field 转换后$unpivot_field对应值所属字段
     * @param array $without_fields $list 转换到新数组需排除的字段
     * @return array
     */
    public static function unpivot(array $list, array $unpivot_values, string $unpivot_field, string $agge_field, array $without_fields = [])
    {
        $res = [];

        foreach($list as $item){
            $keys = array_keys($item);
            $filter_keys = array_diff($keys, $unpivot_values, $without_fields);
            $one = self::key_filter($item, $filter_keys);

            foreach($unpivot_values as $value){
                if(isset($item[$value])){
                    $one[$unpivot_field] = $value;
                    $one[$agge_field] = $item[$value];
                    $res[] = $one;
                }
            }
        }

        return $res;
    }

    /**
     * 数组多对多扁平化（一对多对一）
     * @param $params
     [
        [array 左表二维数组, string 左表关联字段, array 左表筛选字段 全选则设空数组],
        [array 中间表二维数组, string 左表关联字段, string 右表关联字段, array 左表筛选字段 全选则设空数组],
        [array 右表二维数组, string 右表关联字段, array 右表筛选字段 全选则设空数组],
     ]
     * @return array
     */
    public static function multi_join_multi($params){
        $res = [];

        list($left, $middle, $right) = $params;
        list($left_list, $left_join, $left_fields) = $left;
        list($middle_list, $ml_join, $mr_join, $middle_fields) = $middle;
        list($right_list, $right_join, $right_fields) = $right;

        $left_map = array_column($left_list, null, $left_join);
        $right_map = array_column($right_list, null, $right_join);

        array_walk($middle_list, function($middle) use(
            &$res,
            $left_map,
            $ml_join,
            $right_map,
            $mr_join,
            $left_fields,
            $right_fields,
            $middle_fields
        ) {
            $left = $left_map[$middle[$ml_join]] ?? [];
            $right = $right_map[$middle[$mr_join]] ?? [];

            if(!empty($left) && !empty($right)){
                if(!empty($left_fields)) $left = self::key_filter($left, $left_fields);

                if(!empty($right_fields)) $right = self::key_filter($right, $right_fields);

                if(!empty($middle_fields)) $middle = self::key_filter($middle, $middle_fields);

                $res[] = array_merge($left, $middle, $right);
            }
        });

        return $res;
    }
}













