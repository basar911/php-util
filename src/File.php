<?php

namespace basar911\phpUtil;

// 公共文件处理类
use Exception;
use ReflectionException;

class File
{
    /**
     * 读取文件并逐行处理
     * @param $file
     * @param \Closure $func 匿名函数，参数：（行数， 该行内容）
     * @return void
     * @throws Exception
     */
    public static function handle_line($file, \Closure $func)
    {
        $handler = fopen($file, 'r'); // 打开文件只读

        if (!$handler) throw new Exception('读取文件失败');

        $line = 0;

        while ($content = fgets($handler)) { // 逐行读取文件内容
            $line++;
            $func($line, $content);
        }

        fclose($handler); // 关闭文件
    }

    /**
     * 读取文件并逐行处理
     * @param $file
     * @param \Closure $func 匿名函数，参数：（行数， 该行内容）
     * @return void
     * @throws Exception
     */
    public static function handle_line_yield($file, \Closure $func)
    {
        $yield_read = self::yield_read($file);

        foreach($yield_read as $item){
            list($line, $content) = $item;
            $func($line, $content);
        }
    }

    /**
     * yield读取文件
     * @param $file
     * @return \Generator
     * @throws Exception
     */
    public static function yield_read($file)
    {
        $handler = fopen($file, 'r'); // 打开文件只读

        if (!$handler) throw new Exception('读取文件失败');

        $line = 0;

        while ($content = fgets($handler)) { // 逐行读取文件内容
            $line++;
            yield [$line, $content];
        }

        fclose($handler); // 关闭文件
    }

    /**
     * 获取文件夹下所有类
     * @param string $dir 文件夹地址 非斜杠结尾
     * @param bool $only_name 是否只获取类名 true-是  false-获取实例
     * @return object[]|string[]
     */
    public static function get_class_by_dir($dir, $only_name = false): array
    {
        $files = self::get_glob_files($dir . DIRECTORY_SEPARATOR . '*');

        if (empty($files)) return [];

        $result = [];

        array_walk($files, function ($f) use (&$result, $dir, $only_name) {
            $file = pathinfo($f);

            if ($file['extension'] != 'php') return;

            $file_content = file_get_contents($f);
            $file_content = Str::del_enter($file_content);  // 去掉换行符
            $namespace = Str::sub_between($file_content, 'namespace', ';', ' ', 'once');

            if(empty($namespace)) return;

            $class_name = Str::sub_between($file_content, 'class', 'extends', ' ', 'once');

            if(empty($class_name)){
                $class_name = Str::sub_between($file_content, 'class', 'implements', ' ', 'once');

                if(empty($class_name)){
                    $class_name = Str::sub_between($file_content, 'class', '\{', ' ', 'once');
                }
            }

            if(empty($class_name)) return;

            $class_name = $namespace . '\\' . $class_name;

            if ($only_name) {
                $result[] = $class_name;
            } else {
                $result[] = new $class_name;
            }
        });

        return $result;
    }

    /**
     * 获取文件夹下所有文件名
     * @param string $dir
     * @param string $ext
     * @return array
     */
    public static function get_glob_files(string $dir, string $ext = '*'): array
    {
        $files = glob($dir);
        $ret = [];
        $ext_arr = explode(',', $ext);

        foreach($files as $file){
            if(is_dir($file)){
                $ret = array_merge($ret, self::get_glob_files($file . '/*', $ext));
            }

            if(is_file($file)){
                $file_ext = pathinfo($file, PATHINFO_EXTENSION);

                if($ext == '*' || in_array($file_ext, $ext_arr)) $ret[] = $file;
            }
        }

        return $ret;
    }

    /**
     * 向文件指定行写入内容
     * @param string $file
     * @param int $line
     * @param string $insert
     * @return bool
     * @throws Exception
     */
    public static function append_to($file, $line, $insert): bool
    {
        if ($line > 0) $line--;

        if (!file_exists($file)) {
            throw new Exception('文件不存在');
        }

        if (!is_writable($file)) {
            throw new Exception('文件不可写');
        }

        $lines = file($file); // 读取文件到数组
        array_splice($lines, $line, 0, $insert);

        // 写回文件
        file_put_contents($file, implode('', $lines));
        return true;
    }

    /**
     * 搜索指定内容在文件的哪些行
     * @param $file
     * @param $search
     * @return array
     * @throws Exception
     */
    public static function search_line($file, $search): array
    {
        if (!file_exists($file)) {
            throw new Exception('文件不存在');
        }

        if (!is_readable($file)) {
            throw new Exception('文件不可读');
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);  // 忽略换行符
        $res = [];

        array_walk($lines, function ($content, $line) use($search, &$res){
            if (strpos($content, $search) !== false) {
                $res[] = $line + 1; // file()是从0开始计行数的，所以加1
            }
        });

        return $res;
    }

    /**
     * 导出大量数据到Excel(csv)文件
     * @param \Closure $func
     * @param array $header_line 第一行列名
     * @param string $file_name
     * @param int $size
     * @param string $export_url 直接输出到浏览器 or 输出到指定路径文件下
     * @return string
     */
    public static function export_csv(\Closure $func, array $header_line, string $file_name = '', int $size = 10000, string $export_url = 'php://output')
    {
        set_time_limit(0);// 取消脚本运行时间的限制
        ini_set('memory_limit', '256M');// 设置php内存限制

        $file_name = empty($file_name) ? date('YmdHis') : $file_name;
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $file_name . '.csv"');
        header('Cache-Control: max-age=0');

        // 打开PHP文件句柄,php://output 表示直接输出到浏览器
        $fp = fopen($export_url, 'a');

        // 输出Excel列名信息
        foreach ($header_line as $key => $value) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            try {
                $header_line[$key] = iconv('utf-8', 'gbk', $value);
            } catch (\Throwable $e) {
                $header_line[$key] = mb_convert_encoding($value, "GBK", "UTF-8");
            }
        }

        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $header_line);
        $page = 1;

        while(true){
            $data = $func($page, $size);  // 闭包获取分页数据

            if(empty($data)) break;

            // 逐行取出，不浪费内存
            while($row = array_shift($data)){
                foreach ($row as $k => $v) {
                    try {
                        $row[$k] = iconv('utf-8', 'gbk', $v);
                    } catch (\Throwable $e) {
                        $row[$k] = mb_convert_encoding($v, "GBK", "UTF-8");
                    }
                }

                fputcsv($fp, $row);
            }

            // 每隔$size行，刷新一下输出buffer
            ob_flush();
            flush();// 刷新buffer
            $page++;
        }

        fclose($fp);
        exit();
    }

    /**
     * 删除文件夹下所有文件
     * @param string $dir
     * @param bool $del_dir
     * @return bool
     */
    public static function deleteFilesAndDirs(string $dir, bool $del_dir = false) {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::deleteFilesAndDirs("$dir/$file", $del_dir) : unlink("$dir/$file");
        }

        return !$del_dir || rmdir($dir);
    }

    /**
     * 计算代码执行时间
     * @param \Closure $func
     * @return void
     */
    public static function costTime(\Closure $func){
        $beg_time = microtime(true);
        is_callable($func) && $func();
        $end_time = microtime(true);
        dump('cost time ' . bcsub($end_time, $beg_time, 4) . ' 秒');
    }
}