<?php

namespace basar911\phpUtil;

class Math
{

    /* 求多个数的最小公倍数
    * @param int ...$numbers
    * @return int|mixed
    */
    public static function lcm(...$numbers)
    {
        $ans = $numbers[0];
        for ($i = 1; $i < count($numbers); $i++) {
            $ans = ((($numbers[$i] * $ans)) / (self::gcd($numbers[$i], $ans)));
        }

        return $ans;
    }

    /**
     * 求多个数的最大公约数
     * @param int ...$numbers
     * @return int|mixed
     */
    public static function gcd(...$numbers)
    {
        if (count($numbers) > 2) {
            return array_reduce($numbers, 'gcd');
        }

        $r = $numbers[0] % $numbers[1];
        return $r === 0 ? abs($numbers[1]) : self::gcd($numbers[1], $r);
    }

    /**
     * 从$array数组中取$m个数的组合算法
     * @param array $array
     * @param int $m
     * @return array
     */
    public static function combine(array $array, int $m)
    {
        if(empty($array) || $m < 1) return [];

        if ($m == 1) {
            return array_map(function ($item) {
                return [$item];
            }, $array);
        }

        $result = [];
        $l = count($array) - $m;

        for ($i = 0; $i <= $l; $i++) {
            $slice  = array_slice($array, $i + 1);
            $res = self::combine($slice, $m - 1);

            foreach ($res as $comb) {
                array_unshift($comb, $array[$i]);
                $result[] = $comb;
            }
        }

        return $result;
    }

    /**
     * 从$array数组中取$m个数的排列算法
     * @param array $array
     * @param int $m
     * @return array
     */
    public static function permute(array $array, int $m)
    {
        if(empty($array) || $m < 1) return [];

        if ($m == 1) {
            return array_map(function ($item) {
                return [$item];
            }, $array);
        }

        $result = [];
        $l = count($array);

        for ($i = 0; $i < $l; $i++) {
            $slice  = array_merge(array_slice($array, 0, $i), array_slice($array, $i + 1));
            $res = self::permute($slice, $m - 1);

            foreach ($res as $comb) {
                array_unshift($comb, $array[$i]);
                $result[] = $comb;
            }
        }

        return $result;
    }

    /**
     * 浮点精度的累加求和
     * @param $nums
     * @param string $return_type
     * @param int $scale 结果保留几位小数
     * @return float|int
     */
    public static function bc_sum(array $nums, string $return_type = 'float', int $scale = 2)
    {
        $sum = array_reduce($nums, function ($total, $number) {
            return bcadd($total, $number, 10);
        }, 0);

        return $return_type == 'float' ? round((float)$sum, $scale) : intval($sum);
    }

    /**
     * 二维数组按字段分组求和
     * @param array $list
     * @param string $count_field
     * @param string $group_field
     * @param int $scale
     * @return array
     */
    public static function bc_group_sum(array $list, string $count_field, string $group_field, int $scale = 2)
    {
        if(empty($list)) return [];

        $res = [];
        $add = 0;

        foreach ($list as $item)
        {
            $add = $res[$item[$group_field]] ?? 0;

            if($scale == 0){
                $res[$item[$group_field]] = $add + $item[$count_field];
            }else{
                $res[$item[$group_field]] = bcadd($add, $item[$count_field], 10);
            }

        }

        if($scale > 0){
            foreach($res as $key => $value){
                $res[$key] = round($value, $scale);
            }
        }

        return $res;
    }

    /**
     * 浮点精度的累减求差
     * @param int|float $number
     * @param array $nums
     * @param string $return_type
     * @param int $scale 结果保留几位小数
     * @return float|int
     */
    public static function bc_sub($number, array $nums, string $return_type = 'float', int $scale = 2)
    {
        $result = array_reduce($nums, function ($res, $sub) {
            return bcsub($res, $sub, 10);
        }, $number);

        return $return_type == 'float' ? round((float)$result, $scale) : intval($result);
    }

     /**
     * 浮点精度的累乘
     * @param int|float $number
     * @param array $nums
     * @param string $return_type
     * @param int $scale 结果保留几位小数
     * @return float|int
     */
    public static function bc_mul($number, array $nums, string $return_type = 'float', int $scale = 2)
    {
        $result = array_reduce($nums, function ($res, $div) {
            return bcmul($res, $div, 10);
        }, $number);

        return $return_type == 'float' ? round((float)$result, $scale) : intval($result);
    }

    /**
     * 浮点精度的累除
     * @param int|float $number
     * @param array $nums
     * @param string $return_type
     * @param int $scale 结果保留几位小数
     * @return float|int
     */
    public static function bc_div($number, array $nums, string $return_type = 'float', int $scale = 2)
    {
        $result = array_reduce($nums, function ($res, $div) {
            return bcdiv($res, $div, 10);
        }, $number);

        return $return_type == 'float' ? round((float)$result, $scale) : intval($result);
    }

    /**
     * 计算两个坐标点的距离
     * @param array $a  [x, y]
     * @param array $b  [x, y]
     * @param float|null $comp_l  传入了该参数，则返回距离与该参数值的比较结果，1-大于 0-等于  -1-小于
     * @return float|int
     */
    public static function point_distance(array $a, array $b, ?int $comp_l = null, $scale = 10){
        $x_len = pow($a[0] - $b[0], 2);
        $y_len = pow($a[1] - $b[1], 2);

        if(is_null($comp_l)) return sqrt($x_len + $y_len);

        return bccomp($x_len + $y_len, pow($comp_l, 2), $scale);
    }

    /**
     * 价格元转为分
     * @param int|float $money
     * @return int
     */
    public static function yuan_to_fen($money)
    {
        return (int) round(bcmul($money, 100, 2));
    }

    /**
     * 价格分转为元
     * @param int|float $money
     * @return float
     */
    public static function fen_to_yuan($money)
    {
        return (float) bcdiv($money, 100, 2);
    }

    /**
     * 逆波兰运算
     * @param string $express 数学运算表达式  '(10.05 * 3.05 - (8.73 / 12.3) * 7.5) * 3.50 / 80.90'  // 负数要加括号，否则报错  -1 => （0 - 1）
     * @param int $res_scale 返回结果小数点位
     * @param int $rpn_scale 逆波兰运算小数点位，越大精度越高
     * @return float
     */
    public static function rpn(string $express, int $res_scale = 2, int $rpn_scale = 10)
    {
        $calculator = new RPN();
        $rpn_express = $calculator->toRPNExpression($express);
        $rpn = $calculator->calculate($rpn_express, $rpn_scale);

        return round($rpn, $res_scale);
    }

    /**
     * 取随机浮点数
     * @param float $min
     * @param float $max
     * @params int $scale
     * @return float
     */
    public static function random_float($min = 0, $max = 1, int $scale = 2)
    {
        $num = $min + mt_rand() / mt_getrandmax() * ($max - $min);

        return round($num, $scale);
    }
}