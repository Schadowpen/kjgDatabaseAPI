<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, true, false))
    exit;

// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
if (isset($_GET['archive'])) {
    $dbc = $dbo->getArchiveDatabaseConnection($_GET['archive'], true);
} else {
    $dbc = $dbo->getCurrentDatabaseConnection();
}
if ($dbc == false)
    exit;
if (!$dbc->readlockDatabase())
    exit;

// lese Datenbank
$veranstaltung = $dbc->getVeranstaltungJson();
$plaetze = $dbc->getPlaetzeJson();
$vorstellungen = $dbc->getVorstellungenJson();
$platzStatusse = $dbc->getPlatzStatusseJson();
$vorgaenge = $dbc->getVorgaengeJson();

$dbc->unlockDatabase();

// Verbinde Daten zu Output
$anzahlPlaetze = count($plaetze);
$output = (object)[
    'veranstaltung' => $veranstaltung->veranstaltung,
    'kartenPreis' => $veranstaltung->kartenPreis,
    'versandPreis' => $veranstaltung->versandPreis,
    'postEinnahmen' => 0,
    'postGezahlteEinnahmen' => 0
];

// berechne Einnahmen durch Post
for ($i = 0; $i < count($vorgaenge); $i++) {
    if ($vorgaenge[$i]->versandart == "Post") {
        $output->postEinnahmen += $veranstaltung->versandPreis;
        if ($vorgaenge[$i]->bezahlung == "bezahlt")
            $output->postGezahlteEinnahmen += $veranstaltung->versandPreis;
    }
}

// Berechne für jede Vorstellung die Daten
$output->data = [];
for ($i = 0; $i < count($vorstellungen); $i++) {
    $data = (object)[
        "date" => $vorstellungen[$i]->date,
        "time" => $vorstellungen[$i]->time,
        "verfuegbar" => $anzahlPlaetze,
        "reserviert" => 0,
        "gebucht" => 0,
        "VIP" => 0,
        "TripleA" => 0,
        "einnahmen" => 0,
        "gezahlteEinnahmen" => 0,
    ];

    // gehe alle PlatzStatusse für diese Vorstellung durch
    for ($k = 0; $k < count($platzStatusse); $k++) {
        if ($platzStatusse[$k]->date == $data->date && $platzStatusse[$k]->time == $data->time) {

            // Wenn Platz gesperrt, verringere Verfügbare Plätze
            if ($platzStatusse[$k]->status == "gesperrt") {
                $data->verfuegbar--;

            } else if ($platzStatusse[$k]->status == "frei") {
                // freie Plätze sind bereits eingerechnet

            } else { // "reserviert", "gebucht" oder "anwesend"
                // Platz gehört zu einem Vorgang
                $vorgang = null;
                for ($j = 0; $j < count($vorgaenge); $j++) {
                    if ($vorgaenge[$j]->nummer == @$platzStatusse[$k]->vorgangsNr) {
                        $vorgang = $vorgaenge[$j];
                        break;
                    }
                }
                if ($vorgang == null) {
                    die("Error: platzStatus ohne zugehörigem Vorgang: " . json_encode($platzStatusse[$k]));
                }

                // Ob Zuschauer tatsächlich anwesend waren, ist hier nicht relevant. Lediglich, ob sie gezahlt haben
                if ($platzStatusse[$k]->status == "anwesend") {
                    if ($vorgang->bezahlung == "offen")
                        $platzStatusse[$k]->status = "reserviert"; // nicht bezahlt
                    else // "bezahlt" und "Abendkasse"
                        $platzStatusse[$k]->status = "gebucht"; // bezahlt
                }

                // Wenn Platz reserviert, wurde noch nicht bezahlt
                if ($platzStatusse[$k]->status == "reserviert") {
                    $data->reserviert++;
                    for ($j = 0; $j < count($vorgaenge); $j++) {
                        if ($vorgaenge[$j]->nummer == @$platzStatusse[$k]->vorgangsNr) {
                            if ($vorgaenge[$j]->bezahlart != "VIP" && $vorgaenge[$j]->bezahlart != "TripleA") {
                                $data->einnahmen += ($vorgaenge[$j]->preis != null ? $vorgaenge[$j]->preis : $veranstaltung->kartenPreis);
                            }
                            break;
                        }
                    }

                    // Wenn Platz gebucht, wurde bereits gezahlt. VIP und TripleA bekommen Platzkarten kostenlos
                } else if ($platzStatusse[$k]->status == "gebucht") {
                    for ($j = 0; $j < count($vorgaenge); $j++) {
                        if ($vorgaenge[$j]->nummer == $platzStatusse[$k]->vorgangsNr) {
                            if ($vorgaenge[$j]->bezahlart == "VIP") {
                                $data->VIP++;
                            } else if ($vorgaenge[$j]->bezahlart == "TripleA") {
                                $data->TripleA++;
                            } else {
                                $data->gebucht++;
                                $preis = $vorgaenge[$j]->preis != null ? $vorgaenge[$j]->preis : $veranstaltung->kartenPreis;
                                $data->einnahmen += $preis;
                                $data->gezahlteEinnahmen += $preis;
                            }
                            break;
                        }
                    }
                }
            }
        }
    }
    $output->data[$i] = $data;
}

// output
header("Content-Type: application/json");
$output = json_encode($output);
echo $output;
