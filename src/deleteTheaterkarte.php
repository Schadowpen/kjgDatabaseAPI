<?php
require 'autoload.php';

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

// finde den Vorgang
for ($i = count($vorgaenge) - 1; $i >= 0; $i--) {
    if ($vorgaenge[$i]->nummer == $nummer) {
        $vorgang = $vorgaenge[$i];
        break;
    }
}
if (!isset($vorgang)) {
    echo "Error: Vorgang mit Nummer {$nummer} nicht gefunden";
    $dbc->unlockDatabase();
    exit;
}

// lösche Theaterkarte für diesen Vorgang
if (isset($vorgang->theaterkarte)) {
    $fileName = rawurldecode(substr($vorgang->theaterkarte, strrpos($vorgang->theaterkarte, "/") + 1));
    if (file_exists($ticketsFolder . $fileName))
        unlink($ticketsFolder . $fileName);
}
unset($vorgang->theaterkarte);

// schreibe Datenbank
$dbc->setVorgaengeJson($vorgaenge);

$dbc->unlockDatabase();

// gib geänderten Vorgang zurück
header("Content-Type: application/json");
echo json_encode($vorgang);