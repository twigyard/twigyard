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
     * FormLoggerFactory constructor.
     * @param string $logDir
     */
    public function __construct($logDir)
    {
        $this->logDir = $logDir;
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
