<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, false, false))
    exit;

if (!isset($_GET['nummer'])) {
    echo "Error: No Vorgang nummer defined in url variables \n";
    exit;
}
$nummer = $_GET['nummer'];
if ($nummer < 0) {
    echo "Error: Only positive numbers for Vorgang Nummer allowed \n";
    exit;
}

// Verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getCurrentDatabaseConnection();
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$vorgaenge = $dbc->getVorgaengeJson();
$platzStatusse = $dbc->getPlatzStatusseJson();

// lösche den Vorgang
for ($i = count($vorgaenge) - 1; $i >= 0; $i--) {
    if ($vorgaenge[$i]->nummer == $nummer) {
        $vorgang = $vorgaenge[$i];
        array_splice($vorgaenge, $i, 1);
    }
}

// lösche PlatzStatusse, die mit dem Vorgang verbunden sind
for ($i = count($platzStatusse) - 1; $i >= 0; $i--) {
    if (@$platzStatusse[$i]->vorgangsNr == $nummer) {
        array_splice($platzStatusse, $i, 1);
    }
}

// lösche Theaterkarte für diesen Vorgang
if (isset($vorgang) && @isset($vorgang->theaterkarte)) {
    $fileName = rawurldecode(substr($vorgang->theaterkarte, strrpos($vorgang->theaterkarte, "/") + 1));
    if (file_exists($ticketsFolder . $fileName))
        unlink($ticketsFolder . $fileName);
}

// schreibe Datenbank
$dbc->setVorgaengeJson($vorgaenge);
$dbc->setPlatzStatusseJson($platzStatusse);

$dbc->unlockDatabase();

echo "Success";
