<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, true, true))
    exit;
if (isset($_GET["archive"]) && isset($_GET["template"]))
    die("Error: Both template and archive is given. Only one of them is allowed\n");

// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
if (isset($_GET["archive"])) {
    $dbc = $dbo->getArchiveDatabaseConnection($_GET["archive"]);
} elseif (isset($_GET["template"])) {
    $dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
} else {
    $dbc = $dbo->getCurrentDatabaseConnection();
}
if ($dbc == false)
    exit;
if (!$dbc->readlockDatabase())
    exit;

// Pseudodaten
$vorgang = (object)[
    "nummer" => 0,
    "vorname" => "Max",
    "nachname" => "Mustermann",
    "email" => "max.mustermann@mustermail.de",
    "bezahlart" => "Bar",
    "bezahlung" => "DEMO Eintrittskarte",
    "versandart" => "E-Mail",
    "preis" => -100
];
$vorstellungen = $dbc->getVorstellungenJson();
if ($dbc instanceof \database\TemplateDatabaseConnection) {
    $plaetze = \database\DatabaseOverview::getPlaetzeIncludingPlatzGruppe($dbc);
} else {
    $plaetze = $dbc->getPlaetzeJson();
}
if (count($vorstellungen) === 0)
    die("Error: Demo Theaterkarte konnte nicht erstellt werden, es existiert noch keine einzige Vorstellung \n");
if (count($plaetze) === 0)
    die("Error: Demo Theaterkarte konnte nicht erstellt werden, es existiert noch kein einziger Sitzplatz \n");
$platzStatusse = [
    (object)[
        "date" => $vorstellungen[0]->date,
        "time" => $vorstellungen[0]->time,
        "block" => $plaetze[0]->block,
        "reihe" => $plaetze[0]->reihe,
        "platz" => $plaetze[0]->platz,
        "status" => "gebucht",
        "vorgangsNr" => 0
    ]
];

try {
    // Daten übergeben
    $ticketGenerator = new generation\TicketGenerator($dbc, $vorgang);
    $ticketGenerator->setPlaetze($plaetze);
    $ticketGenerator->setPlatzStatusse($platzStatusse);
    $ticketGenerator->loadData();
    $dbc->unlockDatabase();

    // Generiere Ticket
    $ticketGenerator->generateTicket();
    $pdfContent = $ticketGenerator->getTicketContent();

    // output
    header("Content-Type: application/pdf");
    echo $pdfContent;

} catch (Throwable $exception) {
    $dbc->unlockDatabase();
    echo "Error: " . $exception->getMessage() . "\n" . $exception->getTraceAsString() . "\n";
    exit;
}