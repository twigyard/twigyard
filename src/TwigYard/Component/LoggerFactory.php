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
     * @var int
     */
    private $logOnLevel;

    /**
     * @var bool|null
     */
    private $logRotationEnabled;

    /**
     * @var int|null
     */
    private $maxFiles;

    /**
     * @var string|null
     */
    private $logglyToken;

    /**
     * @var array|null
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
     * @throws InvalidApplicationConfigException
     */
    public function __construct(
        string $appRoot,
        string $logDir,
        string $logOnLevel,
        ?bool $logRotationEnabled,
        ?int $maxFiles,
        ?string $logglyToken,
        ?array $logglyTags
    ) {
        $this->checkValidity($logRotationEnabled, $maxFiles, $logglyToken, $logglyTags);

        $this->appRoot = $appRoot;
        $this->logDir = $logDir;
        $this->logOnLevel = (int) constant('Monolog\Logger::' . strtoupper($logOnLevel));
        $this->logRotationEnabled = $logRotationEnabled;
        $this->maxFiles = $maxFiles;
        $this->logglyToken = $logglyToken;
        $this->logglyTags = $logglyTags;
    }

    /**
     * @param bool|null $logRotationEnabled
     * @param int|null $maxFiles
     * @param string|null $logglyToken
     * @param array|null $logglyTags
     * @throws InvalidApplicationConfigException
     */
    private function checkValidity(
        ?bool $logRotationEnabled,
        ?int $maxFiles,
        ?string $logglyToken,
        ?array $logglyTags
    ): void {
        if (isset($logRotationEnabled) && !isset($maxFiles)) {
            throw new InvalidApplicationConfigException(
                'If there is log_rotation_enabled defined in the configuration, log_max_files has to be defined too.'
            );
        }

        if (isset($maxFiles) && !isset($logRotationEnabled)) {
            throw new InvalidApplicationConfigException(
                'If there is log_max_files defined in the configuration, log_rotation_enabled has to be defined too.'
            );
        }

        if (isset($logglyToken) && !isset($logglyTags)) {
            throw new InvalidApplicationConfigException(
                'If there is loggly_token defined in the configuration, loggly_tags has to be defined too.'
            );
        }

        if (isset($logglyTags) && !isset($logglyToken)) {
            throw new InvalidApplicationConfigException(
                'If there is loggly_tags defined in the configuration, loggly_token has to be defined too.'
            );
        }
    }

    /**
     * @param string|null $channelName
     * @throws \Exception
     * @return LoggerInterface
     */
    public function getLogger(?string $channelName): LoggerInterface
    {
        FileSystem::createDir($this->appRoot . '/' . $this->logDir);
        $logger = new Logger($channelName ?: 'NULL');
        if ($this->logRotationEnabled && $this->maxFiles) {
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

            if ($this->logglyTags) {
                foreach ($this->logglyTags as $tag) {
                    $loggerHandler->addTag($tag);
                }
            }

            $logger->pushHandler($loggerHandler);
        }

        return $logger;
    }
}
