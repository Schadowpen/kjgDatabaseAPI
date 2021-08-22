<?php
require "autoload.php";

// Überprüfe Eingabe auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, true, false))
    exit;

// Verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
if (isset($_GET['archive'])) {
    $dbc = $dbo->getArchiveDatabaseConnection($_GET['archive'], true);
} else {
    $dbc = $dbo->getCurrentDatabaseConnection();
}
if ($dbc == false)
    exit;
if (!$dbc->readlockDatabase())
    exit;

// lese Datenbank
$platzStatusseString = $dbc->getPlatzStatusseString();

$dbc->unlockDatabase();

// output
header("Content-Type: application/json");
echo $platzStatusseString;
