<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, true, true))
    exit;
if (isset($_GET["archive"]) && isset($_GET["template"]))
    die("Error: Both template and archive is given. Only one of them is allowed\n");

// Verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
if (isset($_GET['archive'])) {
    $dbc = $dbo->getArchiveDatabaseConnection($_GET['archive'], true);
} elseif (isset($_GET["template"])) {
    $dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
} else {
    $dbc = $dbo->getCurrentDatabaseConnection();
}
if ($dbc == false)
    exit;
if (!$dbc->readlockDatabase())
    exit;

try {
    // Erhalte verfügbare Schriftarten
    $fonts = (new generation\KartenVorlageAnalyzer($dbc))->getAvailableFonts();
    $dbc->unlockDatabase();

    // output
    header("Content-Type: application/json");
    echo json_encode($fonts);

} catch (Throwable $exception) {
    $dbc->unlockDatabase();
    echo "Error: " . $exception->getMessage() . "\n" . $exception->getTraceAsString() . "\n";
    exit;
}