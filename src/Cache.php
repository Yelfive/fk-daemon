<?php

/**
 * @author Felix Huang <yelfivehuang@gmail.com>
 * @date 2017-06-10
 */

namespace fk\daemon;

class Cache
{
    protected static $runtimeDirectory;

    public static function getPIDFile($group)
    {
        $dir = static::getRuntimeDirectory();
        return "$dir/$group.pid";
    }

    public static function getRuntimeDirectory()
    {
        if (!static::$runtimeDirectory) {
            static::$runtimeDirectory = dirname(__DIR__) . '/runtime';
        }
        return static::$runtimeDirectory;
    }

    public static function getFilenameOfPIDGroup($pid)
    {
        $dir = static::getRuntimeDirectory();
        return "$dir/$pid.group";
    }

    public static function lockPIDs($group, $PIDs)
    {
        static::storePIDs($group, $PIDs);
        static::storeGroup($group, $PIDs);
    }

    public static function getPIDsByGroup($name)
    {
        $filename = static::getPIDFile($name);
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $PIDs = $content ? explode(',', $content) : [];
        } else {
            $PIDs = [];
        }

        return $PIDs;
    }

    public static function clearPID()
    {
        // clear xxx.group
        $pid = posix_getpid();
        $filename = static::getFilenameOfPIDGroup($pid);
        if (!is_file($filename)) return;
        $group = file_get_contents($filename);
        unlink($filename);

        // clear xxx.pid
        $PIDs = static::getPIDsByGroup($group);
        if (false !== $key = array_search($pid, $PIDs)) {
            unset($PIDs[$key]);
            static::storePIDs($group, $PIDs);
        }
    }

    protected static function storePIDs($group, $PIDs)
    {
        $filename = static::getPIDFile($group);
        $handler = fopen($filename, 'w');
        fwrite($handler, implode(',', $PIDs));
        fclose($handler);
    }

    protected static function storeGroup($group, $PIDs)
    {
        /*
         * # 1.group
         *      default
         * Contains all
         */
        foreach ($PIDs as $pid) {
            $handler = fopen(static::getFilenameOfPIDGroup($pid), 'w');
            fwrite($handler, $group);
            fclose($handler);
        }
    }

}