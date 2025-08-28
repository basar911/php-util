<?php

namespace basar911\phpUtil;

use Exception;

class Curl
{

    /**
     * @throws Exception
     */
    public static function get($url, $params = [], $header = [])
    {
        if (empty($url)) {
            return false;
        }

        $call_url = empty($params) ? $url : $url . '?' . http_build_query($params);
        $ch = curl_init(); // 初始化curl
        $header = array_merge(["Content-type:application/json"], $header);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_URL, $call_url); // 抓取指定网页
        curl_setopt($ch, CURLOPT_TIMEOUT, 5000); // 设置超时时间5秒
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // curl不直接输出到屏幕
        $res = curl_exec($ch);

        if ($res === false) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return Str::is_json($res) ? json_decode($res, true) : $res;
    }

    /*
    * curl 发送JSON 格式数据
    * @param $url string URL
    * @param $data array|string 请求的具体内容
    * @return string
    *   code 状态码
    *   result 返回结果
    */
    /**
     * @throws Exception
     */
    public static function json($url, $data, $method = 'get', $auth = [], $header = [])
    {
        $curl = curl_init();

        if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST, 1);
        } elseif ($method == 'put') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        } elseif ($method == 'delete') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        }

        if (is_array($data)) {
            $data_string = json_encode($data, 320);
        } elseif (is_string($data)) {
            $data_string = $data;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        if (!empty($auth)) {
            curl_setopt($curl, CURLOPT_USERPWD, $auth['user'] . ':' . $auth['pwd']);
        }

        $header = array_merge(["Content-type:application/json;charset='utf-8'", "Accept:application/json"], $header);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        ob_start();
        curl_exec($curl);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($return_code != 200) {
            throw new Exception($return_content);
        }

        return Str::is_json($return_content) ? json_decode($return_content, true) : $return_content;
    }

    /**
     * @throws Exception
     */
    public static function post($url, $data = [], $header = [])
    {
        $data_string = '';

        if (is_array($data)) {
            $data_string = json_encode($data);
        } elseif (is_string($data)) {
            $data_string = $data;  // $data已经过http_build_query()处理
        }

        $header = array_merge(["Content-type:application/json;charset='utf-8'", "Accept:application/json"], $header);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);

        if ($res === false) {
            throw new Exception(curl_error($curl));
        }

        curl_close($curl);
        return Str::is_json($res) ? json_decode($res, true) : $res;
    }

    /**
     * @throws Exception
     */
    public static function post_array($url, $data = [], $header = [])
    {
        // 初始化cURL会话
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        // 执行请求并获取响应
        $res = curl_exec($curl);

        if ($res === false) {
            throw new Exception(curl_error($curl));
        }

        curl_close($curl);
        return Str::is_json($res) ? json_decode($res, true) : $res;
    }

    /**
     * @throws Exception
     */
    public static function put($httpUrl, $data, $header = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $httpUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $header = array_merge(["Content-type:application/json"], $header);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $res = curl_exec($ch);

        if ($res === false) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);
        return Str::is_json($res) ? json_decode($res, true) : $res;
    }

    /**
     * @throws Exception
     */
    public static function delete($url, $header = [])
    {
        $header = array_merge(["Content-type:application/json;charset='utf-8'", "Accept:application/json"], $header);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);

        if ($res === false) {
            throw new Exception(curl_error($curl));
        }

        curl_close($curl);
        return Str::is_json($res) ? json_decode($res, true) : $res;
    }
}