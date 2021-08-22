<?php
require "autoload.php";

// Überprüfe Input auf Korrektheit
if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, false, false))
    exit;

if (!isset($_GET['nummer'])) {
    echo "Error: No Vorgang nummer defined in url variables \n";
    exit;
}
if ($_GET['nummer'] < 0) {
    echo "Error: Only positive numbers for Vorgang Nummer allowed \n";
    exit;
}

// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getCurrentDatabaseConnection();
if (!$dbc->writelockDatabase())
    exit;

// lese Datenbank
$vorgaenge = $dbc->getVorgaengeJson();
$vorgang = null;
for ($i = 0; $i < count($vorgaenge); $i++) {
    if ($vorgaenge[$i]->nummer == $_GET['nummer']) {
        $vorgang = $vorgaenge[$i];
        break;
    }
}
if ($vorgang === null) {
    $dbc->unlockDatabase();
    echo "Error: Could not find Vorgang with nummer={$_GET['nummer']}\n";
    exit;
}
if ($vorgang->bezahlung === "offen") {
    $dbc->unlockDatabase();
    echo "Error: Not allowed to generate Theaterkarte for unpayed Vorgang\n";
    exit;
}

try {
    // Generiere Ticket
    $ticketGenerator = new generation\TicketGenerator($dbc, $vorgang);
    $ticketGenerator->generateTicket();
    $ticketGenerator->saveTicket();

    // Speichere, dass Ticket generiert wurde, in der Datenbank
    $vorgang->theaterkarte = $ticketGenerator->getTicketURL();
    $dbc->setVorgaengeJson($vorgaenge);

    // Trenne von Datenbank
    $dbc->unlockDatabase();

    // output
    header("Content-Type: application/json");
    echo json_encode($vorgang);

} catch (Throwable $exception) {
    $dbc->unlockDatabase();
    echo "Error: " . $exception->getMessage() . "\n" . $exception->getTraceAsString() . "\n";
    exit;
}