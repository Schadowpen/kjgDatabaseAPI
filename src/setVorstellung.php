<?php
require "autoload.php";

// lese POST Body aus
$vorstellung = json_decode(file_get_contents("php://input"));
$vorstellung = deleteUnnecessaryAttributes($vorstellung, array("date", "time"));


// überprüfe Input auf Korrektheit
$correct = true;

if (!keyValid(true))
    $correct = false;
if (!checkDatabaseUsageAllowed(false, false, true))
    $correct = false;

if (!isset($vorstellung->date)) {
    echo "Error: Kein date angegeben \n";
    $correct = false;
} else if (!is_string($vorstellung->date)) {
    echo "Error: Falscher Datentyp von date \n";
    $correct = false;
} elseif (!preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", $vorstellung->date)) {
    echo "Error: date muss das Format YYYY-MM-DD haben \n";
    $correct = false;
}

if (!isset($vorstellung->time)) {
    echo "Error: Kein time angegeben \n";
    $correct = false;
} else if (!is_string($vorstellung->time)) {
    echo "Error: Falscher Datentyp von time \n";
    $correct = false;
} elseif (!preg_match("/[0-9]{2}:[0-9]{2}/", $vorstellung->time)) {
    echo "Error: time muss das Format hh:mm haben \n";
    $correct = false;
}

if (!$correct)
    exit;


// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
if ($dbc == false)
    exit;
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$vorstellungen = $dbc->getVorstellungenJson();

// füge Vorstellung in richtiger Reihenfolge hinzu
$inserted = false;
for ($i = 0; $i < count($vorstellungen); ++$i) {
    if ($vorstellungen[$i]->date == $vorstellung->date
        && $vorstellungen[$i]->time == $vorstellung->time) {
        $vorstellungen[$i] = $vorstellung;
        $inserted = true;
        break;
    } elseif (strcmp($vorstellungen[$i]->date, $vorstellung->date) >= 0
        && strcmp($vorstellungen[$i]->time, $vorstellung->time) >= 0) {
        array_splice($vorstellungen, $i, 0, array($vorstellung));
        $inserted = true;
        break;
    }
}
if (!$inserted) {
    array_push($vorstellungen, $vorstellung);
}

// schreibe Datenbank
$dbc->setVorstellungenJson($vorstellungen);
$dbc->unlockDatabase();

// gib gespeicherte Vorstellung zurück
header("Content-Type: application/json");
echo json_encode($vorstellung);
