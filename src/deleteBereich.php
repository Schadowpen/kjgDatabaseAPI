<?php
require_once "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(false, false, true))
    exit;

if (!isset($_GET['id'])) {
    echo "Error: No id defined in url variables \n";
    exit;
}
$id = $_GET['id'];
if ($id < 0) {
    echo "Error: Only positive numbers for id allowed \n";
    exit;
}

// Verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
if ($dbc === false)
    exit;
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$bereiche = $dbc->getBereicheJson();

// lösche den Bereich
for ($i = count($bereiche) - 1; $i >= 0; --$i) {
    if ($bereiche[$i]->id == $id) {
        array_splice($bereiche, $i, 1);
    }
}

// schreibe Datenbank
$dbc->setBereicheJson($bereiche);

$dbc->unlockDatabase();

echo "Success";