<?php
require "autoload.php";

// lese POST Body aus
$vorgang = readInputAsJSON();

// überprüfe Input auf Korrektheit
$correct = true;

if (!keyValid(true))
    $correct = false;
if (!checkDatabaseUsageAllowed(true, false, false))
    $correct = false;

if (!isset($vorgang->nummer)) {
    echo "Error: Keine Nummer angegeben \n";
    $correct = false;
} else if (!is_int($vorgang->nummer)) {
    echo "Error: Falscher Datentyp von nummer \n";
    $correct = false;
}

if (!isset($vorgang->blackDataInArchive)) {
    $vorgang->blackDataInArchive = false;
} else if (!is_bool($vorgang->blackDataInArchive)) {
    echo "Error: Falscher Datentyp von blackDataInArchive \n";
    $correct = false;
}

if (!isset($vorgang->vorname) || $vorgang->vorname == "") {
    echo "Error: Kein Vorname angegeben \n";
    $correct = false;
} else if (!is_string($vorgang->vorname)) {
    echo "Error: Falscher Datentyp von vorname \n";
    $correct = false;
}

if (!isset($vorgang->nachname) || $vorgang->nachname == "") {
    echo "Error: Kein Nachname angegeben \n";
    $correct = false;
} else if (!is_string($vorgang->nachname)) {
    echo "Error: Falscher Datentyp von nachname \n";
    $correct = false;
}

if (isset($vorgang->email) && !is_string($vorgang->email)) {
    echo "Error: Falscher Datentyp von email \n";
    $correct = false;
}
if (isset($vorgang->telefon) && !is_string($vorgang->telefon)) {
    echo "Error: Falscher Datentyp von telefon \n";
    $correct = false;
}
if ((!isset($vorgang->email) || $vorgang->email == "") && (!isset($vorgang->telefon) || $vorgang->telefon == "")) {
    echo "Error: Weder E-Mail noch Telefon angegeben \n";
    $correct = false;
}

if (isset($vorgang->preis) && !is_float($vorgang->preis) && !is_int($vorgang->preis)) {
    echo "Error: Falscher Datentyp von preis \n";
    $correct = false;
} else if (isset($vorgang->preis) && $vorgang->preis < 0) {
    echo "Error: keine negative Preise fuer Karten \n";
    $correct = false;
}

if (!isset($vorgang->bezahlart)) {
    $vorgang->bezahlart = "Bar";
} else if (!is_string($vorgang->bezahlart)) {
    echo "Error: Falscher Datentyp von bezahlart \n";
    $correct = false;
} else if ($vorgang->bezahlart != "Bar" && $vorgang->bezahlart != "Ueberweisung" && $vorgang->bezahlart != "PayPal" && $vorgang->bezahlart != "Abendkasse" && $vorgang->bezahlart != "VIP" && $vorgang->bezahlart != "TripleA") {
    echo "Error: Keine gueltige Bezahlart angegeben \n";
    $correct = false;
}

if (!isset($vorgang->bezahlung)) {
    if ($vorgang->bezahlart == "Abendkasse")
        $vorgang->bezahlung = "Abendkasse";
    else if ($vorgang->bezahlart == "VIP" || $vorgang->bezahlart == "TripleA")
        $vorgang->bezahlung = "bezahlt";
    else
        $vorgang->bezahlung = "offen";
} else if (!is_string($vorgang->bezahlung)) {
    echo "Error: Falscher Datentyp von bezahlung \n";
    $correct = false;
} else if ($vorgang->bezahlung != "offen" && $vorgang->bezahlung != "bezahlt" && $vorgang->bezahlung != "Abendkasse") {
    echo "Error: Keine gueltige Bezahlung angegeben \n";
    $correct = false;
} else if ($vorgang->bezahlart == "Abendkasse") {
    $vorgang->bezahlung = "Abendkasse";
}

