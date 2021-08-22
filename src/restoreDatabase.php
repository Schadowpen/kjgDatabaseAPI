<?php
require "autoload.php";

// überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(false, true, false))
    exit;

// verbinde mit aktueller Datenbank
$dbo = new \database\DatabaseOverview();
$dbc1 = $dbo->getCurrentDatabaseConnection();
if ($dbc1 == false)
    exit;
if (!$dbc1->writelockDatabase())
    exit;
$archiveName = $dbc1->getVeranstaltungJson()->veranstaltung;

// verbinde mit Archiv zum archivieren der aktuellen Datenbank und zum laden der neuen Datenbank
$dbc2 = $dbo->createArchiveDatabaseIfNotExists($archiveName);
if ($dbc2 == false)
    exit;
$dbc3 = $dbo->getArchiveDatabaseConnection($_GET["archive"]);
if ($dbc3 == false)
    exit;
$dblHandles = array(
    new \database\DatabaseLockHandle($dbc2, LOCK_EX),
    new \database\DatabaseLockHandle($dbc3, LOCK_SH)
);
if (!$dbo->lockInOrder($dblHandles))
    exit;

// kopiere in Archiv
$success = $dbo->copyDatabase($dbc1, $dbc2);
if (!$success) {
    $dbc1->unlockDatabase();
    $dbc2->unlockDatabase();
    $dbc3->unlockDatabase();
    exit;
}

// schwärze Daten
$success = \database\DatabaseOverview::blackData($dbc2);
$success = $dbc2->unlockDatabase() && $success;
if (!$success) {
    $dbc1->unlockDatabase();
    $dbc3->unlockDatabase();
    exit;
}

// kopiere Datenbank
$success = $dbo->copyDatabase($dbc3, $dbc1);
if (!$success) {
    $dbc1->unlockDatabase();
    $dbc3->unlockDatabase();
    exit;
}

$dbc1->unlockDatabase();
$dbc3->unlockDatabase();

echo "Success";
