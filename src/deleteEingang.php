<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(false, false, true))
    exit;

if (!isset($_GET['id'])) {
    echo "Error: No Eingang id defined in url variables \n";
    exit;
}
$id = $_GET['id'];
if ($id < 0) {
    echo "Error: Only positive id for Eingang id allowed \n";
    exit;
}

// Verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
if ($dbc == false)
    exit;
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$eingaenge = $dbc->getEingaengeJson();
$plaetze = $dbc->getPlaetzeJson();
$platzGruppen = $dbc->getPlatzGruppenJson();

// lösche den Eingang
for ($i = count($eingaenge) - 1; $i >= 0; $i--) {
    if ($eingaenge[$i]->id == $id) {
        $eingang = $eingaenge[$i];
        array_splice($eingaenge, $i, 1);
    }
}

// lösche Referenzen auf den Eingang
for ($i = 0 ; $i < count($eingaenge); ++$i) {
    if (@$eingaenge[$i]->eingang == $id) {
        unset($eingaenge[$i]->eingang);
    }
}
for ($i = 0 ; $i < count($plaetze); ++$i) {
    if (@$plaetze[$i]->eingang == $id) {
        unset($plaetze[$i]->eingang);
    }
}
for ($i = 0 ; $i < count($platzGruppen); ++$i) {
    if (@$platzGruppen[$i]->eingang == $id) {
        unset($platzGruppen[$i]->eingang);
    }
}

// schreibe Datenbank
$dbc->setEingaengeJson($eingaenge);
$dbc->setPlaetzeJson($plaetze);
$dbc->setPlatzGruppenJson($platzGruppen);

$dbc->unlockDatabase();

echo "Success";
