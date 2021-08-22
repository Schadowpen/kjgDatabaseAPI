<?php
require "autoload.php";

// überprüfe Input auf Korrektheit
$correct = true;

if (!keyValid())
    $correct = false;
if (!checkDatabaseUsageAllowed(false, false, true))
    $correct = false;

if (!isset($_GET["newName"])) {
    echo "Error: Kein neuer Name angegeben \n";
    $correct = false;
} elseif (!is_string($_GET["newName"])) {
    echo "Error: Falscher Datentyp von newName \n";
    $correct = false;
}

if (!$correct)
    exit;

// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
// überprüfe, welche Datenbank zuerst gelockt werden soll
switch (strcmp($_GET["template"], $_GET["newName"])) {
    case 1:
        // erst alte Datenbank, dann neue Datenbank
        $dbc1 = $dbo->getTemplateDatabaseConnection($_GET["template"]);
        if ($dbc1 == false)
            exit;
        if (!$dbc1->writelockDatabase())
            exit;
        $dbc2 = $dbo->createTemplateDatabase($_GET["newName"]);
        if ($dbc2 == false)
            exit;
        break;
    case -1:
        // erst neue Datenbank, dann alte Datenbank
        $dbc2 = $dbo->createTemplateDatabase($_GET["newName"]);
        if ($dbc2 == false)
            exit;
        $dbc1 = $dbo->getTemplateDatabaseConnection($_GET["template"]);
        if ($dbc1 == false)
            exit;
        if (!$dbc1->writelockDatabase())
            exit;
        break;
    default:
        echo "Error: alter Name gleicht dem neuen Namen \n";
        exit;
}

// kopiere Datenbank
$success = $dbo->copyDatabase($dbc1, $dbc2);
if (!$success) {
    $dbc1->unlockDatabase();
    $dbc2->unlockDatabase();
    exit;
}

// lösche erste Datenbank
$dbo->deleteDatabase($dbc1);

// setze Namen in veranstaltung.json
$veranstaltung = $dbc2->getVeranstaltungJson();
$veranstaltung->veranstaltung = $_GET["newName"];
$dbc2->setVeranstaltungJson($veranstaltung);

$dbc2->unlockDatabase();

echo "Success";
