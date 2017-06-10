<?php

/**
 * @author Felix Huang <yelfivehuang@gmail.com>
 * @date 2017-06-10
 */

namespace fk\daemon;

class Signal
{
    public static function register()
    {
        $signals = [SIGABRT];
        foreach ($signals as $signal) {
            pcntl_signal($signal, static::handler());
        }
    }

    public static function handler()
    {
        return function ($signal) {
            switch ($signal) {
                case SIGABRT:
                    Cache::clearPID();
                    exit(0);
            }
        };
    }

    public static function dispatch()
    {
        pcntl_signal_dispatch();
    }

    public static function send($PIDs, $signal)
    {
        if (!is_array($PIDs)) $PIDs = [$PIDs];
        foreach ($PIDs as $pid) {
            posix_kill($pid, $signal);
        }
    }
}