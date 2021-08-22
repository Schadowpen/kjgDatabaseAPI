<?php
require_once "autoload.php";
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Überprüfe Input auf Korrektheit
$correct = true;

if (!keyValid(true))
    $correct = false;
if (!checkDatabaseUsageAllowed(true, true, false))
    $correct = false;

$forVorstellung = isset($_GET["date"]) || isset($_GET["time"]);

if ($forVorstellung) {
    if (!isset($_GET["date"])) {
        echo "Error: Kein date angegeben \n";
        $correct = false;
    } else if (!is_string($_GET["date"])) {
        echo "Error: Falscher Datentyp von date \n";
        $correct = false;
    } elseif (!preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", $_GET["date"])) {
        echo "Error: date muss das Format YYYY-MM-DD haben \n";
        $correct = false;
    }

    if (!isset($_GET["time"])) {
        echo "Error: Kein time angegeben \n";
        $correct = false;
    } else if (!is_string($_GET["time"])) {
        echo "Error: Falscher Datentyp von time \n";
        $correct = false;
    } elseif (!preg_match("/[0-9]{2}:[0-9]{2}/", $_GET["time"])) {
        echo "Error: time muss das Format hh:mm haben \n";
        $correct = false;
    }
}

if (!$correct)
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

// print as XLSX file
try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    if ($forVorstellung) {
        $sheet->setCellValue('A1', 'Vorname')
            ->setCellValue('B1', 'Nachname')
            ->setCellValue('C1', 'Anschrift')
            ->setCellValue('D1', 'E-Mail')
            ->setCellValue('E1', 'Telefon')
            ->setCellValue('F1', 'Block')
            ->setCellValue('G1', 'Platz')
            ->setCellValue('H1', 'Kommentar');
        $lastColumn = 'H';
    } else {
        $sheet->setCellValue('A1', 'Datum')
            ->setCellValue('B1', 'Uhrzeit')
            ->setCellValue('C1', 'Vorname')
            ->setCellValue('D1', 'Nachname')
            ->setCellValue('E1', 'Anschrift')
            ->setCellValue('F1', 'E-Mail')
            ->setCellValue('G1', 'Telefon')
            ->setCellValue('H1', 'Block')
            ->setCellValue('I1', 'Platz')
            ->setCellValue('J1', 'Kommentar');
        $lastColumn = 'J';
    }
    for ($i = 0; $i < count($veranstaltung->additionalFieldsForVorgang); $i++) {
        $lastColumn ++;
        $sheet->setCellValue($lastColumn . '1', $veranstaltung->additionalFieldsForVorgang[$i]->description);
        $veranstaltung->additionalFieldsForVorgang[$i]->column = $lastColumn;
        if ($lastColumn == 'Z')
            break;
    }
    $sheet->getStyle('A1:'.$lastColumn.'1' )->applyFromArray(['font' => ['bold' => true]]);

    // print every viewer
    $sheetRow = 2;
    for ($i = 0; $i < count($platzStatusse); $i++) {
        if ((!$forVorstellung || ($platzStatusse[$i]->date == $_GET["date"] && $platzStatusse[$i]->time == $_GET["time"]))
                    && isset($platzStatusse[$i]->vorgangsNr)) {
            for ($j = 0; $j < count($vorgaenge); $j++) {
                if ($vorgaenge[$j]->nummer == $platzStatusse[$i]->vorgangsNr) {
                    $vorgang = $vorgaenge[$j];
                    if ($forVorstellung) {
                        $sheet->fromArray(array(
                            $vorgang->vorname,
                            $vorgang->nachname,
                            $vorgang->anschrift,
                            $vorgang->email,
                            $vorgang->telefon,
                            $platzStatusse[$i]->block,
                            $platzStatusse[$i]->reihe . $platzStatusse[$i]->platz,
                            $vorgang->kommentar),
                            null, 'A' . $sheetRow);
                    } else {
                        $sheet->fromArray(array(
                            $platzStatusse[$i]->date,
                            $platzStatusse[$i]->time,
                            $vorgang->vorname,
                            $vorgang->nachname,
                            $vorgang->anschrift,
                            $vorgang->email,
                            $vorgang->telefon,
                            $platzStatusse[$i]->block,
                            $platzStatusse[$i]->reihe . $platzStatusse[$i]->platz,
                            $vorgang->kommentar),
                            null, 'A' . $sheetRow);
                    }
                    for ($k = 0; $k < count($veranstaltung->additionalFieldsForVorgang); $k++) {
                        $sheet->setCellValue($veranstaltung->additionalFieldsForVorgang[$k]->column . $sheetRow, $vorgang->{$veranstaltung->additionalFieldsForVorgang[$k]->fieldName});
                    }
                    $sheetRow ++;
                    break;
                }
            }
        }
    }

    // setup column widths
    for ($column = 'A'; $column <= $lastColumn; $column++)
        $sheet->getColumnDimension($column)->setAutoSize(true);
    $sheet->calculateColumnWidths();
    $sheet->freezePane($forVorstellung ? 'C2' : 'E2');

    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $fileName = 'Zuschauer ' . $veranstaltung->veranstaltung . ($forVorstellung ? (' ' . $_GET["date"] . ' ' . $_GET["time"]) : '') . '.xlsx';
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    $writer->save("php://output");
} catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
    die("Error: {$e->getMessage()}\n");
} catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
    die("Error: {$e->getMessage()}\n");
}