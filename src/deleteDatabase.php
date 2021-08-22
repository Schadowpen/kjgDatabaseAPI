<?php
require "autoload.php";

// überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(false, true, true))
    exit;
if (isset($_GET["archive"]) && isset($_GET["template"]))
    die("Error: Both template and archive is given. Only one of them is allowed\n");

// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
if (isset($_GET["archive"])) {
    $dbc = $dbo->getArchiveDatabaseConnection($_GET["archive"]);
} else {
    $dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
}
if ($dbc == false)
    exit;
if (!$dbc->writelockDatabase())
    exit;


// lösche Datenbank
$success = $dbo->deleteDatabase($dbc);
if (!$success)
    exit;

// unlock nicht notwendig, da deleteDatabase() das bereits erledigt

echo "Success";
