<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;

$dbo = new \database\DatabaseOverview();
$archiveNames = $dbo->getArchivedDatabaseNames();

// output
header("Content-Type: application/json");
echo json_encode($archiveNames);
