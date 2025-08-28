<?php

namespace basar911\phpUtil;

use DateTime;

class Date
{
    /**
     * 求两个日期之间相差的天数
     * (针对1970年1月1日之后，求之前可以采用泰勒公式)
     * @param string $day1
     * @param string $day2
     * @return number
     */
    public static function diff_two_days($day1, $day2)
    {
        $second1 = strtotime(substr($day1, 0, 10));
        $second2 = strtotime(substr($day2, 0, 10));

        return abs($second1 - $second2) / 86400;
    }

    /**
     * 获取当前datetime
     * @return false|string
     */
    public static function get_date_time()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * 获取指定月份的最后一天
     * @param $month
     * @param $year
     * @return false|string
     */
    public static function get_month_last_day($month, $year = 0){
        if($year == 0) $year = date('Y');

        return date("d", strtotime("$year-$month-01 +1 month -1 day"));
    }

    /**
     * 获取格式化时间差
     * @param $timestamp
     * @return string|void
     * @throws \Exception
     */
    public static function get_format_diff($timestamp)
    {
        $datetime     = new DateTime(date('Y-m-d H:i:s', $timestamp));
        $datetime_now = new DateTime();
        $interval     = $datetime_now->diff($datetime);
        list($y, $m, $d, $h, $i, $s) = explode('-', $interval->format('%y-%m-%d-%h-%i-%s'));
        if ((($result = $y) && ($suffix = '年前')) ||
            (($result = $m) && ($suffix = '月前')) ||
            (($result = $d) && ($suffix = '天前')) ||
            (($result = $h) && ($suffix = '小时前')) ||
            (($result = $i) && ($suffix = '分钟前')) ||
            (($result = $s) && ($suffix = '刚刚'))) {
            return $suffix != '刚刚' ? $result . $suffix : $suffix;
        }
    }

    /**
     * 获取两个日期的格式化时间差
     * @param $end_time
     * @param $start_time
     * @return string
     */
    public static function diff_format($end_time, $start_time){
        $diff = abs($end_time - $start_time);

        $day = floor(($diff) / 86400);
        $hour = floor(($diff) % 86400 / 3600);
        $minute = floor(($diff) % 86400 % 3600 / 60);
        $second = $diff % 86400 % 60;

        $str = '';
        $day > 0 && $str .= $day . '天';
        $hour > 0 && $str .= $hour . '小时';
        $minute > 0 && $str .= $minute . '分钟';
        $second > 0 && $str .= $second . '秒';

        return $str;
    }

    /**
     * 获取13位时间戳
     * @return int
     */
    public static function get_micro_time()
    {
        // 获取当前的微秒时间戳
        $microtime = microtime(true);

        return (int)bcmul($microtime, 1000);
    }

    /**
     * @param string $date Y-m-d
     * @param string $weekend_type
     * @return bool
     */
    public static function is_work_day(string $date, $weekend_type = 'double')
    {
        $holiday = config('app.holiday.rest');
        $adjust = config('app.holiday.adjust');
        $date = substr($date, 0, 10);

        if(in_array($date, $adjust)) return true;  // 是否是调休工作日

        foreach($holiday as $bewteen){  // 是否是节假日
            if(count($bewteen) == 1) $bewteen[1] = $bewteen[0];

            if(self::is_between_day($date, $bewteen)) return false;
        }

        // 是否是周末
        $w = date('w', strtotime($date));

        if($weekend_type == 'double' && in_array($w, [6, 0])) return false;

        if($weekend_type == 'simple' && $w == 0) return false;

        return true;
    }

    /**
     * 判断 $date 是否在 $between = [$start_date, $end_date] 中
     * @param string $date Y-m-d
     * @param array $between
     * @return bool
     */
    public static function is_between_day(string $date, array $between)
    {
        list($start_date, $end_date) = $between;

        if(
            strtotime($date) >= strtotime($start_date)
            && strtotime($date) <= strtotime($end_date)
        ) return true;

        return false;
    }

}