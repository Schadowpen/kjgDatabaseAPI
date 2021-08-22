<?php
require "autoload.php";

// lese POST Body aus
$eingang = json_decode(file_get_contents("php://input"));
$eingang = deleteUnnecessaryAttributes($eingang, array("id", "x0", "y0", "x1", "y1", "x2", "y2", "x3", "y3", "text", "textXPos", "textYPos", "eingang"));


// überprüfe Input auf Korrektheit
$correct = true;

if (!keyValid(true))
    $correct = false;
if (!checkDatabaseUsageAllowed(false, false, true))
    $correct = false;

if (!isset($eingang->id)) {
    echo "Error: Keine id angegeben \n";
    $correct = false;
} else if (!is_int($eingang->id)) {
    echo "Error: Falscher Datentyp von id \n";
    $correct = false;
}

if (!isset($eingang->x0)) {
    echo "Error: Kein x0 angegeben \n";
    $correct = false;
} else if (!is_number($eingang->x0)) {
    echo "Error: Falscher Datentyp von x0 \n";
    $correct = false;
}

if (!isset($eingang->y0)) {
    echo "Error: Kein y0 angegeben \n";
    $correct = false;
} else if (!is_number($eingang->y0)) {
    echo "Error: Falscher Datentyp von y0 \n";
    $correct = false;
}

if (!isset($eingang->x1)) {
    echo "Error: Kein x1 angegeben \n";
    $correct = false;
} else if (!is_number($eingang->x1)) {
    echo "Error: Falscher Datentyp von x1 \n";
    $correct = false;
}

if (!isset($eingang->y1)) {
    echo "Error: Kein y1 angegeben \n";
    $correct = false;
} else if (!is_number($eingang->y1)) {
    echo "Error: Falscher Datentyp von y1 \n";
    $correct = false;
}

if (!isset($eingang->x2)) {
    echo "Error: Kein x2 angegeben \n";
    $correct = false;
} else if (!is_number($eingang->x2)) {
    echo "Error: Falscher Datentyp von x2 \n";
    $correct = false;
}

if (!isset($eingang->y2)) {
    echo "Error: Kein y2 angegeben \n";
    $correct = false;
} else if (!is_number($eingang->y2)) {
    echo "Error: Falscher Datentyp von y2 \n";
    $correct = false;
}

if (!isset($eingang->x3)) {
    echo "Error: Kein x3 angegeben \n";
    $correct = false;
} else if (!is_number($eingang->x3)) {
    echo "Error: Falscher Datentyp von x3 \n";
    $correct = false;
}

if (!isset($eingang->y3)) {
    echo "Error: Kein y3 angegeben \n";
    $correct = false;
} else if (!is_number($eingang->y3)) {
    echo "Error: Falscher Datentyp von y3 \n";
    $correct = false;
}

if (isset($eingang->text)) {
    if (!is_string($eingang->text)) {
        echo "Error: Falscher Datentyp von text \n";
        $correct = false;
    }

    if (!isset($eingang->textXPos)) {
        echo "Error: Kein textXPos angegeben \n";
        $correct = false;
    } else if (!is_number($eingang->textXPos)) {
        echo "Error: Falscher Datentyp von textXPos \n";
        $correct = false;
    }

    if (!isset($eingang->textYPos)) {
        echo "Error: Kein textYPos angegeben \n";
        $correct = false;
    } else if (!is_number($eingang->textYPos)) {
        echo "Error: Falscher Datentyp von textYPos \n";
        $correct = false;
    }
}

if (isset($eingang->eingang) && !is_int($eingang->eingang)) {
    echo "Error: Falscher Datentyp von eingang \n";
    $correct = false;
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

// Überprüfe, ob eingang->eingang existiert
if (isset($eingang->eingang)) {
    $correct = false;
    for ($i = 0; $i < count($eingaenge); $i++) {
        if ($eingaenge[$i]->id == $eingang->eingang) {
            $correct = true;
            break;
        }
    }
    if (!$correct)
        die("Error: eingang refers to another, but non-existent eingang \n");
}

// vergebe ID für neuen Eingang
if ($eingang->id < 0) {
    // erstelle neue zufällige ID (Beschränkung auf 32-Bit Integer Bereich)
    $existingIds = [];
    for ($i = 0; $i < count($eingaenge); $i++) {
        array_push($existingIds, $eingaenge[$i]->id);
    }
    $eingang->id = 1;
    while (in_array($eingang->id, $existingIds)) {
        $eingang->id ++;
    }
}

// ersetze Eingang oder füge ihn neu hinzu
$eingangReplaced = false;
for ($i = 0; $i < count($eingaenge); $i++) {
    if ($eingaenge[$i]->id == $eingang->id) {
        // Eingang ersetzen
        $eingaenge[$i] = $eingang;
        $eingangReplaced = true;
        break;
    }
}
if (!$eingangReplaced) {
    array_push($eingaenge, $eingang);
}

// schreibe Datenbank
$dbc->setEingaengeJson($eingaenge);
$dbc->unlockDatabase();

// gib gespeicherten Eingang zurück
header("Content-Type: application/json");
echo json_encode($eingang);