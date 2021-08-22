<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, true, true))
    exit;
if (isset($_GET["archive"]) && isset($_GET["template"]))
    die("Error: Both template and archive is given. Only one of them is allowed\n");

// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
if (isset($_GET['archive'])) {
    $dbc = $dbo->getArchiveDatabaseConnection($_GET['archive']);
} elseif (isset($_GET["template"])) {
    $dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
} else {
    $dbc = $dbo->getCurrentDatabaseConnection();
}
if ($dbc == false)
    exit;
if (!$dbc->readlockDatabase())
    exit;

$kartenConfigString = $dbc->getKartenConfigString();
$dbc->unlockDatabase();

if ($kartenConfigString === false) {
    echo "Error: Could not read kartenConfig from database";
    exit;
}

// output
header("Content-Type: application/json");
echo $kartenConfigString;