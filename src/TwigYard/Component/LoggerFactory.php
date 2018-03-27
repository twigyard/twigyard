<?php

namespace TwigYard\Component;

use Monolog\Handler\LogglyHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nette\Utils\FileSystem;
use Psr\Log\LoggerInterface;
use TwigYard\Exception\InvalidApplicationConfigException;

class LoggerFactory
{
    const LOG_FILE = 'system.log';

    /**
     * @var string
     */
    private $appRoot;

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
     * @param string $appRoot
     * @param string $logDir
     * @param string $logOnLevel
     * @param bool $logRotationEnabled
     * @param int|null $maxFiles
     * @param string|null $logglyToken
     * @param array|null $logglyTags
     */
    public function __construct(
        string $appRoot,
        string $logDir,
        string $logOnLevel,
        bool $logRotationEnabled,
        ?int $maxFiles = 0,
        ?string $logglyToken = null,
        ?array $logglyTags = []
    ) {
        $this->appRoot = $appRoot;
        $this->logDir = $logDir;
        $this->logOnLevel = constant('Monolog\Logger::' . strtoupper($logOnLevel));
        $this->logRotationEnabled = $logRotationEnabled;
        $this->maxFiles = $maxFiles;
        $this->logglyToken = $logglyToken;
        $this->logglyTags = $logglyTags;

        $this->checkValidity();
    }

    /**
     * @throws InvalidApplicationConfigException
     */
    private function checkValidity(): void
    {
        if (isset($this->logRotationEnabled) && !isset($this->maxFiles)) {
            throw new InvalidApplicationConfigException(
                'If there is log_rotation_enabled defined in the configuration, log_max_files has to be defined too.'
            );
        }

        if (isset($this->maxFiles) && !isset($this->logRotationEnabled)) {
            throw new InvalidApplicationConfigException(
                'If there is log_max_files defined in the configuration, log_rotation_enabled has to be defined too.'
            );
        }

        if (isset($this->logglyToken) && !isset($this->logglyTags)) {
            throw new InvalidApplicationConfigException(
                'If there is loggly_token defined in the configuration, loggly_tags has to be defined too.'
            );
        }

        if (isset($this->logglyTags) && !isset($this->logglyToken)) {
            throw new InvalidApplicationConfigException(
                'If there is loggly_tags defined in the configuration, loggly_token has to be defined too.'
            );
        }
    }

    /**
     * @param string|null $channelName
     * @return LoggerInterface
     */
    public function getLogger(?string $channelName): LoggerInterface
    {
        FileSystem::createDir($this->appRoot . '/' . $this->logDir);
        $logger = new Logger($channelName);
        if ($this->logRotationEnabled) {
            $logger->pushHandler(
                new RotatingFileHandler(
                    $this->appRoot . '/' . $this->logDir . '/' . self::LOG_FILE,
                    $this->maxFiles,
                    $this->logOnLevel
                )
            );
        } else {
            $logger->pushHandler(
                new StreamHandler(
                    $this->appRoot . '/' . $this->logDir . '/' . self::LOG_FILE,
                    $this->logOnLevel
                )
            );
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
