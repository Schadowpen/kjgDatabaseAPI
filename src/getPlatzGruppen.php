<?php
require "autoload.php";

// Überprüfe Eingabe auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(false, false, true))
    exit;

// Verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
if ($dbc == false)
    exit;
if (!$dbc->readlockDatabase())
    exit;

// lese Datenbank
$platzGruppenString = $dbc->getPlatzGruppenString();

$dbc->unlockDatabase();

// output
header("Content-Type: application/json");
echo $platzGruppenString;
