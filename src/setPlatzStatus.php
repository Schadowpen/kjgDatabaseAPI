<?php
require "autoload.php";

// lese POST Body aus
$status = json_decode(file_get_contents("php://input"));
$status = deleteUnnecessaryAttributes($status, array("date", "time", "block", "reihe", "platz", "status", "vorgangsNr"));

// überprüfe Input auf Korrektheit
$correct = true;

if (!keyValid(true))
    $correct = false;
if (!checkDatabaseUsageAllowed(true, false, false))
    exit;

if (!isset($status->date)) {
    echo "Error: Kein date angegeben \n";
    $correct = false;
} else if (!is_string($status->date)) {
    echo "Error: Falscher Datentyp von date \n";
    $correct = false;
}

if (!isset($status->time)) {
    echo "Error: Kein time angegeben \n";
    $correct = false;
} else if (!is_string($status->time)) {
    echo "Error: Falscher Datentyp von time \n";
    $correct = false;
}

if (!isset($status->block)) {
    echo "Error: Kein block angegeben \n";
    $correct = false;
} else if (!is_string($status->block)) {
    echo "Error: Falscher Datentyp von block \n";
    $correct = false;
}

if (!isset($status->reihe)) {
    echo "Error: Kein reihe angegeben \n";
    $correct = false;
} else if (!is_string($status->reihe)) {
    echo "Error: Falscher Datentyp von reihe \n";
    $correct = false;
}

if (!isset($status->platz)) {
    echo "Error: Kein platz angegeben \n";
    $correct = false;
} else if (!is_int($status->platz)) {
    echo "Error: Falscher Datentyp von platz \n";
    $correct = false;
}

if (!isset($status->status)) {
    echo "Error: Kein status angegeben \n";
    $correct = false;
} else if (!is_string($status->status)) {
    echo "Error: Falscher Datentyp von status \n";
    $correct = false;
} else if ($status->status != "frei" && $status->status != "reserviert" && $status->status != "gebucht" && $status->status != "gesperrt" && $status->status != "anwesend") {
    echo "Error: Kein gueltiger status angegeben \n";
    $correct = false;
}

if (($status->status == "reserviert" || $status->status == "gebucht" || $status->status == "anwesend") && !isset($status->vorgangsNr)) {
    echo "Error: Kein vorgangsNr angegeben \n";
    $correct = false;
} else if (isset($status->vorgangsNr) && !is_int($status->vorgangsNr)) {
    echo "Error: Falscher Datentyp von vorgangsNr \n";
    $correct = false;
} else if (isset($status->vorgangsNr) && ($status->status == "frei" || $status->status == "gesperrt")) {
    unset($status->vorgangsNr);
}

if (!$correct)
    exit;


// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getCurrentDatabaseConnection();
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$vorstellungen = $dbc->getVorstellungenJson();
$vorgaenge = $dbc->getVorgaengeJson();
$plaetze = $dbc->getPlaetzeJson();
$platzStatusse = $dbc->getPlatzStatusseJson();

// Überprüfung auf Korrektheit mit Datenbankinformationen
$correctVorstellung = false;
for ($k = 0; $k < count($vorstellungen); $k++) {
    if ($status->date === $vorstellungen[$k]->date
        && $status->time === $vorstellungen[$k]->time)
        $correctVorstellung = true;
}
if (!$correctVorstellung) {
    echo "Error: Angegebener PlatzStatus gehoert zu keiner gueltigen Vorstellung \n";
    $correct = false;
}

$correctPlatz = false;
for ($k = 0; $k < count($plaetze); $k++) {
    if ($status->block === $plaetze[$k]->block
        && $status->reihe === $plaetze[$k]->reihe
        && $status->platz === $plaetze[$k]->platz)
        $correctPlatz = true;
}
if (!$correctPlatz) {
    echo "Error: Angegebener PlatzStatus ist kein gueltiger Sitzplatz \n";
    $correct = false;
}

$correctVorgang = !isset($status->vorgangsNr);
for ($k = 0; $k < count($vorgaenge); $k++) {
    if (@$status->vorgangsNr === @$vorgaenge[$k]->nummer) {
        $correctVorgang = true;
        if ($status->status != "anwesend") {
            if ($vorgaenge[$k]->bezahlung == "bezahlt")
                $status->status = "gebucht";
            else
                $status->status = "reserviert";
        }
        $belongingVorgang = $vorgaenge[$k];
        break;
    }
}
if (!$correctVorgang) {
    echo "Error: Angegebener PlatzStatus gehoert zu keinem gueltigen Vorgang \n";
    $correct = false;
}

