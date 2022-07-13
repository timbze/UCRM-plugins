<?php

declare(strict_types=1);


namespace SmsNotifier\Service;

use Psr\Log\LogLevel;

class Logger extends \Katzgrau\KLogger\Logger
{
    const logFileDirectory = "data";
    const logFileName = "plugin";
    const logFileExtension = "log";

    private const DEFAULT_LEVEL = LogLevel::INFO; // now configurable in manifest
    private const AVAILABLE_LEVELS = [
     LogLevel::EMERGENCY,
     LogLevel::ALERT,
     LogLevel::CRITICAL,
     LogLevel::ERROR,
     LogLevel::WARNING,
     LogLevel::NOTICE,
     LogLevel::INFO,
     LogLevel::DEBUG,
    ];

    public function __construct($level = null)
    {
        parent::__construct(
            self::logFileDirectory,
            self::DEFAULT_LEVEL,
            [
                'extension' => self::logFileExtension,
                'filename' => self::logFileName,
            ]
        );
        if ($level) {
            $this->setLogLevelThreshold($level);
        }
    }

    /**
     * @param mixed $level
     * @param mixed $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if (!is_string($message)) {
            $message = var_export($message,true);
        }
        return parent::log($level, $message, $context);
    }

    private function validateLevel($level, $defaultLevel): string
    {
        if (in_array($level, self::AVAILABLE_LEVELS, true)) {
            return $level;
        }
        return $defaultLevel;
    }

    public function setLogLevelThreshold($logLevelThreshold): void
    {
        $logLevelThreshold = $this->validateLevel($logLevelThreshold, self::DEFAULT_LEVEL);
        parent::setLogLevelThreshold($logLevelThreshold);
        if ($logLevelThreshold != self::DEFAULT_LEVEL)
            $this->notice('Logging level set to:' . $logLevelThreshold);
    }
}
