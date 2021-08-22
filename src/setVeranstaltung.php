<?php
require_once 'autoload.php';

// lese POST Body aus
$veranstaltung = json_decode(file_get_contents("php://input"));
$veranstaltung = deleteUnnecessaryAttributes($veranstaltung, array("veranstaltung", "raumLaenge", "raumBreite", "sitzLaenge", "sitzBreite", "laengenEinheit", "kartenPreis", "versandPreis", "additionalFieldsForVorgang"));

// überprüfe Input auf Korrektheit
$correct = true;

if (!keyValid(true))
    $correct = false;
if (!checkDatabaseUsageAllowed(false, false, true))
    $correct = false;

if (!isset($veranstaltung->veranstaltung)) {
    echo "Error: Keine veranstaltung angegeben \n";
    $correct = false;
} elseif (!is_string($veranstaltung->veranstaltung)) {
    echo "Error: Falscher Datentyp von veranstaltung \n";
    $correct = false;
}

if (!isset($veranstaltung->raumBreite)) {
    echo "Error: Keine raumBreite angegeben \n";
    $correct = false;
} elseif (!is_number($veranstaltung->raumBreite)) {
    echo "Error: Falscher Datentyp von raumBreite \n";
    $correct = false;
} elseif ($veranstaltung->raumBreite < 0) {
    echo "Error: raumBreite muss positiv sein \n";
    $correct = false;
}

if (!isset($veranstaltung->raumLaenge)) {
    echo "Error: Keine raumLaenge angegeben \n";
    $correct = false;
} elseif (!is_number($veranstaltung->raumLaenge)) {
    echo "Error: Falscher Datentyp von raumLaenge \n";
    $correct = false;
} elseif ($veranstaltung->raumLaenge < 0) {
    echo "Error: raumLaenge muss positiv sein \n";
    $correct = false;
}

if (!isset($veranstaltung->sitzBreite)) {
    echo "Error: Keine sitzBreite angegeben \n";
    $correct = false;
} elseif (!is_number($veranstaltung->sitzBreite)) {
    echo "Error: Falscher Datentyp von sitzBreite \n";
    $correct = false;
} elseif ($veranstaltung->sitzBreite < 0) {
    echo "Error: sitzBreite muss positiv sein \n";
    $correct = false;
}

if (!isset($veranstaltung->sitzLaenge)) {
    echo "Error: Keine sitzLaenge angegeben \n";
    $correct = false;
} elseif (!is_number($veranstaltung->sitzLaenge)) {
    echo "Error: Falscher Datentyp von sitzLaenge \n";
    $correct = false;
} elseif ($veranstaltung->sitzLaenge < 0) {
    echo "Error: sitzLaenge muss positiv sein \n";
    $correct = false;
}

if (!isset($veranstaltung->laengenEinheit)) {
    echo "Error: Keine laengenEinheit angegeben \n";
    $correct = false;
} elseif (!is_string($veranstaltung->laengenEinheit)) {
    echo "Error: Falscher Datentyp von laengenEinheit \n";
    $correct = false;
}

if (!isset($veranstaltung->kartenPreis)) {
    echo "Error: Kein kartenPreis angegeben \n";
    $correct = false;
} elseif (!is_number($veranstaltung->kartenPreis)) {
    echo "Error: Falscher Datentyp von kartenPreis \n";
    $correct = false;
} elseif ($veranstaltung->kartenPreis < 0) {
    echo "Error: kartenPreis muss positiv sein \n";
    $correct = false;
}

if (!isset($veranstaltung->versandPreis)) {
    echo "Error: Kein versandPreis angegeben \n";
    $correct = false;
} elseif (!is_number($veranstaltung->versandPreis)) {
    echo "Error: Falscher Datentyp von versandPreis \n";
    $correct = false;
} elseif ($veranstaltung->versandPreis < 0) {
    echo "Error: versandPreis muss positiv sein \n";
    $correct = false;
}

if (isset($veranstaltung->additionalFieldsForVorgang)) {
    if (!is_array($veranstaltung->additionalFieldsForVorgang)) {
        echo "Error: Falscher Datentyp von additionalFieldsForVorgang \n";
        $correct = false;
    } else {
        for ($i = 0; $i < count($veranstaltung->additionalFieldsForVorgang); ++$i) {
            $field = $veranstaltung->additionalFieldsForVorgang[$i];
            if (!is_object($field)) {
                echo "Error: Falscher Datentyp von additionalFieldsForVorgang[$i] \n";
                $correct = false;
            } else {
                $veranstaltung->additionalFieldsForVorgang[$i] = deleteUnnecessaryAttributes($field, array("type", "fieldName", "description", "required"));

                if (!isset($field->type)) {
                    echo "Error: Kein additionalFieldsForVorgang[$i]->type angegeben \n";
                    $correct = false;
                } elseif (!is_string($field->type)) {
                    echo "Error: Falscher Datentyp von additionalFieldsForVorgang[$i]->type \n";
                    $correct = false;
                } elseif ($field->type != "integer" && $field->type != "float" && $field->type != "string" && $field->type != "longString" && $field->type != "boolean") {
                    echo "Error: Kein gültiger additionalFieldsForVorgang[$i]->type angegeben \n";
                    $correct = false;
                }

                if (!isset($field->fieldName)) {
                    echo "Error: Kein additionalFieldsForVorgang[$i]->fieldName angegeben \n";
                    $correct = false;
                } elseif (!is_string($field->fieldName)) {
                    echo "Error: Falscher Datentyp von additionalFieldsForVorgang[$i]->fieldName \n";
                    $correct = false;
                } elseif (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $field->fieldName)) {
                    echo "Error: additionalFieldsForVorgang[$i]->fieldName is not possible as fieldName \n";
                    $correct = false;
                }

                if (!isset($field->description)) {
                    echo "Error: Keine additionalFieldsForVorgang[$i]->description angegeben \n";
                    $correct = false;
                } elseif (!is_string($field->description)) {
                    echo "Error: Falscher Datentyp von additionalFieldsForVorgang[$i]->description \n";
                    $correct = false;
                }

                if (!isset($field->required)) {
                    echo "Error: Kein additionalFieldsForVorgang[$i]->required angegeben \n";
                    $correct = false;
                } elseif (!is_bool($field->required)) {
                    echo "Error: Falscher Datentyp von additionalFieldsForVorgang[$i]->required \n";
                    $correct = false;
                }
            }
        }
    }
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

// schreibe Daten in Datenbank
$dbc->setVeranstaltungJson($veranstaltung);

$dbc->unlockDatabase();

// gib gespeicherte Veranstaltung zurück
header("Content-Type: application/json");
echo json_encode($veranstaltung);
