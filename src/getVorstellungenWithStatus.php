<?php
require "autoload.php";

// überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, true, false))
    exit;

// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
if (isset($_GET['archive'])) {
    $dbc = $dbo->getArchiveDatabaseConnection($_GET['archive'], true);
} else {
    $dbc = $dbo->getCurrentDatabaseConnection();
}
if ($dbc == false)
    exit;
if (!$dbc->readlockDatabase())
    exit;

// lese Datenbank
$vorstellungen = $dbc->getVorstellungenJson();
$platzStatusse = $dbc->getPlatzStatusseJson();

$dbc->unlockDatabase();

// lege in jeder Vorstellung für jeden zugehörigen PlatzStatus ein Objekt an.
for ($i = 0; $i < count($platzStatusse); $i++) {
    for ($k = 0; $k < count($vorstellungen); $k++) {
        if (strcmp($platzStatusse[$i]->date, $vorstellungen[$k]->date) == 0
            && strcmp($platzStatusse[$i]->time, $vorstellungen[$k]->time) == 0) {
            /** @var string Parametername für diesen PlatzStatus */
            $id = $platzStatusse[$i]->block . "," . $platzStatusse[$i]->reihe . $platzStatusse[$i]->platz;
            $temp = (object)["status" => $platzStatusse[$i]->status];
            if (@$platzStatusse[$i]->vorgangsNr != null)
                $temp->vorgangsNr = $platzStatusse[$i]->vorgangsNr;
            $vorstellungen[$k]->$id = $temp;
            break;
        }
    }
}

// output
header("Content-Type: application/json");
$output = json_encode($vorstellungen);
echo $output;
