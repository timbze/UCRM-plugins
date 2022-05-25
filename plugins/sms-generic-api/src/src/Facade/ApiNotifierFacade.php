<?php

declare(strict_types=1);

namespace SmsNotifier\Facade;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use SmsNotifier\Data\NotificationData;
use SmsNotifier\Data\PluginData;
use SmsNotifier\Factory\MessageTextFactory;
use SmsNotifier\Service\Logger;
use SmsNotifier\Service\OptionsManager;
use SmsNotifier\Service\SmsNumberProvider;

class ApiNotifierFacade extends AbstractMessageNotifierFacade {

    /** @var PluginData */
    private $pluginData;

    public function __construct(
        Logger $logger,
        MessageTextFactory $messageTextFactory,
        SmsNumberProvider $smsNumberProvider,
        OptionsManager $optionsManager
    )
    {
        parent::__construct($logger, $messageTextFactory, $smsNumberProvider);
        // load config data
        $this->pluginData = $optionsManager->load();
    }

    /**
     * Send message through the API
     * @throws GuzzleException
     */
    protected function sendMessage(
        NotificationData $notificationData,
        string $clientSmsNumber,
        string $messageBody
    ): void
    {
        $this->logger->debug(sprintf('Sending: %s', $messageBody));

        $apiClient = new Client();
        $headers = ['Content-Type' => 'application/json'];
        if (!empty($this->pluginData->apiAuthCode))
            $headers = ['Authorization' => $this->pluginData->apiAuthCode];
        $request = new Request('POST',
            $this->pluginData->apiAddress,
            $headers,
            $this->createPostBody($messageBody, $clientSmsNumber)
        );

        try {
            $apiClient->send($request);
            $this->logger->debug("SMS to $clientSmsNumber was sent");
        } catch (GuzzleException $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            $this->logger->warning("API error code: $code, Msg: $msg");
            throw $e;
        }
    }

    private function createPostBody(string $messageBody, string $clientSmsNumber): string {
        $output = $this->pluginData->apiMessageTemplate;
        $cn = '%%clientNumber%%';
        $sm = '%%smsMessage%%';
        if (!(strpos($output, $cn) > -1)) throw new \UnexpectedValueException("The apiMessageTemplate MUST contain $cn");
        if (!(strpos($output, $sm) > -1)) throw new \UnexpectedValueException("The apiMessageTemplate MUST contain $sm");

        $output = str_replace($cn, $clientSmsNumber, $output);
        $output = str_replace($sm, $messageBody, $output);

        $this->logger->debug("POST body is: $output");
        return $output;
    }
}
