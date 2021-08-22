<?php


namespace generation;

use pdf\graphics\operator\AbstractImageOperator;
use pdf\graphics\Point;
use pdf\PdfDocument;

/**
 * Diese Klasse findet die Automatische Konfiguration für die unterschiedlichen Objekte in der Theaterkarte in einem Pdf-Dokument, welches die Vorlage für die Theaterkarte darstellet.
 * @package templates
 */
class AutoConfig
{
    /**
     * Errechnete Konfiguration für den QR-Code
     * @var array|null
     */
    public $qrCodeConfig = null;
    /**
     * Errechnete Konfiguration für den Sitzplan
     * @var array|null
     */
    public $sitzplanConfig = null;
    /**
     * Errechnete Konfiguration für den Text mit dem Datum
     * @var array|null
     */
    public $dateTextConfig = null;
    /**
     * Errechnete Konfiguration für den Text mit der Uhrzeit
     * @var array|null
     */
    public $timeTextConfig = null;
    /**
     * Errechnete Konfiguration für den Text mit dem Block (im Sitzplan)
     * @var array|null
     */
    public $blockTextConfig = null;
    /**
     * Errechnete Konfiguration für den Text mit der Reihe (im Sitzplan)
     * @var array|null
     */
    public $reiheTextConfig = null;
    /**
     * Errechnete Konfiguration für den Text mit der Platznummer (im Sitzplan)
     * @var array|null
     */
    public $platzTextConfig = null;
    /**
     * Errechnete Konfiguration für den Text mit dem Beitrag aka. Eintrittspreis
     * @var array|null
     */
    public $preisTextConfig = null;
    /**
     * Errechnete Konfiguration für den Text mit dem Bezahlungsstatus
     * @var array|null
     */
    public $bezahlstatusTextConfig = null;
    /**
     * Errechnete Konfiguration für den Text mit der Vorgangsnummer
     * @var array|null
     */
    public $vorgangsNummerTextConfig = null;

