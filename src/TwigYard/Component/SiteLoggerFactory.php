<?php

namespace TwigYard\Component;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nette\Utils\FileSystem;

class SiteLoggerFactory
{
    const CHANNEL = 'form';

    /**
     * @var string
     */
    private $logDir;

    /**
     * @var string
     */
    private $logOnLevel;

    /**
     * FormLoggerFactory constructor.
     * @param string $logDir
     * @param string $logOnLevel
     */
    public function __construct($logDir, $logOnLevel)
    {
        $this->logDir = $logDir;
        $this->logOnLevel = $logOnLevel;
    }

    /**
     * @param string $siteDir
     * @param string $logFile
     * @return \Monolog\Logger
     */
    public function getFormLogger($siteDir, $logFile)
    {
        $logDir = $siteDir . '/' . $this->logDir;
        FileSystem::createDir($logDir);
        $logger = new Logger(self::CHANNEL);
        $logger->pushHandler(new StreamHandler($logDir . '/' . $logFile, Logger::INFO));
        return $logger;
    }
}
