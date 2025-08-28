<?php

namespace basar911\phpUtil;

use app\lib\common\Singleton;
use support\exception\BusinessException;

class RedisTool
{
    use Singleton;

    /**
     * @var int|mixed|string
     */
    private $_lockFlag;
    private $redis;  // redis 实例

    public function setRedis($redis)
    {
        $this->redis = $redis;
        return $this;
    }

    public function __call($name, $arguments)
    {
        return $this->redis->$name(...$arguments);
    }

    /**
     * 获取redis锁(悲观锁)
     * @param string $name 锁名称
     * @param string $key 全局唯一id，解锁时需用到
     * @param integer $expire 过期时间，单位为秒
     * @return int|boolean      1|false
     */
     public function lock($name, $key = '', $expire = 3){
         if(empty($key)) return false;
         return $this->redis->set($name, $key, 'ex', $expire, 'nx');
     }

    /**
     * 获取全局唯一id
     * @return string
     */
    public static function randStr()
    {
        //strtoupper转换成全大写的
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $uuid = substr($charid, 0, 8) . substr($charid, 8, 4) . substr($charid, 12, 4) . substr($charid, 16, 4) . substr($charid, 20, 12);
        return $uuid;
    }

    /**
     * 解开redis锁
     * @param string $name 锁名称
     * @param string $key 全局唯一id
     * @return bool
     */
     public function unlock($name, $key = ''){
         //使用lua脚本保证高并发下的原子性
         $script = 'if redis.call("get",KEYS[1]) == ARGV[1] then return redis.call("del",KEYS[1]) else return 0 end';
         return $this->redis->eval($script, 1, $name, $key);
     }

    /**
     * 限制访问api频率（令牌桶模式）(不适合在常驻内存框架使用)
     * @param string $key 标识key,可用user_id
     * @param int $limit_num 单位时间内的限定访问次数
     * @param int $expire 单位时间段（秒），例如限定一分钟内访问6次，则$limit_num = 6, $expire = 60;
     * @return array
     */
    public function requestLimit($key, $limit_num, $expire = 60)
    {
        $result = ['status' => true, 'msg' => '访问成功'];
        $this->redis->watch($key);//开启redis事务，监听
        $limit_val = $this->redis->get($key);

        if ($limit_val) {
            $limit_val = json_decode($limit_val, true);
            $new_num = floor(min($limit_num, ($limit_val['num'] - 1) + (($limit_num / $expire) * (time() - $limit_val['time']))));//获取上次访问的时间即上次存入令牌的时间，计算当前时刻与上次访问的时间差乘以速率就是此次需要补充的令牌个数，注意补充令牌后总的令牌个数不能大于初始化的令牌个数，以补充数和初始化数的最小值为准。

            if ($new_num > 0) {
                $redis_val = json_encode(['num' => $new_num, 'time' => $_SERVER['REQUEST_TIME']]);
            } else {
                return ['status' => false, 'msg' => '服务器繁忙，请稍后操作！'];
            }
        } else {
            $redis_val = json_encode(['num' => $limit_num, 'time' => $_SERVER['REQUEST_TIME']]);//为用户重新颁发令牌
        }

        $this->redis->multi();//乐观锁监听
        $this->redis->set($key, $redis_val);
        $rob_result = $this->redis->exec();//执行事务

        if (!$rob_result) {
            $result = ['status' => false, 'msg' => '服务器繁忙，请稍后操作！'];
        }

        return $result;
    }

    /**
     * 限制访问频率（可在常驻内存框架使用）
     * @param string $key 标识key,可用user_id
     * @param int $limit_num 单位时间内的限定访问次数
     * @param int $expire 单位时间段（秒），例如限定一分钟内访问6次，则$limit_num = 6, $expire = 60;
     * @return bool
     */
    public function rateLimit(string $key, int $limit_num, int $expire = 60)
    {
        try {
            // 使用Lua脚本保证原子性操作
            $lua = <<<LUA
local key = KEYS[1]
local max = tonumber(ARGV[1])
local ttl = tonumber(ARGV[2])

local current = redis.call('GET', key) or 0
if tonumber(current) >= max then
    return {0, 0}
end

local newVal = redis.call('INCR', key)
if newVal == 1 then
    redis.call('EXPIRE', key, ttl)
end

return {1, max - newVal}
LUA;

            // 执行原子化脚本
            $res = $this->redis->eval($lua, 1, $key, $limit_num, $expire);

            return $res[0] == 1;
        } catch (\Throwable $e) {
            // Redis异常时默认放行（避免因限流系统故障导致服务不可用）
            return true;
        }
    }

    /**
     * 生成手机验证码
     * @param string $mobile 手机号
     * @param string $area 作用域，如登录-login  注册-reg  富民-fumin
     * @param int $expire 过期时间（秒）
     * @param int $code_lenth 验证码长度
     * @return string
     * @throws BusinessException
     */
    public function generate_mobile_code($mobile, $area, $expire = 300, $code_lenth = 4)
    {
        $code = Str::rand_string($code_lenth, 1);

        if(!$this->redis->set($area . ':' . $mobile, $code, 'ex', $expire, 'nx')) throw new BusinessException('验证码已存在');

        return $this->redis->get($area . ':' . $mobile);
    }

    /**
     * 验证手机验证码
     * @param $mobile
     * @param $code
     * @param $key
     * @return bool
     * @throws BusinessException
     */
    public function verify_mobile_code($mobile, $code, $area): bool
    {
        $key = $area . ':' . $mobile;

        if(!$this->redis->exists($key)) throw new BusinessException('验证码已过期');;

        if((string)$code !== $this->redis->get($key)) throw new BusinessException('验证码错误');;

        $this->redis->del($key);

        return true;
    }
}