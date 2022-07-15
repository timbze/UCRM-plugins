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

function getDocuments(array $params, UcrmApi $api): array
{
    // make sure the dates are in YYYY-MM-DD format
    if ($params['createdDateFrom']) {
        $params['createdDateFrom'] = new \DateTimeImmutable($params['createdDateFrom']);
        $params['createdDateFrom'] = $params['createdDateFrom']->format('Y-m-d');
    }
    if ($params['createdDateTo']) {
        $params['createdDateTo'] = new \DateTimeImmutable($params['createdDateTo']);
        $params['createdDateTo'] = $params['createdDateTo']->format('Y-m-d');
    }

    $invoices = $api->get('invoices', $params);
    $payments = $api->get('payments', $params);
    $documents = [];
    foreach ($invoices as $invoice){
        $documents[] = [
            'createdDate' => $invoice['createdDate'],
            'number' => $invoice['id'],
            'clientCompanyName' => $invoice['clientCompanyName'],
            'clientFirstName' => $invoice['clientFirstName'],
            'clientLastName' => $invoice['clientLastName'],
            'total' => $invoice['total'],
            'type' => "invoice",
        ];
    }

    foreach ($payments as $payment) {
        $client = $api->get('clients/'.$payment['clientId']);
        $documents[] = [
            'createdDate' => $payment['createdDate'],
            'number' => $payment['id'],
            'clientCompanyName' => $client['companyName'],
            'clientFirstName' => $client['firstName'],
            'clientLastName' => $client['lastName'],
            'total' => $payment['amount'],
            'type' => "payment",
        ];
    }

    usort($documents, function ($item1, $item2) {
        return $item2['createdDate'] <=> $item1['createdDate'];
    });

    return $documents;
}

// Process submitted form.
if (array_key_exists('organization', $_GET) && array_key_exists('since', $_GET) && array_key_exists('until', $_GET)) {
    $parameters = [
        'organizationId' => $_GET['organization'],
        'createdDateFrom' => $_GET['since'],
        'createdDateTo' => $_GET['until'],
    ];

    $documents = getDocuments($parameters, $api);
} else {
    // first load
    $parameters = [
        'organizationId' => 1, // first company
        'createdDateFrom' => date('Y-m-d'), // today
        'createdDateTo' => date('Y-m-d'), // today
    ];

    $documents = getDocuments($parameters, $api);
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
