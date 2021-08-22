<?php
require "autoload.php";

// lese POST Body aus
$platz = json_decode(file_get_contents("php://input"));
$platz = deleteUnnecessaryAttributes($platz, array("block", "reihe", "platz", "xPos", "yPos", "rotation", "eingang"));


// überprüfe Input auf Korrektheit
$correct = true;

if (!keyValid(true))
    $correct = false;
if (!checkDatabaseUsageAllowed(false, false, true))
    $correct = false;

if (!isset($platz->block)) {
    echo "Error: Kein block angegeben \n";
    $correct = false;
} else if (!is_string($platz->block)) {
    echo "Error: Falscher Datentyp von block \n";
    $correct = false;
}

if (!isset($platz->reihe)) {
    echo "Error: Keine reihe angegeben \n";
    $correct = false;
} else if (!is_string($platz->reihe)) {
    echo "Error: Falscher Datentyp von reihe \n";
    $correct = false;
} else if (strlen($platz->reihe) != 1 || !($platz->reihe[0] >= 'A' && $platz->reihe[0] <= "Z" && $platz->reihe != "J")) {
    echo "Error: reihe darf nur Buchstaben A-Z ohne J enthalten \n";
    $correct = false;
}

if (!isset($platz->platz)) {
    echo "Error: Kein platz angegeben \n";
    $correct = false;
} else if (!is_int($platz->platz)) {
    echo "Error: Falscher Datentyp von platz \n";
    $correct = false;
}

if (!isset($platz->xPos)) {
    echo "Error: Kein xPos angegeben \n";
    $correct = false;
} else if (!is_number($platz->xPos)) {
    echo "Error: Falscher Datentyp von xPos \n";
    $correct = false;
}

if (!isset($platz->yPos)) {
    echo "Error: Kein yPos angegeben \n";
    $correct = false;
} else if (!is_number($platz->yPos)) {
    echo "Error: Falscher Datentyp von yPos \n";
    $correct = false;
}

if (!isset($platz->rotation)) {
    echo "Error: Kein rotation angegeben \n";
    $correct = false;
} else if (!is_number($platz->rotation)) {
    echo "Error: Falscher Datentyp von rotation \n";
    $correct = false;
}

if (isset($platz->eingang)) {
    if (!is_int($platz->eingang)) {
        echo "Error: Falscher Datentyp von eingang \n";
        $correct = false;
    }
}

if (!$correct)
    exit;


// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
if ($dbc == false)
    exit;
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$plaetze = $dbc->getPlaetzeJson();
$eingaenge = $dbc->getEingaengeJson();

// überprüfe, of eingang existiert
if (isset($platz->eingang)) {
    $correct = false;
    for ($i = 0; $i < count($eingaenge); $i++) {
        if ($eingaenge[$i]->id == $platz->eingang) {
            $correct = true;
            break;
        }
    }
    if (!$correct)
        die("Error: eingang refers to a non-existent Eingang \n");
}

// ersetze Platz oder füge neu hinzu
$platzReplaced = false;
for ($i = 0; $i < count($plaetze); ++$i) {
    if ($plaetze[$i]->block == $platz->block
        && $plaetze[$i]->reihe == $platz->reihe
        && $plaetze[$i]->platz == $platz->platz) {
        $plaetze[$i] = $platz;
        $platzReplaced = true;
        break;
    }
}
if (!$platzReplaced) {
    array_push($plaetze, $platz);
}

// schreibe Datenbank
$dbc->setPlaetzeJson($plaetze);
$dbc->unlockDatabase();

// gib gespeicherten Platz zurück
header("Content-Type: application/json");
echo json_encode($platz);
