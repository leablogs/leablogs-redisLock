<?php
declare(strict_types = 1);
namespace Leablogs\redisLock;

class RedisLock
{

    private $redis;

    private $lockedName = array();

    public function __construct($redis)
    {
        $this->redis = new \Redis();
    }

    /**
     * 获取锁
     *
     * @author shilh <shilh123@sina.cn>
     * @param string $key
     *            锁的标识
     * @param integer $timeout
     *            循环锁的等待超时时间，再次时间内会一直尝试获取锁，直到设置的超时时间，0，失败后返回不等待
     * @param integer $expire
     *            锁的最大生存时间（秒）必须大于0，如果超时未释放锁，系统强制释放
     * @param integer $waiteIntervalUs
     *            获取锁失败后挂起等时间间隔到了在重新获取单位（毫秒）
     * @return bool
     * @date 2019年12月2日 下午2:52:04
     */
    public function lock($key, $timeout = 0, $expire = 15, $waiteIntervalUs = 100000): bool
    {
        if ($key == null)
            return false;
        // get current time
        $current_time = time();
        // lock fail,wait timeout
        $timeoutAt = $current_time + $timeout;
        // lock max survival
        $expireAt = $current_time + $expire;
        $redisKey = "Lock:{$key}";
        while (true) {
            // 将rediskey 最大生存时刻存到redis，过了这个时刻会自动释放
            $result = $this->redis->setnx($redisKey, $expireAt);

            if ($result != false) {
                // 设置key失效时间
                $this->expire($redisKey, $expireAt);
                // 蒋锁的表示放到lockname数组中
                $this->lockedName[$key] = $expireAt;
                return true;
            }
            // 已秒为单位返回给定的key剩余生存周期
            $ttl = $this->redis->ttl($redisKey);
            // ttl小于0，表示key没有设置生存周期
            //
            //
            if ($ttl < 0) {
                $this->redis->set($redisKey, $expireAt);
                $this->lockedNames[$key];
                return true;
            }
            // 如果设置锁失败，或超过最大等待时间，就退出
            if ($timeout <= 0 || $timeoutAt < microtime(true))
                break;
            // /间隔 指定时间后请求
            usleep($waiteIntervalUs);
        }
        return true;
    }

    /**
     * 释放锁
     *
     * @author shilh <shilh123@sina.cn>
     * @param string $key
     * @return boolean
     * @date 2019年12月2日 下午3:00:16
     */
    public function unLock($key): bool
    {
        if ($this->isLocking($key)) {
            if ($this->redis->delete("Lock:{$key}")) {
                unset($this->lockedName[$key]);
                return true;
            }
        }
        return true;
    }

    /**
     * 释放所有锁
     *
     * @author shilh <shilh123@sina.cn>
     * @return boolean
     * @date 2019年12月2日 下午3:01:07
     */
    public function unLockAll(): bool
    {
        $allSuccess = true;
        foreach ($this->lockedName as $key => $expireAt) {
            if (false === $this->unLock($key)) {
                $allSuccess = false;
            }
        }
        return $allSuccess;
    }

    /**
     * 给当前所设置生存周期,必须大于0
     *
     * @author shilh <shilh123@sina.cn>
     * @param string $key
     * @param integer $expire
     * @return boolean
     * @date 2019年12月2日 下午3:04:51
     */
    public function expire($key, $expire): bool
    {
        if ($this->isLocking($key)) {
            $expire = max($expire, 1);
            if ($this->redis->expire("Lock:$name", $expire)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取当前指定锁状态
     *
     * @author shilh <shilh123@sina.cn>
     * @param string $key
     * @return boolean
     * @date 2019年12月2日 下午3:01:27
     */
    private function isLocking($key): string
    {
        if (isset($this->lockedName[$key])) {
            return (string) $this->lockedName[$name] = (string) $this->redisString->get("Lock:{$name}");
        }
        return false;
    }
}

