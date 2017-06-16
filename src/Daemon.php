<?php

/**
 * @author Felix Huang <yelfivehuang@gmail.com>
 * @date 2017-06-09
 */

namespace fk\daemon;

class Daemon
{

    /**
     * @var int How many child processes to fork
     */
    protected $concurrency;

    protected $maxConcurrency = 100;

    protected $runAtDaemon;

    protected $groupName;

    public function __construct(int $concurrency = 1, bool $runAtDaemon = true, string $group = 'default')
    {
        if ($concurrency > $this->maxConcurrency) {
            throw new \Exception("Concurrency cannot be over $this->maxConcurrency");
        }
        $this->concurrency = $concurrency;
        $this->runAtDaemon = $runAtDaemon;
        $this->groupName = $group;
    }

    /**
     * To start a daemon for a callable
     * @param callable $callback
     * @throws \Exception
     */
    public function guard(callable $callback)
    {
        if ($this->fork()) return;
        Signal::register();

        // ready to loop
        while (true) {
            call_user_func($callback);
            $this->integrityChecking();
            Signal::dispatch();
        }
    }

    /**
     * Check if child sibling processes exited
     */
    protected function integrityChecking()
    {
        // TODO: Check and keep the child process always online
        // TODO: restart after child process exited
    }

    public static function kill($groupName)
    {
        $PIDs = Cache::getPIDsByGroup($groupName);
        Signal::send($PIDs, SIGABRT);
    }

    /**
     * @return bool Whether current process is parent process or children
     * @throws \Exception
     */
    protected function fork(): bool
    {
        if (!$this->runAtDaemon) return false;

        $count = $this->concurrency;
        $PIDs = [];
        while ($count--) {
            $childPID = pcntl_fork();
            if ($childPID < 0) {
                throw new \Exception('Failed to fork current process, aborting');
            } else if ($childPID <= 0) {
                // It is forked child
                return false;
                break;
            } else {
                $PIDs[] = $childPID;
            }
        }

        // Write the $PIDs for future usage
        Cache::lockPIDs($this->groupName, $PIDs);

        return true;
    }

    /**
     * Fire a command, and returns the exit code and message
     * @param string $cmd
     * @return array
     *  [
     *      int exitCode
     *      array $output
     *  ]
     */
    public static function fireCommand($cmd): array
    {
        if (is_array($cmd)) {
            /*
             * [ls, -l]
             */
        }

        if (strpos($cmd, '2>&1') === false) {
            $cmd = "$cmd 2>&1";
        }

        exec($cmd, $output, $exitCode);

        return [
            $exitCode,
            $output
        ];
    }

}

/*
 * Daemon:
 *  Daemon::run(function () {
 *      if (Queue::execute()) {
 *          Queue::shift();
 *          return true;
 *      } else {
 *          return false; // Which will re-execute the cmd in the queue
 *      }
 *  });
 *
 *  function run ()
 *  {
 *      1. fork
 *      2. execute
 *      3. delete
 *      4. expires failed member over allowed threshold
 *  }
 *
 * Yii2:
 *
 *  php yii queue/start
 *  Yii::$app->queue->start();
 *
 *  class QueueController extends sth
 *  {
 *      public function actionStart()
 *      {
 *          //
 *          Daemon::run(function () {
 *          });
 *      }
 *  }
 *
 *
 *
 */