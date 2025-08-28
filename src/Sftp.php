<?php

namespace basar911\phpUtil;

use app\lib\common\Singleton;

class Sftp {
    use Singleton;

    private $conn;
    private $res_sftp;
    private $config = [];

    public function __construct(){}

    // 连接sftp获取最新资源
    public function connect($config)
    {
        if(!function_exists('ssh2_connect')) throw new \Exception('未安装ssh2扩展');

        $this->config = $config;
        $this->conn = ssh2_connect($this->config['host'], $this->config['port']);

        if(!$this->conn) throw new \Exception('连接sftp失败');

        $res = ssh2_auth_password($this->conn, $this->config['user'], $this->config['password']);

        if(!$res) throw new \Exception('身份认证失败');

        $this->res_sftp = ssh2_sftp($this->conn);
        return $this;
    }

    /**
     * 下载文件
     * @param $remote
     * @param $local
     * @return void
     * @throws \Exception
     */
    public function download($remote, $local)
    {

        $content = file_get_contents("ssh2.sftp://" . intval($this->res_sftp) . $remote);

        if(!$content) throw new \Exception('读取远程文件失败');

        if(!is_dir(dirname($local))){
            mkdir(dirname($local), 777, true);
        }

        file_put_contents($local, $content);
    }

    /**
     * 判断目录或文件是否存在
     * @param $dir
     * @return bool
     */
    public function exits($dir)
    {
        return file_exists("ssh2.sftp://" . intval($this->res_sftp) . $dir);
    }
}

