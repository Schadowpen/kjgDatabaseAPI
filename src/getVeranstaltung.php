<?php
require "autoload.php";

// check Input
if (!checkDatabaseUsageAllowed(true, true, true))
    exit;
if (isset($_GET["archive"]) && isset($_GET["template"]))
    die("Error: Both template and archive is given. Only one of them is allowed\n");

// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
if (isset($_GET['archive'])) {
    $dbc = $dbo->getArchiveDatabaseConnection($_GET['archive'], true);
} elseif (isset($_GET["template"])) {
    $dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
} else {
    $dbc = $dbo->getCurrentDatabaseConnection();
}
if ($dbc == false)
    exit;
if (!$dbc->readlockDatabase())
    exit;

// lese Datenbank
$veranstaltungString = $dbc->getVeranstaltungString();
$dbc->unlockDatabase();

// output
header("Content-Type: application/json");
echo $veranstaltungString;
