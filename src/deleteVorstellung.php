<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(false, false, true))
    exit;

if (!isset($_GET['date'])) {
    echo "Error: No date defined in url variables \n";
    exit;
}
$date = $_GET['date']; // automatically string

if (!isset($_GET['time'])) {
    echo "Error: No time defined in url variables \n";
    exit;
}
$time = $_GET['time']; // automatically string

// Verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
if ($dbc == false)
    exit;
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$vorstellungen = $dbc->getVorstellungenJson();

// lösche den Eingang
for ($i = count($vorstellungen) - 1; $i >= 0; $i--) {
    if ($vorstellungen[$i]->date == $date && $vorstellungen[$i]->time == $time) {
        array_splice($vorstellungen, $i, 1);
    }
}

// schreibe Datenbank
$dbc->setVorstellungenJson($vorstellungen);
$dbc->unlockDatabase();

echo "Success";
