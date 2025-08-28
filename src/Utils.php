<?php

namespace basar911\phpUtil;


// 公共函数
class Utils
{

    /**
     * 经典的概率算法，抽盲盒
     * $proArr是一个预先设置的数组，
     * 假设数组为：array(100,200,300，400)，
     * 开始是从1,1000 这个概率范围内筛选第一个数是否在他的出现概率范围之内，
     * 如果不在，则将概率空间，也就是k的值减去刚刚的那个数字的概率空间，
     * 在本例当中就是减去100，也就是说第二个数是在1，900这个范围内筛选的。
     * 这样 筛选到最终，总会有一个数满足要求。
     * 就相当于去一个箱子里摸东西，
     * 第一个不是，第二个不是，第三个还不是，那最后一个一定是。
     * 这个算法简单，而且效率非常高，
     * 这个算法在大数据量的项目中效率非常棒。
     * ---------------------------------------------------
     * * 奖项数组
     * 是一个二维数组，记录了所有本次抽奖的奖项信息，
     * 其中id表示中奖等级，prize表示奖品，v表示中奖概率。
     * 注意其中的v必须为整数，你可以将对应的 奖项的v设置成0，即意味着该奖项抽中的几率是0，
     * 数组中v的总和（基数），基数越大越能体现概率的准确性。
     * 本例中v的总和为100，那么平板电脑对应的 中奖概率就是1%，
     * 如果v的总和是10000，那中奖概率就是万分之一了。
     *
     *   $prize_arr = array(
     *       '0' => array('id'=>1,'prize'=>'平板电脑','v'=>1),
     *       '1' => array('id'=>2,'prize'=>'数码相机','v'=>5),
     *       '2' => array('id'=>3,'prize'=>'音箱设备','v'=>10),
     *       '3' => array('id'=>4,'prize'=>'4G优盘','v'=>12),
     *       '4' => array('id'=>5,'prize'=>'10Q币','v'=>22),
     *       '5' => array('id'=>6,'prize'=>'下次没准就能中哦','v'=>50),
     *    );
     * @param array $array 奖品信息
     * @param int $total_rate 概率总数
     * @param string $rate_name 概率字段名
     * @param string $id_name 奖品id字段名
     * @return  array
     */
    public static function get_lottery($array, $total_rate = 100, $id_name = 'id', $rate_name = 'v')
    {
        $prize_arr = array_column($array, null, $id_name);//id：奖品id, v: 奖品中奖概率
        $pro_id    = 0;

        //概率数组循环
        foreach ($prize_arr as $id => $prize) {
            $id      = intval($id);
            $rate    = intval($prize[$rate_name]);
            $randNum = mt_rand(1, $total_rate);

            if ($randNum <= $rate) {
                $pro_id = $id;
                break;
            } else {
                $total_rate -= $rate;
            }
        }

        return $prize_arr[$pro_id];
    }

    /**
     * 根据区间来计算概率,只需循环一次，比上面的方法效率更高
     * @param array $array 奖品信息
     * @param int $total_rate 概率总数
     * @param string $rate_name 概率字段名
     * @param string $id_name 奖品id字段名
     * @return array
     */
    public static function get_lottery_rate($array, $total_rate = 100, $id_name = 'id', $rate_name = 'v')
    {
        $prize_rate = [];
        $start_rate = $end_rate = 1;
        $prize_map  = [];

        foreach ($array as $prize) {

            if ($prize[$rate_name] <= 0) {
                continue;
            }

            $end_rate                     = $start_rate + $prize[$rate_name] - 1;
            $prize_rate[$prize[$id_name]] = [$start_rate, $end_rate];  //  概率区间
            $start_rate                   = $end_rate + 1;
            $prize_map[$prize[$id_name]]  = $prize;
        }

        unset($array);
        $lot_id   = 0;
        $rand_num = mt_rand(1, $total_rate);

        //概率数组循环
        foreach ($prize_rate as $id => $rate) {

            if ($rand_num <= $rate[1] && $rand_num >= $rate[0]) {
                $lot_id = $id;
                break;
            }
        }

        return $lot_id > 0 ? $prize_map[$lot_id] : [];
    }

    /**
     * 生成随机手机号
     * @param int $count 生成数量
     * @param string $type 结果格式   array:数组   string:字符串
     * @param bool $white_space 是否消除空格，仅在$type为字符串时有效
     * @return array|false|string|string[]|null
     */
    public static function generate_mobile($count, $type = "array", $white_space = false)
    {
        $tmp = [];
        $arr = array(130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 144, 147, 150, 151, 152, 153, 155, 156, 157, 158, 159, 176, 177, 178, 180, 181, 182, 183, 184, 185, 186, 187, 188, 189,);

        for ($i = 0; $i < $count; $i++) {
            $tmp[] = $arr[array_rand($arr)] . '****' . mt_rand(1000, 9999);
        }

        if ($type === "string") {
            $tmp = json_encode($tmp);//如果是字符串，解析成字符串
        }

        if ($white_space === true) {
            $tmp = preg_replace("/\s*/", "", $tmp);
        }

        return $tmp;
    }

    /**
     * 压缩图片
     * @param string $source_path  原文件地址
     * @param string $target_path  压缩后文件地址
     * @param int $quality 压缩质量,50压缩比例约25%
     * @return bool
     * @throws \Exception
     */
    public static function compress_img($source_path, $target_path, $quality)
    {
        if (!extension_loaded('gd') || !function_exists('gd_info')) {
            throw new \Exception('GD扩展未启用，请安装或启用GD扩展');
        }

        if(!is_dir(dirname($target_path))){
            mkdir(dirname($target_path), 0777, true);
        }

        $info = getimagesize($source_path);
        $mime = $info['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source_path);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source_path);
                break;
            default:
                throw new \Exception('压缩失败，请检查文件类型');
        }

        imagejpeg($image, $target_path, $quality);
        imagedestroy($image);

        return true;
    }

    // 美观打印
    public static function pp($data)
    {
        echo '<pre style=color:#00ae19;>';
        var_export($data);
        echo '</pre>';
    }
}