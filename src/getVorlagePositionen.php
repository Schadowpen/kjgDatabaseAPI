<?php
require "autoload.php";

if (!keyValid())
    exit;
if (!checkDatabaseUsageAllowed(true, true, true))
    exit;
if (isset($_GET["archive"]) && isset($_GET["template"]))
    die("Error: Both template and archive is given. Only one of them is allowed\n");

// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
if (isset($_GET['archive'])) {
    $dbc = $dbo->getArchiveDatabaseConnection($_GET['archive'], true);
} elseif (isset($_GET["template"])) {
    $dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
} else {
    $dbc = $dbo->getCurrentDatabaseConnection();
}
if ($dbc == false)
    exit;
if (!$dbc->readlockDatabase())
    exit;

// lese Vorlage PDF aus Datenbank
$kartenVorlageString = $dbc->getKartenVorlageString();
if ($kartenVorlageString === false) {
    $dbc->unlockDatabase();
    echo "Error: Could not read kartenVorlage from database\n";
    exit;
}
$dbc->unlockDatabase();

try {
    $pdfFile = new pdf\PdfFile(new misc\StringReader($kartenVorlageString));
    $pdfDocument = new pdf\PdfDocument($pdfFile);
    if ($pdfDocument->getPageList()->getPageCount() !== 1) {
        echo "Error: PDF-Datei hat nicht exakt eine Seite!\n";
        exit;
    }

    $defaultPage = $pdfDocument->getPageList()->getPage(0);
    $contentStream = $defaultPage->getContents();
    $cropBox = $defaultPage->getCropBox();
    $analyzedContentStream = new pdf\graphics\AnalyzedContentStream(new pdf\graphics\state\GraphicsStateStack(new pdf\graphics\TransformationMatrix(), $cropBox), $contentStream);

    // output
    $output = (object)[
        "cropBox" => [
            "lowerLeftX" => $cropBox->getLowerLeftX(),
            "lowerLeftY" => $cropBox->getLowerLeftY(),
            "upperRightX" => $cropBox->getUpperRightX(),
            "upperRightY" => $cropBox->getUpperRightY()
        ],
        "imageOperators" => [],
        "textOperators" => []
    ];
    foreach ($analyzedContentStream->getImageOperators() as $imageOperator) {
        array_push($output->imageOperators, [
            "operatorNumber" => $imageOperator->getOperatorNumber(),
            "lowerLeftCorner" => $imageOperator->getLowerLeftCorner(),
            "lowerRightCorner" => $imageOperator->getLowerRightCorner(),
            "upperLeftCorner" => $imageOperator->getUpperLeftCorner(),
            "upperRightCorner" => $imageOperator->getUpperRightCorner(),
            "name" => $imageOperator->getName()
        ]);
    }
    foreach ($analyzedContentStream->getTextOperators() as $textOperator) {
        array_push($output->textOperators, [
            "operatorNumber" => $textOperator->getOperatorNumber(),
            "text" => $textOperator->getTextUTF8(),
            "font" => $textOperator->getFont(),
            "fontSize" => $textOperator->getFontSize(),
            "startPoint" => $textOperator->getStartPos(),
            "endPoint" => $textOperator->getEndPos()
        ]);
    }

    header("Content-Type: application/json");
    echo json_encode($output, JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (Throwable $exception) {
    echo "Error: " . $exception->getMessage() . "\n" . $exception->getTraceAsString() . "\n";
    exit;
}