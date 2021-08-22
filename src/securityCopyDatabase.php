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
$archiveName = "SecurityCopy_" . gmdate("Y-m-d_H-i", time()) . "_GMT_" . $dbc1->getVeranstaltungJson()->veranstaltung;
$dbc2 = $dbo->createArchiveDatabaseIfNotExists($archiveName);
if ($dbc2 === false) {
    $dbc1->unlockDatabase();
    exit;
}

// kopiere Datenbank
$success = $dbo->copyDatabase($dbc1, $dbc2);
if (!$success) {
    $dbc1->unlockDatabase();
    $dbc2->unlockDatabase();
    exit;
}

$dbc1->unlockDatabase();
$dbc2->unlockDatabase();

echo "Success";
