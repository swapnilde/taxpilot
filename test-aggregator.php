<?php
require_once '../../../wp-load.php';

$aggregator = new \TaxPilot\Services\RatesAggregator();
$result = $aggregator->sync();

echo $result ? "SUCCESS: Aggregator synced and saved dynamic-rates.json.\n" : "FAILED: Aggregator did not sync.\n";
