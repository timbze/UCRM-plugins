<?php

declare(strict_types=1);

use App\Service\TemplateRenderer;
use Ubnt\UcrmPluginSdk\Security\PermissionNames;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;
use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

// Retrieve API connection.
$api = UcrmApi::create();

// Ensure that user is logged in and has permission to view invoices.
$security = UcrmSecurity::create();
$user = $security->getUser();
if (! $user || $user->isClient || ! $user->hasViewPermission(PermissionNames::BILLING_INVOICES)) {
    \App\Http::forbidden();
}

// Process submitted form.
if (array_key_exists('organization', $_GET) && array_key_exists('since', $_GET) && array_key_exists('until', $_GET)) {
    $parameters = [
        'organizationId' => $_GET['organization'],
        'createdDateFrom' => $_GET['since'],
        'createdDateTo' => $_GET['until'],
        'order' => 'createdDate',
        'direction' => 'DESC',
    ];

    // make sure the dates are in YYYY-MM-DD format
    if ($parameters['createdDateFrom']) {
        $parameters['createdDateFrom'] = new \DateTimeImmutable($parameters['createdDateFrom']);
        $parameters['createdDateFrom'] = $parameters['createdDateFrom']->format('Y-m-d');
    }
    if ($parameters['createdDateTo']) {
        $parameters['createdDateTo'] = new \DateTimeImmutable($parameters['createdDateTo']);
        $parameters['createdDateTo'] = $parameters['createdDateTo']->format('Y-m-d');
    }

    $invoices = $api->get('invoices', $parameters);
    $payments = $api->get('payments', $parameters);
    $documents = [];
    foreach ($invoices as $invoice){
        $documents[] = [
            'createdDate' => $invoice['createdDate'],
            'number' => $invoice['number'],
            'clientCompanyName' => $invoice['clientCompanyName'],
            'clientFirstName' => $invoice['clientFirstName'],
            'clientLastName' => $invoice['clientLastName'],
            'total' => $invoice['total'],
            'type' => "invoice",
        ];
    }

    foreach ($payments as $payment){
        $documents[] = [
            'createdDate' => $payment['createdDate'],
            'number' => $payment['id'],
            'clientCompanyName' => "",// $payment['clientCompanyName'],
            'clientFirstName' => "",//$payment['clientFirstName'],
            'clientLastName' => "",//$payment['clientLastName'],
            'total' => $payment['amount'],
            'type' => "payment",
        ];
    }

//    krsort($documents);

} else {
    // first load
    $parameters = [
        'organizationId' => 1, // first company
        'createdDateFrom' => date('Y-m-d'), // today
        'createdDateTo' => date('Y-m-d'), // today
        'order' => 'createdDate',
        'direction' => 'DESC',
    ];

    $documents = $api->get('invoices', $parameters);
}

// Render form.
$organizations = $api->get('organizations');

$optionsManager = UcrmOptionsManager::create();

$renderer = new TemplateRenderer();
$renderer->render(
    __DIR__ . '/templates/form.php',
    [
        'organizations' => $organizations,
        'ucrmPublicUrl' => $optionsManager->loadOptions()->ucrmPublicUrl,
        'documents' => $documents ?? []
    ]
);