// Überprüfe, ob platzStatus überschrieben werden kann, und lösche ihn
if ($correct) {
    for ($k = count($platzStatusse) - 1; $k >= 0; $k--) {
        if ($status->date === $platzStatusse[$k]->date
            && $status->time === $platzStatusse[$k]->time
            && $status->block === $platzStatusse[$k]->block
            && $status->reihe === $platzStatusse[$k]->reihe
            && $status->platz === $platzStatusse[$k]->platz) {
            if (isset($platzStatusse[$k]->vorgangsNr)) {
                if (!isset($status->vorgangsNr)) {
                    // Wenn dieser Platz einem Vorgang entfernt wurde, sollen dessen Theaterkarten aktualisiert werden
                    for ($i = 0; $i < count($vorgaenge); $i++) {
                        if ($platzStatusse[$k]->vorgangsNr === @$vorgaenge[$i]->nummer) {
                            $belongingVorgang = $vorgaenge[$i];
                            break;
                        }
                    }
                    array_splice($platzStatusse, $k, 1);

                } else if ($platzStatusse[$k]->vorgangsNr === $status->vorgangsNr) {
                    if ($status->status == "anwesend" && $platzStatusse[$k]->status == "anwesend") {
                        echo "Error: Angegebener PlatzStatus ist bereits als anwesend gespeichert \n";
                        $correct = false;
                    } else {
                        array_splice($platzStatusse, $k, 1);
                    }
                } else {
                    echo "Error: Angegebener PlatzStatus bereits durch anderen Vorgang belegt \n";
                    $correct = false;
                }
            } else if ($platzStatusse[$k]->status === "gesperrt" && isset($status->vorgangsNr)) {
                echo "Error: Angegebener PlatzStatus ist gesperrt \n";
                $correct = false;
            } else if ($status->status === "gesperrt" && isset($platzStatusse[$k]->vorgangsNr)) {
                echo "Error: Angegebener PlatzStatus ist durch einen Vorgang blockiert \n";
                $correct = false;
            } else if ($status->status === "anwesend") {
                echo "Error: Nicht durch einen Vorgang gebuchte Plätze können nicht als anwesend markiert werden \n";
                $correct = false;
            } else {
                array_splice($platzStatusse, $k, 1);
            }
            break;
        }
    }
    if ($k < 0) { // kein PlatzStatus wurde ersetzt
        if ($status->status === "anwesend") {
            echo "Error: Nicht durch einen Vorgang gebuchte Plätze können nicht als anwesend markiert werden \n";
            $correct = false;
        }
    }
}

if (!$correct) {
    $dbc->unlockDatabase();
    exit;
}

// füge neuen PlatzStatus hinzu
array_push($platzStatusse, $status);

// Überprüfe, ob Theaterkarte des Vorgangs neu erstellt werden muss
if (isset($belongingVorgang) && $belongingVorgang->nummer !== @$status->vorgangsNr && isset($belongingVorgang->theaterkarte)) {
    $ticketGenerator = new generation\TicketGenerator($dbc, $belongingVorgang);
    $ticketGenerator->setPlatzStatusse($platzStatusse);
    // Neue Theaterkarte erstellen
    try {
        $ticketGenerator->generateTicket();
        $ticketGenerator->saveTicket();
    } catch (Exception $exception) {
        echo "Error: Theaterkarte des zugehörigen Vorgangs konnte nicht aktualisiert werden.";
        $dbc->unlockDatabase();
        exit;
    }
    // Alte Theaterkarte löschen
    if ($belongingVorgang->theaterkarte !== $ticketGenerator->getTicketURL()) {
        $ticketGenerator->deleteExistingTicket();
        $vorgang->theaterkarte = $ticketGenerator->getTicketURL();
        $dbc->setVorgaengeJson($vorgaenge);
    }
}

// schreibe Datenbank
$dbc->setPlatzStatusseJson($platzStatusse);

$dbc->unlockDatabase();

// gib gespeicherten Status zurück
header("Content-Type: application/json");
echo json_encode($status);
