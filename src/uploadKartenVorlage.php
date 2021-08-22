<?php
require "autoload.php";

// Sicherheitschecks
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(false, false, true))
    exit;

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
    exit;

// Ob die Daten von einem form Upload oder einem AJAX-Request stammen
$fileFromAJAX = isset($_SERVER['HTTP_X_FILENAME']);

// Hochgeladene Datei überprüfen
$filePath = $fileFromAJAX ? $_SERVER['HTTP_X_FILENAME'] : $_FILES['kartenVorlage']['name'];
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if ($extension !== 'pdf') {
    die("Error: Ungültige Dateiendung\n");
}

// Hochgeladene Datei einlesen
if ($fileFromAJAX) {
    $fileContent = file_get_contents('php://input');
} else {
    $fileContent = file_get_contents($_FILES['kartenVorlage']["tmp_name"]);
}

try {
    // Automatische Konfiguration
    $autoConfig = new \generation\AutoConfig(new \pdf\PdfDocument(new \pdf\PdfFile(new \misc\StringReader($fileContent))));

    // verbinde mit Datenbank
    $dbo = new \database\DatabaseOverview();
    $dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
    if ($dbc == false)
        exit;
    if (!$dbc->writelockDatabase())
        exit;

    // Datei speichern
    $dbc->setKartenConfigJson($autoConfig);
    $dbc->setKartenVorlageString($fileContent);
    $dbc->unlockDatabase();

    echo "Success";

} catch (Throwable $throwable) {
    if (@$dbc != null)
        $dbc->unlockDatabase();
    //die ("Error: " . $throwable->getMessage() . "\n" . $throwable->getTraceAsString() . "\n");
    die ("Error: " . $throwable->getMessage() . "\n"); // langer Error kann nicht als E-Mail geöffnet werden
}