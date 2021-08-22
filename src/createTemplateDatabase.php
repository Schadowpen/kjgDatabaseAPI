<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(false, false, true))
    exit;

// connect to Database
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->createTemplateDatabase($_GET["template"]);
if ($dbc == false)
    exit;

$dbo->fillTemplateDatabase($dbc);

$dbc->unlockDatabase();

// output
echo "Success";