<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(false, false, true))
    exit;

if (!isset($_GET['block'])) {
    echo "Error: No block defined in url variables \n";
    exit;
}
$block = $_GET['block'];
if (!is_string($block)) {
    echo "Error: Wrong datatype for block \n";
    exit;
}

if (!isset($_GET['reihe'])) {
    echo "Error: No reihe defined in url variables \n";
    exit;
}
$reihe = $_GET['reihe'];
if (!is_string($reihe)) {
    echo "Error: Wrong datatype for reihe \n";
    exit;
}

if (!isset($_GET['platz'])) {
    echo "Error: No platz defined in url variables \n";
    exit;
}
$platz = (int) $_GET['platz'];
if (!is_int($platz)) {
    echo "Error: Wrong datatype for platz \n";
    exit;
}

// Verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$plaetze = $dbc->getPlaetzeJson();

// lösche den Eintrag
for ($i = count($plaetze) - 1; $i >= 0; --$i) {
    if ($plaetze[$i]->block == $block
        && $plaetze[$i]->reihe == $reihe
        && $plaetze[$i]->platz == $platz) {
        array_splice($plaetze, $i, 1);
    }
}

// schreibe Datenbank
$dbc->setPlaetzeJson($plaetze);
$dbc->unlockDatabase();

echo "Success";
