<?php
require "autoload.php";

// überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, true, true))
    exit;
if (isset($_GET["archive"]) && isset($_GET["template"]))
    die("Error: Both template and archive is given. Only one of them is allowed\n");

if (!isset($_GET["veranstaltung"]) || $_GET["veranstaltung"] == "") {
    die("Error: Kein neuer Name für die Veranstaltung angegeben \n");
} else if (!is_string($_GET["veranstaltung"])) {
    die("Error: Falscher Datentyp von veranstaltung \n");
}

// Um Deadlock Protection zu umgehen, erstelle die Datenbank separat
$dbo = new \database\DatabaseOverview();
$dbc2 = $dbo->createTemplateDatabase($_GET["veranstaltung"]);
if ($dbc2 == false)
    exit;
$success = $dbo->fillTemplateDatabase($dbc2);
$dbc2->unlockDatabase();
if (!$success)
    exit;

// verbinde mit aktueller Datenbank
$dbo = new \database\DatabaseOverview();
if (isset($_GET['archive'])) {
    $dbc1 = $dbo->getArchiveDatabaseConnection($_GET['archive']);
} elseif (isset($_GET["template"])) {
    $dbc1 = $dbo->getTemplateDatabaseConnection($_GET["template"]);
} else {
    $dbc1 = $dbo->getCurrentDatabaseConnection();
}
if ($dbc1 == false)
    exit;

$dbc2 = $dbo->getTemplateDatabaseConnection($_GET["veranstaltung"]);
if ($dbc2 == false)
    exit;
$success = $dbo->lockInOrder([
    new \database\DatabaseLockHandle($dbc1, LOCK_SH),
    new \database\DatabaseLockHandle($dbc2, LOCK_EX)
]);
if (!$success)
    exit;

// kopiere Datenbank
$success = $dbo->copyDatabase($dbc1, $dbc2);
if (!$success) {
    $dbc1->unlockDatabase();
    $dbc2->unlockDatabase();
    exit;
}

$veranstaltung = $dbc2->getVeranstaltungJson();
$veranstaltung->veranstaltung = $_GET["veranstaltung"];
$success = $dbc2->setVeranstaltungJson($veranstaltung);
if (!$success) {
    $dbc1->unlockDatabase();
    $dbc2->unlockDatabase();
    exit;
}

$dbc1->unlockDatabase();
$dbc2->unlockDatabase();

echo "Success";
