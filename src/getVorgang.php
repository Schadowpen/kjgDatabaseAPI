<?php
require "autoload.php";

// überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, true, false))
    exit;

if (!isset($_GET['nummer'])) {
    echo "Error: No Vorgang nummer defined in url variables \n";
    exit;
}
if ($_GET['nummer'] < 0) {
    echo "Error: Only positive numbers for Vorgang Nummer allowed \n";
    exit;
}

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
$dbc->unlockDatabase();

// suche gewünschten Vorgang in Vorgängen
$vorgang = null;
for ($i = 0; $i < count($vorgaenge); $i++) {
    if ($vorgaenge[$i]->nummer == $_GET['nummer']) {
        $vorgang = $vorgaenge[$i];
        break;
    }
}

// output
header("Content-Type: application/json");
$output = json_encode($vorgang);
echo $output;
