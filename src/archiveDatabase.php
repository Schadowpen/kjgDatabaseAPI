<?php
require "autoload.php";

// überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, false, false))
    exit;

// verbinde mit aktueller Datenbank
$dbo = new \database\DatabaseOverview();
$dbc1 = $dbo->getCurrentDatabaseConnection();
if (!$dbc1->readlockDatabase())
    exit;

// verbinde mit archiv-Datenbank
$archiveName = $dbc1->getVeranstaltungJson()->veranstaltung;
$dbc2 = $dbo->createArchiveDatabaseIfNotExists($archiveName);
if (!$dbc2->writelockDatabase())
    exit;

// kopiere Datenbank
$success = $dbo->copyDatabase($dbc1, $dbc2);
if (!$success) {
    $dbc1->unlockDatabase();
    $dbc2->unlockDatabase();
    exit;
}

$dbc1->unlockDatabase();

\database\DatabaseOverview::blackData($dbc2);

$dbc2->unlockDatabase();

echo "Success";