if (!isset($vorgang->versandart)) {
    $vorgang->versandart = "Abholung";
} else if (!is_string($vorgang->versandart)) {
    echo "Error: Falscher Datentyp von versandart \n";
    $correct = false;
} else if ($vorgang->versandart != "Abholung" && $vorgang->versandart != "Post" && $vorgang->versandart != "E-Mail") {
    echo "Error: Keine gueltige Versandart angegeben \n";
    $correct = false;
} else if ($vorgang->bezahlart == "Abendkasse") {
    $vorgang->versandart = "Abholung";
}

if ($vorgang->versandart == "E-Mail" && (!isset($vorgang->email) || $vorgang->email == "")) {
    echo "Error: Bei Versendung per E-Mail muss die E-Mail Adresse angegeben werden \n";
    $correct = false;
}

if ($vorgang->versandart == "Post" && (!isset($vorgang->anschrift) || $vorgang->anschrift == "")) {
    echo "Error: Bei Versendung per Post muss die Anschrift angegeben werden \n";
    $correct = false;
}
if (isset($vorgang->anschrift) && !is_string($vorgang->anschrift)) {
    echo "Error: Falscher Datentyp von anschrift \n";
    $correct = false;
}

if (isset($vorgang->kommentar) && !is_string($vorgang->kommentar)) {
    echo "Error: Falscher Datentyp von kommentar \n";
    $correct = false;
}


if (!$correct)
    exit;


// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getCurrentDatabaseConnection();
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$veranstaltung = $dbc->getVeranstaltungJson();
$vorstellungen = $dbc->getVorstellungenJson();
$vorgaenge = $dbc->getVorgaengeJson();
$platzStatusse = $dbc->getPlatzStatusseJson();

// überprüfe zusätzliche Felder
if (isset($veranstaltung->additionalFieldsForVorgang)) {
    foreach ($veranstaltung->additionalFieldsForVorgang as $additionalField) {
        if (!isset($vorgang->{$additionalField->fieldName})) {
            if ($additionalField->required) {
                echo "Error: Kein {$additionalField->fieldName} angegeben \n";
                $correct = false;
            }
        } else {
            switch ($additionalField->type) {
                case "integer":
                    if (!is_int($vorgang->{$additionalField->fieldName})) {
                        echo "Error: Falscher Datentyp von {$additionalField->fieldName} \n";
                        $correct = false;
                    }
                    break;
                case "float":
                    if (!is_number($vorgang->{$additionalField->fieldName})) {
                        echo "Error: Falscher Datentyp von {$additionalField->fieldName} \n";
                        $correct = false;
                    }
                    break;
                case "string":
                case "longString":
                    if (!is_string($vorgang->{$additionalField->fieldName})) {
                        echo "Error: Falscher Datentyp von {$additionalField->fieldName} \n";
                        $correct = false;
                    }
                    break;
                case "boolean":
                    if (!is_bool($vorgang->{$additionalField->fieldName})) {
                        echo "Error: Falscher Datentyp von {$additionalField->fieldName} \n";
                        $correct = false;
                    }
                    break;
            }
        }
    }
}
if (!$correct) {
    $dbc->unlockDatabase();
    exit;
}

// Setze Standardwerte, wenn keine angegeben
if (!isset($vorgang->preis))
    $vorgang->preis = $veranstaltung->kartenPreis;

if ($vorgang->nummer < 0) {
    // erstelle neue zufällige Vorgangsnummer (Beschränkung auf 32-Bit Integer Bereich)
    $existingNumbers = [];
    for ($i = 0; $i < count($vorgaenge); $i++) {
        array_push($existingNumbers, $vorgaenge[$i]->nummer);
    }
    do {
        try {
            $vorgang->nummer = random_int(1, 999999999);
        } catch (Exception $e) {
            $vorgang->nummer = rand(1, 999999999);
        }
    } while (in_array($vorgang->nummer, $existingNumbers));
}

