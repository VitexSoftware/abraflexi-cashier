#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This file is part of the CashTools for AbraFlexi package
 *
 * https://github.com/VitexSoftware/AbraFlexi-CashTools
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../vendor/autoload.php';

use AbraFlexi\Cash\Cashier;
use Ease\Shared;

// Define Application Name
\define('APP_NAME', 'AbraFlexi Cash Vacuum');

// Load configuration from .env file
Shared::init(
    [
        'ABRAFLEXI_URL',
        'ABRAFLEXI_LOGIN',
        'ABRAFLEXI_PASSWORD',
        'ABRAFLEXI_COMPANY',
        'ABRAFLEXI_CASH_FIX_SCOPE',
        'APP_DEBUG',
    ],
    file_exists(__DIR__.'/../.env') ? __DIR__.'/../.env' : null,
);

// Optional CLI parameters
$options = getopt('o::e::', ['output::','environment::','dry-run', 'scope:', 'from:', 'to:']);
$destination = \array_key_exists('o', $options) ? $options['o'] : (\array_key_exists('output', $options) ? $options['output'] : \Ease\Shared::cfg('RESULT_FILE', 'php://stdout'));

$scope = $options['scope'] ?? Shared::cfg('ABRAFLEXI_CASH_FIX_SCOPE', 'last_month');
$dryRun = \array_key_exists('dry-run', $options);

// Initialize Cashier
$cashier = new Cashier($scope);

// Enable verbose logging if APP_DEBUG
if (Shared::cfg('APP_DEBUG')) {
    $cashier->logBanner();
}

// Dry-run flag
if ($dryRun) {
    $cashier->enableDryRun();
    $cashier->addSatusMessage("Running in DRY-RUN mode. No real changes will be saved.") ;
}

// Run the fixing process
$report = $cashier->fixAll();

    $cashier->addSatusMessage('Fixing completed. Result: '.json_encode($result));

    $report['exitcode'] = $exitcode;
$written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE : 0));
$cashier->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($exitcode);