    /**
     * PositionFinder constructor.
     * Der Konstruktor führt auch direkt den Findungsalgorithmus durch.
     * @param PdfDocument $pdfDocument Vorlage für eine Theaterkarte
     * @throws \Exception Wenn die Vorlage nicht korrekt analysiert werden konnte
     */
    public function __construct(PdfDocument $pdfDocument)
    {
        // Start Analyse
        if ($pdfDocument->getPageList()->getPageCount() !== 1)
            throw new \Exception("Only PDF Documents with one Page can be used as Template");
        $page = $pdfDocument->getPageList()->getPage(0);
        $contentStream = $page->getContents();
        $analyzedContentStream = new \pdf\graphics\AnalyzedContentStream(new \pdf\graphics\state\GraphicsStateStack(new \pdf\graphics\TransformationMatrix(), $page->getCropBox()), $contentStream);


        // Detektion mit Bildern
        $imageOperators = $analyzedContentStream->getImageOperators();
        $imageOperatorCount = count($imageOperators);
        if ($imageOperatorCount > 0) {

            // Finden der Position für den Saalplan
            $sitzplanIndex = null;
            $sitzplanValue = INF;
            for ($i = 0; $i < $imageOperatorCount; ++$i) {
                $imageOperator = $imageOperators[$i];
                $value = $this->calcDiagonalValue($imageOperator->getLowerLeftCorner());
                if ($value < $sitzplanValue) {
                    $sitzplanValue = $value;
                    $sitzplanIndex = $i;
                }
            }

            // Speichern in SitzplanConfig
            if ($sitzplanIndex !== null) {
                $sitzplanOperator = $imageOperators[$sitzplanIndex];
                $deletionRange = $analyzedContentStream->getDeletableRangeForOperator($sitzplanOperator->getOperatorNumber());
                $this->sitzplanConfig = [
                    "operatorNumber" => $sitzplanOperator->getOperatorNumber(), // Nummer über ALLE Operatoren, nicht nur imageOperatoren
                    "operatorName" => $sitzplanOperator->getName(),
                    "resourceDeletable" => $sitzplanOperator->getName() !== "Inline Image"
                        && ($this->countImageOperatorsWithName($imageOperators, $imageOperatorCount, $sitzplanOperator->getName()) === 1),
                    "deleteStartIndex" => $deletionRange->getStartIndex(),
                    "deleteEndIndex" => $deletionRange->getEndIndex(),
                    "lowerLeftCorner" => $sitzplanOperator->getLowerLeftCorner(),
                    "lowerRightCorner" => $sitzplanOperator->getLowerRightCorner(),
                    "upperLeftCorner" => $sitzplanOperator->getUpperLeftCorner(),
                    "upperRightCorner" => $sitzplanOperator->getUpperRightCorner(),
                    "font" => "Times-Roman",
                    "fontSize" => 12,
                    "seatNumbersVisible" => false,
                    "lineWidth" => 3,
                    "connectEntranceArrows" => true,
                ];
            }


            // Finden der Position für den QR-Code
            $cropBoxXCenter = ($page->getCropBox()->getLowerLeftX() + $page->getCropBox()->getUpperRightX()) / 2.0;
            $qrCodeIndex = null;
            $qrCodeValue = -INF;
            for ($i = 0; $i < $imageOperatorCount; ++$i) {
                $imageOperator = $imageOperators[$i];
                $lowerLeftCorner = $imageOperator->getLowerLeftCorner();
                $lowerRightCorner = $imageOperator->getLowerRightCorner();
                $upperLeftCorner = $imageOperator->getUpperLeftCorner();
                $upperRightCorner = $imageOperator->getUpperRightCorner();
                if ($lowerLeftCorner->x < $cropBoxXCenter
                    || $lowerRightCorner->x < $cropBoxXCenter
                    || $upperLeftCorner->x < $cropBoxXCenter
                    || $upperRightCorner->x < $cropBoxXCenter)
                    continue; // Nur Bilder, die komplett auf der rechten Hälfte der PDF sind.

                // Etwaige Verdrehung des QR-Codes zulassen
                $value = max(
                    $this->calcDiagonalValue($lowerLeftCorner),
                    $this->calcDiagonalValue($lowerRightCorner),
                    $this->calcDiagonalValue($upperLeftCorner),
                    $this->calcDiagonalValue($upperRightCorner)
                );
                if ($value > $qrCodeValue) {
                    $qrCodeValue = $value;
                    $qrCodeIndex = $i;
                }
            }

            // Wenn Saalplan und QR-Code das selbe imageObject nutzen wollen
            if ($qrCodeIndex === $sitzplanIndex) {
                $qrCodeIndex = null;
            }

            // Speichern in QRCodeConfig
            if ($qrCodeIndex !== null) {
                $qrCodeOperator = $imageOperators[$qrCodeIndex];
                $deletionRange = $analyzedContentStream->getDeletableRangeForOperator($qrCodeOperator->getOperatorNumber());
                $this->qrCodeConfig = [
                    "operatorNumber" => $qrCodeOperator->getOperatorNumber(), // Nummer über ALLE Operatoren, nicht nur imageOperatoren
                    "operatorName" => $qrCodeOperator->getName(),
                    "resourceDeletable" => $qrCodeOperator->getName() !== "Inline Image"
                        && ($this->countImageOperatorsWithName($imageOperators, $imageOperatorCount, $qrCodeOperator->getName()) === 1),
                    "deleteStartIndex" => $deletionRange->getStartIndex(),
                    "deleteEndIndex" => $deletionRange->getEndIndex(),
                    "lowerLeftCorner" => $qrCodeOperator->getLowerLeftCorner(),
                    "lowerRightCorner" => $qrCodeOperator->getLowerRightCorner(),
                    "upperLeftCorner" => $qrCodeOperator->getUpperLeftCorner(),
                    "upperRightCorner" => $qrCodeOperator->getUpperRightCorner()
                ];
            }
        }
        // Aufräumen für Debuggen und Performance
        unset($sitzplanValue);
        unset($sitzplanIndex);
        unset($sitzplanOperator);
        unset($qrCodeValue);
        unset($qrCodeIndex);
        unset($qrCodeOperator);
        unset($lowerLeftCorner);
        unset($lowerRightCorner);
        unset($upperLeftCorner);
        unset($upperRightCorner);
        unset($imageOperator);
        unset($imageOperatorCount);
        unset($imageOperators);
        unset($deletionRange);


        // Detektion mit Text
        $textOperators = $analyzedContentStream->getTextOperators();
        $textOperatorCount = count($textOperators);
        /** @var array $textFindData Daten zu den einzelnen, gefundenen Texten. Der Inhalt des Textes ist gleichzeitig der Schlüssel in dem Array zu den Daten */
        $textFindData = [
            "dateTextConfig" => ["text" => "Datum"],
            "timeTextConfig" => ["text" => "Uhrzeit"],
            "blockTextConfig" => ["text" => "Block"],
            "reiheTextConfig" => ["text" => "Reihe"],
            "platzTextConfig" => ["text" => "Platz"],
            "preisTextConfig" => ["text" => "Beitrag"],
            "vorgangsNummerTextConfig" => ["text" => "Buchungsnummer"]
        ];
        $textFindDataCount = 0;
        $avgTextStartX = 0;
        $avgTextCenterX = 0;
        $avgTextEndX = 0;

        // Für alle Texte den TextOperatoren finden
        foreach ($textFindData as $findDataKey => &$textFindDatum) {
            for ($i = 0; $i < $textOperatorCount; ++$i) {
                $textOperatorText = $textOperators[$i]->getText();
                // Nur TextOperatoren, deren Texte maximal 2 länger sind als die Suchtexte und wo der Suchtext komplett drin enthalten ist.
                if (strlen($textOperatorText) > strlen($textFindDatum["text"]) + 2)
                    continue;
                $strPos = strpos($textOperatorText, $textFindDatum["text"]);
                if ($strPos === false || $strPos > 2)
                    continue;

                // Sollten zwei oder mehrere TextOperatoren mit diesem Text gefunden werden, nehme denjenigen, der näher an der rechten oberen Ecke ist
                $startPos = $textOperators[$i]->getStartPos();
                $endPos = $textOperators[$i]->getEndPos();
                $centerPos = new Point(($startPos->x + $endPos->x) / 2, ($startPos->y + $endPos->y) / 2);
                if (@$textFindDatum["centerPos"] !== null && $this->calcDiagonalValue($textFindDatum["centerPos"]) > $this->calcDiagonalValue($centerPos))
                    continue; // Bereits gespeicherter Operator ist näher an der oberen linken Ecke

                // relevante Daten speichern
                $textFindDatum["startPos"] = $startPos;
                $textFindDatum["endPos"] = $endPos;
                $textFindDatum["centerPos"] = $centerPos;
                $textFindDatum["fontSize"] = $textOperators[$i]->getFontSize();
            }

            // Überprüfe ob gefunden
            if (@$textFindDatum["centerPos"] !== null) {
                ++$textFindDataCount;
                $avgTextStartX += $textFindDatum["startPos"]->x;
                $avgTextCenterX += $textFindDatum["centerPos"]->x;
                $avgTextEndX += $textFindDatum["endPos"]->x;
            } else {
                unset($textFindData[$findDataKey]);
            }
        }

        // Wenn keinerlei Texte konfiguriert werden können, hier bereits abbrechen
        if ($textFindDataCount == 0)
            return;

        // Berechne Mittelwerte
        $avgTextStartX /= $textFindDataCount;
        $avgTextCenterX /= $textFindDataCount;
        $avgTextEndX /= $textFindDataCount;
        // Berechne Varianz
        $varTextStartX = 0;
        $varTextCenterX = 0;
        $varTextEndX = 0;
        foreach ($textFindData as &$textFindDatum) {
            $varTextStartX += ($textFindDatum["startPos"]->x - $avgTextStartX) * ($textFindDatum["startPos"]->x - $avgTextStartX);
            $varTextCenterX += ($textFindDatum["centerPos"]->x - $avgTextCenterX) * ($textFindDatum["centerPos"]->x - $avgTextCenterX);
            $varTextEndX += ($textFindDatum["endPos"]->x - $avgTextEndX) * ($textFindDatum["endPos"]->x - $avgTextEndX);
        }
        if ($varTextStartX < $varTextCenterX && $varTextStartX < $varTextEndX) {
            // Text linksbündig ausrichten
            if (@$textFindData["preisTextConfig"] !== null) {
                $this->bezahlstatusTextConfig = $this->getDefaultTextConfig(
                    new Point($avgTextStartX, $textFindData["preisTextConfig"]["startPos"]->y - $textFindData["preisTextConfig"]["fontSize"] * 3.0),
                    TextAlignment::alignLeft,
                    $textFindData["preisTextConfig"]["fontSize"]
                );
            }
            foreach ($textFindData as $key => &$textFindDatum) {
                $this->$key = $this->getDefaultTextConfig(
                    new Point($avgTextStartX, $textFindDatum["startPos"]->y - $textFindDatum["fontSize"] * 1.5),
                    TextAlignment::alignLeft,
                    $textFindDatum["fontSize"]
                );
            }

        } else if ($varTextCenterX < $varTextEndX) {
            // Text zentriert ausrichten
            if (@$textFindData["preisTextConfig"] !== null) {
                $this->bezahlstatusTextConfig = $this->getDefaultTextConfig(
                    new Point($avgTextCenterX, $textFindData["preisTextConfig"]["centerPos"]->y - $textFindData["preisTextConfig"]["fontSize"] * 3.0),
                    TextAlignment::alignCenter,
                    $textFindData["preisTextConfig"]["fontSize"]
                );
            }
            foreach ($textFindData as $key => &$textFindDatum) {
                $this->$key = $this->getDefaultTextConfig(
                    new Point($avgTextCenterX, $textFindDatum["centerPos"]->y - $textFindDatum["fontSize"] * 1.5),
                    TextAlignment::alignCenter,
                    $textFindDatum["fontSize"]
                );
            }

        } else {
            // Text rechtsbündig ausrichten
            if (@$textFindData["preisTextConfig"] !== null) {
                $this->bezahlstatusTextConfig = $this->getDefaultTextConfig(
                    new Point($avgTextEndX, $textFindData["preisTextConfig"]["endPos"]->y - $textFindData["preisTextConfig"]["fontSize"] * 3.0),
                    TextAlignment::alignRight,
                    $textFindData["preisTextConfig"]["fontSize"]
                );
            }
            foreach ($textFindData as $key => &$textFindDatum) {
                $this->$key = $this->getDefaultTextConfig(
                    new Point($avgTextEndX, $textFindDatum["endPos"]->y - $textFindDatum["fontSize"] * 1.5),
                    TextAlignment::alignRight,
                    $textFindDatum["fontSize"]
                );
            }
        }
    }