// wenn $vorgang->plaetze existiert, sollen die PlatzStatusse entfernt und neu gesetzt werden
if (isset($vorgang->plaetze)) {
    // lese weitere nun benötigte Daten aus Datenbank
    $plaetze = $dbc->getPlaetzeJson();

    // überprüfe Plätze auf Korrektheit
    $correct = true;
    for ($i = 0; $i < count($vorgang->plaetze); $i++) {

        if (!isset($vorgang->plaetze[$i]->date)) {
            echo "Error: Kein plaetze->date angegeben \n";
            $correct = false;
        } else if (!is_string($vorgang->plaetze[$i]->date)) {
            echo "Error: Falscher Datentyp von plaetze->date \n";
            $correct = false;
        }

        if (!isset($vorgang->plaetze[$i]->time)) {
            echo "Error: Kein plaetze->time angegeben \n";
            $correct = false;
        } else if (!is_string($vorgang->plaetze[$i]->time)) {
            echo "Error: Falscher Datentyp von plaetze->time \n";
            $correct = false;
        }

        if (!isset($vorgang->plaetze[$i]->block)) {
            echo "Error: Kein plaetze->block angegeben \n";
            $correct = false;
        } else if (!is_string($vorgang->plaetze[$i]->block)) {
            echo "Error: Falscher Datentyp von plaetze->block \n";
            $correct = false;
        }

        if (!isset($vorgang->plaetze[$i]->reihe)) {
            echo "Error: Kein plaetze->reihe angegeben \n";
            $correct = false;
        } else if (!is_string($vorgang->plaetze[$i]->reihe)) {
            echo "Error: Falscher Datentyp von plaetze->reihe \n";
            $correct = false;
        }

        if (!isset($vorgang->plaetze[$i]->platz)) {
            echo "Error: Kein plaetze->platz angegeben \n";
            $correct = false;
        } else if (!is_int($vorgang->plaetze[$i]->platz)) {
            echo "Error: Falscher Datentyp von plaetze->platz \n";
            $correct = false;
        }

        if ($correct) {
            $correctVorstellung = false;
            for ($k = 0; $k < count($vorstellungen); $k++) {
                if ($vorgang->plaetze[$i]->date === $vorstellungen[$k]->date
                    && $vorgang->plaetze[$i]->time === $vorstellungen[$k]->time)
                    $correctVorstellung = true;
            }
            if (!$correctVorstellung) {
                echo "Error: Angegebener Platz gehoert zu keiner gueltigen Vorstellung \n";
                $correct = false;
            }

            $correctPlatz = false;
            for ($k = 0; $k < count($plaetze); $k++) {

                if ($vorgang->plaetze[$i]->block === $plaetze[$k]->block
                    && $vorgang->plaetze[$i]->reihe === $plaetze[$k]->reihe
                    && $vorgang->plaetze[$i]->platz === $plaetze[$k]->platz) {
                    $correctPlatz = true;
                }

            }
            if (!$correctPlatz) {
                echo "Error: Angegebener Platz ist kein gueltiger Sitzplatz \n";
                $correct = false;
            }

            if ($correct) {
                // gehe alle PlatzStatusse durch und lösche
                for ($k = count($platzStatusse) - 1; $k >= 0; $k--) {
                    // lösche die PlatzStatusse, die zum Vorgang gehören sollen. Werfe Fehler, falls diese PlatzStatusse belegt sind.
                    if ($vorgang->plaetze[$i]->date === $platzStatusse[$k]->date
                        && $vorgang->plaetze[$i]->time === $platzStatusse[$k]->time
                        && $vorgang->plaetze[$i]->block === $platzStatusse[$k]->block
                        && $vorgang->plaetze[$i]->reihe === $platzStatusse[$k]->reihe
                        && $vorgang->plaetze[$i]->platz === $platzStatusse[$k]->platz) {
                        if (isset($platzStatusse[$k]->vorgangsNr)) {
                            if ($platzStatusse[$k]->vorgangsNr === $vorgang->nummer) {
                                array_splice($platzStatusse, $k, 1);
                            } else {
                                echo "Error: Angegebener Platz bereits durch anderen Vorgang belegt \n";
                                $correct = false;
                            }
                        } else if ($platzStatusse[$k]->status === "gesperrt") {
                            echo "Error: Angegebener Platz ist gesperrt \n";
                            $correct = false;
                        } else {
                            array_splice($platzStatusse, $k, 1);
                        }
                        break;

                        // lösche die PlatzStatusse, die zum Vorgang gehören, da sie nachher neu gespeichert werden
                    } elseif (isset($platzStatusse[$k]->vorgangsNr) && $platzStatusse[$k]->vorgangsNr === $vorgang->nummer) {
                        array_splice($platzStatusse, $k, 1);
                    }
                }
            }
        }
    }
    if (!$correct) {
        // es wurde noch nichts in der Datenbank gespeichert, deshalb kann bei Fehlern immer noch abgebrochen werden.
        $dbc->unlockDatabase();
        exit;
    }

    // Füge neue PlatzStatusse hinzu
    for ($i = 0; $i < count($vorgang->plaetze); $i++) {
        array_push($platzStatusse, (object)array(
            "date" => $vorgang->plaetze[$i]->date,
            "time" => $vorgang->plaetze[$i]->time,
            "block" => $vorgang->plaetze[$i]->block,
            "reihe" => $vorgang->plaetze[$i]->reihe,
            "platz" => $vorgang->plaetze[$i]->platz,
            "status" => $vorgang->bezahlung == "bezahlt" ? "gebucht" : "reserviert",
            "vorgangsNr" => $vorgang->nummer));
    }

} else {
    // aktualisere den Status in allen verbundenen PlatzStatussen
    for ($i = 0; $i < count($platzStatusse); $i++) {
        if (@$platzStatusse[$i]->vorgangsNr === $vorgang->nummer && $platzStatusse[$i]->status != "anwesend") {
            if ($vorgang->bezahlung == "bezahlt")
                $platzStatusse[$i]->status = "gebucht";
            else
                $platzStatusse[$i]->status = "reserviert";
        }
    }
}

