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
$plaetze = $dbc->getPlaetzeJson();
$platzGruppen = $dbc->getPlatzGruppenJson();

// lösche die PlatzGruppe
for ($i = count($platzGruppen) - 1; $i >= 0; $i--) {
    if ($platzGruppen[$i]->id == $id) {
        $platzGruppe = $platzGruppen[$i];
        array_splice($platzGruppen, $i, 1);
    }
}
if (@$platzGruppe == null)
    die("Error: PlatzGruppe with given id does not exist \n");

// füge in Plätze an
$newPlaetze = \database\DatabaseOverview::getPlaetzeFromPlatzGruppe($platzGruppe);
$plaetze = array_merge($plaetze, $newPlaetze);

// schreibe Datenbank
$dbc->setPlaetzeJson($plaetze);
$dbc->setPlatzGruppenJson($platzGruppen);
$dbc->unlockDatabase();

// gib alle Plätze zurück
header("Content-Type: application/json");
echo json_encode($plaetze);
