<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(false, false, true))
    exit;

if (!isset($_GET['id'])) {
    echo "Error: No PlatzGruppe id defined in url variables \n";
    exit;
}
$id = $_GET['id'];
if ($id < 0) {
    echo "Error: Only positive id for PlatzGruppe id allowed \n";
    exit;
}

// Verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$platzGruppen = $dbc->getPlatzGruppenJson();

// lösche die PlatzGruppe
for ($i = count($platzGruppen) - 1; $i >= 0; $i--) {
    if ($platzGruppen[$i]->id == $id) {
        array_splice($platzGruppen, $i, 1);
    }
}

// schreibe Datenbank
$dbc->setPlatzGruppenJson($platzGruppen);
$dbc->unlockDatabase();

echo "Success";
