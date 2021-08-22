<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;

$dbo = new \database\DatabaseOverview();
$templateNames = $dbo->getTemplateDatabaseNames();

// output
header("Content-Type: application/json");
echo json_encode($templateNames);