// Bereinigung von Attributen, die nichts in der Datenbank verloren haben
$allowedAttributes = array("nummer", "blackDataInArchive", "vorname", "nachname", "email", "telefon", "preis", "bezahlart", "bezahlung", "versandart", "anschrift", "kommentar");
if (isset($veranstaltung->additionalFieldsForVorgang)) {
    foreach ($veranstaltung->additionalFieldsForVorgang as $additionalField)
        array_push($allowedAttributes, $additionalField->fieldName);
}
$vorgang = deleteUnnecessaryAttributes($vorgang, $allowedAttributes);

// ersetze Vorgang oder füge ihn neu hinzu
$vorgangReplaced = false;
for ($i = 0; $i < count($vorgaenge); $i++) {
    if ($vorgaenge[$i]->nummer == $vorgang->nummer) {

        // Theaterkarte neu berechnen
        if (isset($vorgaenge[$i]->theaterkarte)) {
            $ticketGenerator = new generation\TicketGenerator($dbc, $vorgang);
            $ticketGenerator->setPlatzStatusse($platzStatusse);
            $ticketGenerator->setVeranstaltung($veranstaltung);
            if ($vorgang->bezahlung === "offen") {
                // Theaterkarte entfernen
                $ticketGenerator->deleteExistingTicket($vorgaenge[$i]->theaterkarte);
            } else {
                // Neue Theaterkarte erstellen
                try {
                    $ticketGenerator->generateTicket();
                    $ticketGenerator->saveTicket();
                } catch (Throwable $exception) {
                    $dbc->unlockDatabase();
                    die( "Error: Theaterkarte konnte nicht erstellt werden.");
                }
                // Alte Theaterkarte löschen
                $vorgang->theaterkarte = $ticketGenerator->getTicketURL();
                if ($vorgang->theaterkarte !== $vorgaenge[$i]->theaterkarte)
                    $ticketGenerator->deleteExistingTicket($vorgaenge[$i]->theaterkarte);
            }
        }

        // Vorgang ersetzen
        $vorgaenge[$i] = $vorgang;
        $vorgangReplaced = true;
        break;
    }
}
if (!$vorgangReplaced) {
    array_push($vorgaenge, $vorgang);
}

// schreibe Datenbank
$dbc->setVorgaengeJson($vorgaenge);
$dbc->setPlatzStatusseJson($platzStatusse);

$dbc->unlockDatabase();

// gib gespeicherten Vorgang zurück
header("Content-Type: application/json");
echo json_encode($vorgang);
