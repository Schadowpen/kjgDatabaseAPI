<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
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
$vorgaenge = $dbc->getVorgaengeJson();
$platzStatusse = $dbc->getPlatzStatusseJson();

$dbc->unlockDatabase();

// Füge zu jedem Vorgang Zusatzinformation hinzu
for ($i = 0; $i < count($vorgaenge); $i++) {
    $vorgaenge[$i]->anzahlPlaetze = 0;
    $vorgaenge[$i]->vorstellungen = [];

    for ($k = 0; $k < count($platzStatusse); $k++) {
        if (@$platzStatusse[$k]->vorgangsNr == $vorgaenge[$i]->nummer) {
            $vorgaenge[$i]->anzahlPlaetze++;
            $vorstellung = (object)["date" => $platzStatusse[$k]->date, "time" => $platzStatusse[$k]->time];
            $vorstellungExists = false;
            for ($j = 0; $j < count($vorgaenge[$i]->vorstellungen); $j++) {
                if ($vorgaenge[$i]->vorstellungen[$j]->date == $vorstellung->date
                    && $vorgaenge[$i]->vorstellungen[$j]->time == $vorstellung->time)
                    $vorstellungExists = true;
            }
            if (!$vorstellungExists)
                array_push($vorgaenge[$i]->vorstellungen, $vorstellung);
        }
    }
}

// output
header("Content-Type: application/json");
$output = json_encode($vorgaenge);
echo $output;
