<?php
require_once "autoload.php";

// lese POST Body aus
$bereich = json_decode(file_get_contents("php://input"));
$bereich = deleteUnnecessaryAttributes($bereich, array("id", "xPos", "yPos", "breite", "laenge", "farbe", "text", "textXPos", "textYPos", "textFarbe"));

// überprüfe Input auf Korrektheit
$correct = true;

if (!keyValid())
    $correct = false;
if (!checkDatabaseUsageAllowed(false, false, true))
    $correct = false;

if (!isset($bereich->id)) {
    echo "Error: Keine id angegeben \n";
    $correct = false;
} else if (!is_int($bereich->id)) {
    echo "Error: Falscher Datentyp von id \n";
    $correct = false;
}

if (!isset($bereich->xPos)) {
    echo "Error: Keine xPos angegeben \n";
    $correct = false;
} else if (!is_number($bereich->xPos)) {
    echo "Error: Falscher Datentyp von xPos \n";
    $correct = false;
}

if (!isset($bereich->yPos)) {
    echo "Error: Keine yPos angegeben \n";
    $correct = false;
} else if (!is_number($bereich->yPos)) {
    echo "Error: Falscher Datentyp von yPos \n";
    $correct = false;
}

if (!isset($bereich->breite)) {
    echo "Error: Keine breite angegeben \n";
    $correct = false;
} else if (!is_number($bereich->breite)) {
    echo "Error: Falscher Datentyp von breite \n";
    $correct = false;
}

if (!isset($bereich->laenge)) {
    echo "Error: Keine laenge angegeben \n";
    $correct = false;
} else if (!is_number($bereich->laenge)) {
    echo "Error: Falscher Datentyp von laenge \n";
    $correct = false;
}

if (!isset($bereich->farbe)) {
    echo "Error: Keine farbe angegeben \n";
    $correct = false;
} else if (!is_string($bereich->farbe)) {
    echo "Error: Falscher Datentyp von farbe \n";
    $correct = false;
} elseif (!preg_match("/#([0-9a-fA-F]{6})/", $bereich->farbe)) {
    echo "Error: farbe muss Hexadezimal in der Form #rrggbb angegeben werden \n";
    $correct = false;
}

if (!isset($bereich->text)) {
    echo "Error: Kein text angegeben \n";
    $correct = false;
} else if (!is_string($bereich->text)) {
    echo "Error: Falscher Datentyp von text \n";
    $correct = false;
}

if (!isset($bereich->textXPos)) {
    echo "Error: Keine textXPos angegeben \n";
    $correct = false;
} else if (!is_number($bereich->textXPos)) {
    echo "Error: Falscher Datentyp von textXPos \n";
    $correct = false;
}

if (!isset($bereich->textYPos)) {
    echo "Error: Keine textYPos angegeben \n";
    $correct = false;
} else if (!is_number($bereich->textYPos)) {
    echo "Error: Falscher Datentyp von textYPos \n";
    $correct = false;
}

if (!isset($bereich->textFarbe)) {
    echo "Error: Keine textFarbe angegeben \n";
    $correct = false;
} else if (!is_string($bereich->textFarbe)) {
    echo "Error: Falscher Datentyp von textFarbe \n";
    $correct = false;
} elseif (!preg_match("/#([0-9a-fA-F]{6})/", $bereich->textFarbe)) {
    echo "Error: textFarbe muss Hexadezimal in der Form #rrggbb angegeben werden \n";
    $correct = false;
}

if (!$correct)
    exit;


// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
if ($dbc === false)
    exit;
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$bereiche = $dbc->getBereicheJson();

if ($bereich->id < 0) {
    // erstelle neue zufällige id (Beschränkung auf 32-Bit Integer Bereich)
    $existingIds = [];
    for ($i = 0; $i < count($bereiche); $i++) {
        array_push($existingIds, $bereiche[$i]->id);
    }
    $bereich->id = 1;
    while (in_array($bereich->id, $existingIds))
        $bereich->id ++;
}

// ersetze Bereich oder füge neu hinzu
$bereichReplaced = false;
for ($i = 0; $i < count($bereiche); $i++) {
    if ($bereiche[$i]->id == $bereich->id) {
        $bereiche[$i] = $bereich;
        $bereichReplaced = true;
        break;
    }
}
if (!$bereichReplaced)
    array_push($bereiche, $bereich);

// schreibe Datenbank
$dbc->setBereicheJson($bereiche);

$dbc->unlockDatabase();

// gib gespeicherten Bereich zurück
header("Content-Type: application/json");
echo json_encode($bereich);
