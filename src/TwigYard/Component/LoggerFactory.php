<?php

namespace TwigYard\Component;

use Monolog\Handler\LogglyHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nette\Utils\FileSystem;

class LoggerFactory
{
    const LOG_FILE = 'system.log';
    
    /**
     * @var string
     */
    private $logDir;

    /**
     * @var string
     */
    private $logOnLevel;

    /**
     * @var string
     */
    private $logRotationEnabled;

    /**
     * @var string
     */
    private $maxFiles;

    /**
     * @var string
     */
    private $logglyToken;

    /**
     * @var array
     */
    private $logglyTags;

    /**
     * LoggerFactory constructor.
     * @param string $logDir
     * @param string $logOnLevel
     * @param bool $logRotationEnabled
     * @param int $maxFiles
     * @param string $logglyToken
     * @param array $logglyTags
     */
    public function __construct(
        $logDir,
        $logOnLevel,
        $logRotationEnabled,
        $maxFiles = 0,
        $logglyToken = null,
        $logglyTags = []
    ) {
        $this->logDir = $logDir;
        $this->logOnLevel = $logOnLevel;
        $this->logRotationEnabled = $logRotationEnabled;
        $this->maxFiles = $maxFiles;
        $this->logglyToken = $logglyToken;
        $this->logglyTags = $logglyTags;
    }

    /**
     * @param string $channelName
     * @return \Monolog\Logger
     */
    public function getLogger($channelName)
    {
        FileSystem::createDir($this->logDir);
        $logger = new Logger($channelName);
        if ($this->logRotationEnabled) {
            $logger->pushHandler(
                new RotatingFileHandler($this->logDir . '/' . self::LOG_FILE, $this->maxFiles, $this->logOnLevel)
            );
        } else {
            $logger->pushHandler(new StreamHandler($this->logDir . '/' . self::LOG_FILE, $this->logOnLevel));
        }

        if ($this->logglyToken !== null) {
            $loggerHandler = new LogglyHandler($this->logglyToken, $this->logOnLevel);
            $loggerHandler->addTag($channelName);
            foreach ($this->logglyTags as $tag) {
                $loggerHandler->addTag($tag);
            }
            $logger->pushHandler($loggerHandler);
        }

        return $logger;
    }
}
