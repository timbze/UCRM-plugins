<?php

declare(strict_types=1);


namespace SmsNotifier;


use Psr\Log\LogLevel;
use SmsNotifier\Facade\ApiNotifierFacade;
use SmsNotifier\Factory\NotificationDataFactory;
use SmsNotifier\Service\OptionsManager;
use SmsNotifier\Service\PluginDataValidator;
use SmsNotifier\Service\Logger;

class Plugin
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var PluginDataValidator
     */
    private $pluginDataValidator;

    /**
     * @var ApiNotifierFacade
     */
    private $notifierFacade;

    /**
     * @var NotificationDataFactory
     */
    private $notificationDataFactory;

    public function __construct(
        Logger                  $logger,
        OptionsManager          $optionsManager,
        PluginDataValidator     $pluginDataValidator,
        ApiNotifierFacade       $notifierFacade,
        NotificationDataFactory $notificationDataFactory
    )
    {
        $this->logger = $logger;
        $this->optionsManager = $optionsManager;
        $this->pluginDataValidator = $pluginDataValidator;
        $this->notifierFacade = $notifierFacade;
        $this->notificationDataFactory = $notificationDataFactory;
    }

    public function run(): void
    {
        if (PHP_SAPI === 'fpm-fcgi') {
            $this->logger->debug('Generic API notifier over HTTP started');
            $this->processHttpRequest();
            $this->logger->debug('HTTP request processing ended.');
        } elseif (PHP_SAPI === 'cli') {
            $this->logger->info('Generic API notifier over CLI started');
            $this->processCli();
            $this->logger->info('CLI process ended.');
        } else {
            throw new \UnexpectedValueException('Unknown PHP_SAPI type: ' . PHP_SAPI);
        }
    }

    private function processCli(): void
    {
        if ($this->pluginDataValidator->validate()) {
            $this->logger->info('Validating config');
            $this->optionsManager->load();
        }
    }

    private function processHttpRequest(): void
    {
        $pluginData = $this->optionsManager->load();
        if ($pluginData->logging_level) {
            $this->logger->setLogLevelThreshold(LogLevel::DEBUG);
        }

        $userInput = file_get_contents('php://input');
        if (! $userInput) {
            $this->logger->warning('no input');

            return;
        }

        $jsonData = @json_decode($userInput, true, 10);
        if (! isset($jsonData['uuid'])) {
            $this->logger->error('JSON error: ' . json_last_error_msg());

            return;
        }

        $notification = $this->notificationDataFactory->getObject($jsonData);
        if ($notification->changeType === 'test') {
            $this->logger->info('Webhook test successful.');

            return;
        }
        if (! $notification->clientId) {
            $this->logger->warning('No client specified, cannot notify them.');

            return;
        }

        try {
            $this->notifierFacade->notify($notification);
            $this->cleanLog();
        } catch (\InvalidArgumentException $ex) {
            $this->logger->debug($ex->getMessage());
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            $this->logger->warning('Error stack: ' . $ex->getTraceAsString());
        }

    }

    private function cleanLog(): void {
        $dir = Logger::logFileDirectory;
        $file = Logger::logFileName;
        $ext = Logger::logFileExtension;
        $logPath = "$dir/$file.$ext";

        $mb = 1000000;
        $size = filesize($logPath);
        // never trim file if it's less than 1Mb
        if ($size < $mb) return;

        $mbSize = $size / $mb;
        $this->logger->info("Cleaning up log, size is $mbSize MB");
        $this->trimLogToLength($logPath, 10000);
    }

    /**
     * Idea from [here](https://stackoverflow.com/a/45090213), but modified
     */
    private function trimLogToLength($path, $numRowsToKeep) {
        $file = file($path);
        if (!$file) return;

        // if this file is long enough that we should be truncating it
        $countFile = count($file);
        if ($countFile > $numRowsToKeep) {
            // figure out the rows we want to keep
            $dataRowsToKeep = array_slice($file,$countFile-$numRowsToKeep, $numRowsToKeep);
            file_put_contents($path, implode($dataRowsToKeep));
        }
    }
}