    /**
     * Berechnet für einen Punkt aus seinen Koordinaten x und y einen Wert, der angibt, wie weit er von der unteren linken Ecke der PDF-Seite entfernt ist.
     * @param Point $point Punkt im Device Space
     * @return float x + y
     */
    private function calcDiagonalValue(Point $point)
    {
        return $point->x + $point->y;
    }

    /**
     * Zählt alle ImageOperators mit dem gegebenen Namen
     * @param AbstractImageOperator[] $imageOperators Alle ImageOperators in dem ContentStream
     * @param int $imageOperatorCount count($imageOperators)
     * @param string $name Name eines ImageOperators
     * @return int Anzahl der Operatoren mit dem Namen
     */
    public static function countImageOperatorsWithName(array $imageOperators, $imageOperatorCount, string $name)
    {
        $count = 0;
        for ($i = 0; $i < $imageOperatorCount; ++$i) {
            if ($imageOperators[$i]->getName() === $name)
                ++$count;
        }
        return $count;
    }

    /**
     * Liefert die Standardkonfiguration für einen platzierbaren Text.
     * @param Point $position Position des Textes
     * @param int $alignment Ob der Text linksbündig (0), zentriert (1) oder rechtsbündig (2) Ausgerichtet ist
     * @param float $fontSize Schriftgröße
     * @return array
     */
    private function getDefaultTextConfig(Point $position, int $alignment, float $fontSize)
    {
        return [
            "position" => $position,
            "alignment" => $alignment,
            "font" => "Courier",
            "fontSize" => $fontSize,
            "color" => ["r" => 0, "g" => 0, "b" => 0]
        ];
    }
}