<?php

declare(strict_types=1);

// This contract protects the canonical CreativeSuite install/upgrade anchor.
$root = dirname(__DIR__, 2);
$controllerPath = $root . '/app/extensions/CreativeSuite/System/Http/Controllers/CreativeSuiteDocumentController.php';

function failCreativeSuiteOwnershipContract(string $message): never
{
    fwrite(STDERR, "CreativeSuite document ownership contract failed: {$message}\n");
    exit(1);
}

$controller = file_get_contents($controllerPath);
if ($controller === false) {
    failCreativeSuiteOwnershipContract('unable to read CreativeSuiteDocumentController.php');
}

if (str_contains($controller, 'CreativeSuiteDocument::findOrFail(')) {
    failCreativeSuiteOwnershipContract('controller still performs unscoped document lookups');
}

if (str_contains($controller, 'show(CreativeSuiteDocument $document)')) {
    failCreativeSuiteOwnershipContract('show() still relies on unscoped route-model binding');
}

if (! str_contains($controller, "where('user_id', auth()->id())")) {
    failCreativeSuiteOwnershipContract('controller does not constrain documents to the authenticated owner');
}

if (substr_count($controller, '$this->ownedDocument(') < 5) {
    failCreativeSuiteOwnershipContract('not every read/write action resolves through the owned document helper');
}

if (! str_contains($controller, '$newDocument->user_id = auth()->id();')) {
    failCreativeSuiteOwnershipContract('duplicated documents do not explicitly retain authenticated ownership');
}

echo "CreativeSuite document ownership contract passed.\n";
