<?php
require "autoload.php";

// lese POST Body aus
$platzGruppe = json_decode(file_get_contents("php://input"));
$platzGruppe = deleteUnnecessaryAttributes($platzGruppe, array("id", "block", "reiheVorne", "reiheHinten", "reiheAbstand", "platzLinks", "platzRechts", "platzAbstand", "xPos", "yPos", "rotation", "eingang"));


// überprüfe Input auf Korrektheit
$correct = true;

if (!keyValid())
    $correct = false;
if (!checkDatabaseUsageAllowed(false, false, true))
    $correct = false;

if (!isset($platzGruppe->id)) {
    echo "Error: Keine id angegeben \n";
    $correct = false;
} else if (!is_int($platzGruppe->id)) {
    echo "Error: Falscher Datentyp von id \n";
    $correct = false;
}

if (!isset($platzGruppe->block)) {
    echo "Error: Kein block angegeben \n";
    $correct = false;
} else if (!is_string($platzGruppe->block)) {
    echo "Error: Falscher Datentyp von block \n";
    $correct = false;
}

if (!isset($platzGruppe->reiheVorne)) {
    echo "Error: Keine reiheVorne angegeben \n";
    $correct = false;
} else if (!is_string($platzGruppe->reiheVorne)) {
    echo "Error: Falscher Datentyp von reiheVorne \n";
    $correct = false;
} else if (strlen($platzGruppe->reiheVorne) != 1 || !($platzGruppe->reiheVorne[0] >= 'A' && $platzGruppe->reiheVorne[0] <= "Z" && $platzGruppe->reiheVorne != "J")) {
    echo "Error: reiheVorne darf nur Buchstaben A-Z ohne J enthalten \n";
    $correct = false;
}

if (!isset($platzGruppe->reiheHinten)) {
    echo "Error: Keine reiheHinten angegeben \n";
    $correct = false;
} else if (!is_string($platzGruppe->reiheHinten)) {
    echo "Error: Falscher Datentyp von reiheHinten \n";
    $correct = false;
} else if (strlen($platzGruppe->reiheHinten) != 1 || !($platzGruppe->reiheHinten[0] >= 'A' && $platzGruppe->reiheHinten[0] <= "Z" && $platzGruppe->reiheHinten != "J")) {
    echo "Error: reiheHinten darf nur Buchstaben A-Z ohne J enthalten \n";
    $correct = false;
}

if (!isset($platzGruppe->reiheAbstand)) {
    echo "Error: Keine reiheAbstand angegeben \n";
    $correct = false;
} else if (!is_number($platzGruppe->reiheAbstand)) {
    echo "Error: Falscher Datentyp von reiheAbstand \n";
    $correct = false;
}

if (!isset($platzGruppe->platzLinks)) {
    echo "Error: Kein platzLinks angegeben \n";
    $correct = false;
} else if (!is_int($platzGruppe->platzLinks)) {
    echo "Error: Falscher Datentyp von platzLinks \n";
    $correct = false;
}

if (!isset($platzGruppe->platzRechts)) {
    echo "Error: Kein platzRechts angegeben \n";
    $correct = false;
} else if (!is_int($platzGruppe->platzRechts)) {
    echo "Error: Falscher Datentyp von platzRechts \n";
    $correct = false;
}

if (!isset($platzGruppe->platzAbstand)) {
    echo "Error: Kein platzAbstand angegeben \n";
    $correct = false;
} else if (!is_number($platzGruppe->platzAbstand)) {
    echo "Error: Falscher Datentyp von platzAbstand \n";
    $correct = false;
}

if (!isset($platzGruppe->xPos)) {
    echo "Error: Kein xPos angegeben \n";
    $correct = false;
} else if (!is_number($platzGruppe->xPos)) {
    echo "Error: Falscher Datentyp von xPos \n";
    $correct = false;
}

if (!isset($platzGruppe->yPos)) {
    echo "Error: Keine yPos angegeben \n";
    $correct = false;
} else if (!is_number($platzGruppe->yPos)) {
    echo "Error: Falscher Datentyp von yPos \n";
    $correct = false;
}

if (!isset($platzGruppe->rotation)) {
    echo "Error: Keine rotation angegeben \n";
    $correct = false;
} else if (!is_number($platzGruppe->rotation)) {
    echo "Error: Falscher Datentyp von rotation \n";
    $correct = false;
}

if (isset($platzGruppe->eingang)) {
    if (!is_int($platzGruppe->eingang)) {
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
$eingaenge = $dbc->getEingaengeJson();
$platzGruppen = $dbc->getPlatzGruppenJson();

// überprüfe, ob eingang existiert
if (isset($platzGruppe->eingang)) {
    $correct = false;
    for ($i = 0; $i < count($eingaenge); $i++) {
        if ($eingaenge[$i]->id == $platzGruppe->eingang) {
            $correct = true;
            break;
        }
    }
    if (!$correct)
        die("Error: eingang refers to a non-existent eingang \n");
}

// vergebe ID für neue Platzgruppe
if ($platzGruppe->id < 0) {
    // erstelle neue zufällige ID (Beschränkung auf 32-Bit Integer Bereich)
    $existingIds = [];
    for ($i = 0; $i < count($platzGruppen); $i++) {
        array_push($existingIds, $platzGruppen[$i]->id);
    }
    $platzGruppe->id = 1;
    while (in_array($platzGruppe->id, $existingIds))
        $platzGruppe->id ++;
}

// ersetze Platzgruppe oder füge neu hinzu
$platzGruppeReplaced = false;
for ($i = 0; $i < count($platzGruppen); ++$i) {
    if ($platzGruppen[$i]->id == $platzGruppe->id) {
        $platzGruppen[$i] = $platzGruppe;
        $platzGruppeReplaced = true;
        break;
    }
}
if (!$platzGruppeReplaced) {
    array_push($platzGruppen, $platzGruppe);
}

// schreibe Datenbank
$dbc->setPlatzGruppenJson($platzGruppen);
$dbc->unlockDatabase();

// gib gespeicherte PlatzGruppe zurück
header("Content-Type: application/json");
echo json_encode($platzGruppe);
