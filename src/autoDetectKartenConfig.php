<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(false, false, true))
    exit;


$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
if ($dbc == false)
    exit;
if (!$dbc->writelockDatabase())
    exit;

$kartenVorlageString = $dbc->getKartenVorlageString();
if ($kartenVorlageString === false) {
    $dbc->unlockDatabase();
    echo "Error: Could not read kartenVorlage\n";
    exit;
}

try {
    $pdfFile = new pdf\PdfFile(new misc\StringReader($kartenVorlageString));
    $pdfDocument = new pdf\PdfDocument($pdfFile);

    // Auto-detection
    $kartenConfig = new generation\AutoConfig($pdfDocument);

    // neue Konfiguration speichern
    $kartenConfigString = json_encode($kartenConfig);
    if ($kartenConfigString === false) {
        $dbc->unlockDatabase();
        echo "Error: Could not encode kartenConfig to JSON\n";
        exit;
    }
    $dbc->setKartenConfigString($kartenConfigString);
    $dbc->unlockDatabase();

    // output
    header("Content-Type: application/json");
    echo $kartenConfigString;

} catch (Throwable $exception) {
    $dbc->unlockDatabase();
    echo "Error: " . $exception->getMessage() . "\n" . $exception->getTraceAsString() . "\n";
    exit;
}