<?php
require "autoload.php";

// Überprüfe Input
if (!checkDatabaseUsageAllowed(true, true, false))
    exit;

// verbinde mit Datenbank
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
$veranstaltung = $dbc->getVeranstaltungJson();
$plaetze = $dbc->getPlaetzeJson();
$bereiche = $dbc->getBereicheJson();

$dbc->unlockDatabase();

// führe zu einem Objekt zusammen
$veranstaltung->plaetze = $plaetze;
$veranstaltung->bereiche = $bereiche;

// output
header("Content-Type: application/json");
$output = json_encode($veranstaltung);
echo $output;
