<?php


namespace generation;

use database\DatabaseConnection;
use misc\StringReader;
use pdf\document\ContentStream;
use pdf\document\Font;
use pdf\document\GraphicsStateParameterDictionary;
use pdf\document\PdfDate;
use pdf\document\XObjectImage;
use pdf\graphics\AnalyzedContentStream;
use pdf\graphics\ColorRGB;
use pdf\graphics\GenerateContentStream;
use pdf\graphics\operator\BeginTextObjectOperator;
use pdf\graphics\operator\CharacterSpaceOperator;
use pdf\graphics\operator\ClippingPathNonzeroOperator;
use pdf\graphics\operator\ColorRGBFillingOperator;
use pdf\graphics\operator\ColorRGBStrokingOperator;
use pdf\graphics\operator\EndTextObjectOperator;
use pdf\graphics\operator\ExternalGraphicsStateOperator;
use pdf\graphics\operator\ExternalObjectOperator;
use pdf\graphics\operator\FillPathNonzeroOperator;
use pdf\graphics\operator\InlineImageOperator;
use pdf\graphics\operator\LineCapOperator;
use pdf\graphics\operator\LineJoinOperator;
use pdf\graphics\operator\LineWidthOperator;
use pdf\graphics\operator\ModifyTransformationMatrixOperator;
use pdf\graphics\operator\PathBeginOperator;
use pdf\graphics\operator\PathBezierOperator;
use pdf\graphics\operator\PathLineOperator;
use pdf\graphics\operator\PathRectangleOperator;
use pdf\graphics\operator\PopGraphicsStateOperator;
use pdf\graphics\operator\PushGraphicsStateOperator;
use pdf\graphics\operator\StrokePathOperator;
use pdf\graphics\operator\TextFontOperator;
use pdf\graphics\operator\TextMatrixOperator;
use pdf\graphics\operator\TextOperator;
use pdf\graphics\operator\TextRenderModeOperator;
use pdf\graphics\operator\TextRiseOperator;
use pdf\graphics\operator\TextScaleOperator;
use pdf\graphics\operator\WordSpaceOperator;
use pdf\graphics\Point;
use pdf\graphics\state\GraphicsStateStack;
use pdf\graphics\TransformationMatrix;
use pdf\object\PdfArray;
use pdf\object\PdfDictionary;
use pdf\object\PdfHexString;
use pdf\object\PdfName;
use pdf\object\PdfNumber;
use pdf\object\PdfString;
use pdf\PdfDocument;
use pdf\PdfFile;

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../phpqrcode/qrlib.php";

/**
 * Klasse zum Generieren neuer Theaterkarten
 * @package templates
 */
class TicketGenerator
{
    /**
     * Verbindung zum Datensatz, um die benötigten Daten zu erhalten.
     * Es wird angenommen, dass diese die gesamte Zeit mindestens Leseberechtigung besitzt
     * @var DatabaseConnection
     */
    protected $databaseConnection;
    /**
     * Einzelner Vorgang aus der vorgaenge.json, für den eine Theaterkarte erstellt werden soll.
     * @var object
     */
    protected $vorgang;

    // Speichern für einzelne Tabellen aus der Datenbank
    protected $bereiche = null;
    protected $eingaenge = null;
    protected $kartenConfig = null;
    protected $kartenVorlage = null;
    protected $plaetze = null;
    protected $platzStatusse = null;
    protected $veranstaltung = null;

    /**
     * Ob eine Datenkompression hin zu Object Streams erlaubt werden soll.
     * Dies ist vor allem deshalb sinnvoll, da fpdi (also das Framework mit dem getestet wird, ob die generierten Karten gültige PDFs sind) keine Object Streams unterstützt
     * @var bool
     */
    protected $allowDataCompression = true;

    /**
     * Die Generierte Theaterkarten PDF-Datei als String
     * @var string|null
     */
    protected $generatedTicket = null;

    /**
     * Setze eine Datenbanktabelle, damit dieser nicht doppelt geladen werden muss
     * @param array $bereiche
     */
    public function setBereiche($bereiche): void
    {
        $this->bereiche = $bereiche;
    }

    /**
     * Setze eine Datenbanktabelle, damit dieser nicht doppelt geladen werden muss
     * @param array $eingaenge
     */
    public function setEingaenge($eingaenge): void
    {
        $this->eingaenge = $eingaenge;
    }

    /**
     * Setze eine Datenbanktabelle, damit dieser nicht doppelt geladen werden muss
     * @param object $kartenConfig
     */
    public function setKartenConfig($kartenConfig): void
    {
        $this->kartenConfig = $kartenConfig;
    }

    /**
     * Setzt die Kartenvorlage, damit diese nicht doppelt geladen werden muss
     * @param string $kartenVorlage
     */
    public function setKartenVorlage($kartenVorlage): void
    {
        $this->kartenVorlage = $kartenVorlage;
    }

    /**
     * Setze eine Datenbanktabelle, damit dieser nicht doppelt geladen werden muss
     * @param array $plaetze
     */
    public function setPlaetze($plaetze): void
    {
        $this->plaetze = $plaetze;
    }

    /**
     * Setze eine Datenbanktabelle, damit dieser nicht doppelt geladen werden muss
     * @param array $platzStatusse
     */
    public function setPlatzStatusse($platzStatusse): void
    {
        $this->platzStatusse = $platzStatusse;
    }

    /**
     * Setze eine Datenbanktabelle, damit dieser nicht doppelt geladen werden muss
     * @param object $veranstaltung
     */
    public function setVeranstaltung($veranstaltung): void
    {
        $this->veranstaltung = $veranstaltung;
    }

    /**
     * Ob eine Datenkompression hin zu Object Streams erlaubt werden soll (Standardwert true).
     * Dies ist vor allem deshalb sinnvoll, da fpdi (also das Framework mit dem getestet wird, ob die generierten Karten gültige PDFs sind) keine Object Streams unterstützt.
     * @param bool $allowDataCompression
     */
    public function setAllowDataCompression(bool $allowDataCompression): void
    {
        $this->allowDataCompression = $allowDataCompression;
    }

    /**
     * TicketGenerator constructor.
     * @param DatabaseConnection $databaseConnection Verbindung zum Datensatz, um die benötigten Daten zu erhalten.
     * @param object $vorgang Einzelner Vorgang aus der vorgaenge.json, für den eine Theaterkarte erstellt werden soll.
     */
    public function __construct(DatabaseConnection $databaseConnection, $vorgang)
    {
        $this->databaseConnection = $databaseConnection;
        $this->vorgang = $vorgang;
    }

    /**
     * Lädt die Daten, die noch nicht geladen sind.
     */
    public function loadData()
    {
        if ($this->bereiche === null)
            $this->bereiche = $this->databaseConnection->getBereicheJson();
        if ($this->eingaenge === null)
            $this->eingaenge = $this->databaseConnection->getEingaengeJson();
        if ($this->kartenConfig === null) {
            $this->kartenConfig = $this->databaseConnection->getKartenConfigJson();
            if ($this->kartenConfig === false)
                throw new \Exception("Could not read kartenConfig from database");
        }
        if ($this->kartenVorlage === null) {
            $this->kartenVorlage = $this->databaseConnection->getKartenVorlageString();
            if ($this->kartenVorlage === false)
                throw new \Exception("Could not read kartenVorlage from database");
        }
        if ($this->plaetze === null)
            $this->plaetze = $this->databaseConnection->getPlaetzeJson();
        if ($this->platzStatusse === null)
            $this->platzStatusse = $this->databaseConnection->getPlatzStatusseJson();
        if ($this->veranstaltung === null)
            $this->veranstaltung = $this->databaseConnection->getVeranstaltungJson();
    }

    /**
     * Generiert eine Theaterkarte, speichert sie aber noch nicht ab sondern hinterlegt den Inhalt in $this->generatedTicket.
     * @throws \Exception Wenn die Theaterkarte nicht generiert werden kann
     * @see TicketGenerator::saveTicket() zum abspeichern der Theaterkarte an der üblichen Stelle
     * @see TicketGenerator::getTicketContent() um die PDF-Datei an einer unüblichen Stelle abzuspeichern oder etwas anderes damit zu tun.
     */
    public function generateTicket()
    {
        // -----------------------------------
        // | Daten laden und PDF vorbereiten |
        // -----------------------------------

        $this->loadData();
        if ($this->vorgang->bezahlart === "VIP" || $this->vorgang->bezahlart === "TripleA")
            $preisPdfObject = new PdfName($this->vorgang->bezahlart);
        else
            $preisPdfObject = new PdfNumber($this->vorgang->preis);

        // Lade Vorlage
        $pdfFile = new PdfFile(new StringReader($this->kartenVorlage));
        if ($this->allowDataCompression)
            $pdfFile->setMinVersion("1.5"); // Unterstützt ObjectStreams
        $pdfDocument = new PdfDocument($pdfFile);

        // berechne zugehörige PlatzStatusse
        $platzStatusse = [];
        foreach ($this->platzStatusse as $platzStatus) {
            if (@$platzStatus->vorgangsNr === $this->vorgang->nummer)
                array_push($platzStatusse, clone $platzStatus);
        }

        // Vorlage extrahieren und entfernen
        if ($pdfDocument->getPageList()->getPageCount() !== 1)
            throw new \Exception("Only PDF-Files with one Page can be used as Template");
        $templatePage = $pdfDocument->getPageList()->getPage(0);
        $templateContentStream = $templatePage->getContents();
        $pdfDocument->getPageList()->removePage(0);

        // Dokumenteneigenschaften setzen
        $lastModified = PdfDate::parseDateTime(new \DateTime('now'));
        $pdfDocument->getDocumentCatalog()->setPieceInfo("Theaterkarten", new PdfDictionary([
            "LastModified" => $lastModified,
            "VorgangsNummer" => new PdfNumber($this->vorgang->nummer),
            "Preis" => $preisPdfObject,
            "Bezahlung" => new PdfString($this->vorgang->bezahlung),
            "AnzahlKarten" => new PdfNumber(count($platzStatusse))
        ]));
        $pdfDocument->getDocumentInfo()->setTitle(PdfHexString::parseString("\xfe\xff" . iconv("UTF-8", "UTF-16BE",
                "Theaterkarten für " . $this->veranstaltung->veranstaltung
            )));
        $pdfDocument->getDocumentInfo()->setProducer(new PdfString("KjG-Theater Buchungssystem \xa92019, Philipp Horwat"));
        $pdfDocument->getDocumentInfo()->setModificationDate($lastModified);
        $templateContentStream->getResourceDictionary()->addProcSet(new PdfName("PDF"));
        $templateContentStream->getResourceDictionary()->addProcSet(new PdfName("Text"));
        $templateContentStream->getResourceDictionary()->addProcSet(new PdfName("ImageB"));
        $templateContentStream->getResourceDictionary()->addProcSet(new PdfName("ImageC"));


        // ----------------------------------------
        // | Bilder entfernen, die ersetzt werden |
        // ----------------------------------------

        $analyzedContentStream = new AnalyzedContentStream(
            new GraphicsStateStack(new TransformationMatrix(), $templatePage->getCropBox()),
            $templateContentStream
        );
        $qrCodeConfig = @$this->kartenConfig->qrCodeConfig;
        if ($qrCodeConfig !== null && isset($qrCodeConfig->deleteStartIndex)) {
            $analyzedContentStream->deleteOperators(
                $qrCodeConfig->deleteStartIndex,
                $qrCodeConfig->deleteEndIndex
            );
            if ($qrCodeConfig->resourceDeletable)
                $templateContentStream->getResourceDictionary()->removeXObject($qrCodeConfig->operatorName);
        }
        $sitzplanConfig = @$this->kartenConfig->sitzplanConfig;
        if ($sitzplanConfig !== null && isset($sitzplanConfig->deleteStartIndex)) {
            $analyzedContentStream->deleteOperators(
                $sitzplanConfig->deleteStartIndex,
                $sitzplanConfig->deleteEndIndex
            );
            if ($sitzplanConfig->resourceDeletable)
                $templateContentStream->getResourceDictionary()->removeXObject($sitzplanConfig->operatorName);
        }

        // -------------------------------------------------
        // | Erstelle auf allen Seiten genutzte Ressourcen |
        // -------------------------------------------------

        // Erstellen der Fonts
        if (isset($this->kartenConfig->sitzplanConfig))
            $sitzplanFontName = $this->getFontName($this->kartenConfig->sitzplanConfig, $templateContentStream);
        if (isset($this->kartenConfig->dateTextConfig))
            $dateTextFontName = $this->getFontName($this->kartenConfig->dateTextConfig, $templateContentStream);
        if (isset($this->kartenConfig->timeTextConfig))
            $timeTextFontName = $this->getFontName($this->kartenConfig->timeTextConfig, $templateContentStream);
        if (isset($this->kartenConfig->blockTextConfig))
            $blockTextFontName = $this->getFontName($this->kartenConfig->blockTextConfig, $templateContentStream);
        if (isset($this->kartenConfig->reiheTextConfig))
            $reiheTextFontName = $this->getFontName($this->kartenConfig->reiheTextConfig, $templateContentStream);
        if (isset($this->kartenConfig->platzTextConfig))
            $platzTextFontName = $this->getFontName($this->kartenConfig->platzTextConfig, $templateContentStream);
        if (isset($this->kartenConfig->preisTextConfig))
            $preisTextFontName = $this->getFontName($this->kartenConfig->preisTextConfig, $templateContentStream);
        if (isset($this->kartenConfig->bezahlstatusTextConfig))
            $bezahlstatusTextFontName = $this->getFontName($this->kartenConfig->bezahlstatusTextConfig, $templateContentStream);
        if (isset($this->kartenConfig->vorgangsNummerTextConfig))
            $vorgangsNummerTextFontName = $this->getFontName($this->kartenConfig->vorgangsNummerTextConfig, $templateContentStream);
        if (isset($this->kartenConfig->sitzplanConfig)) {
            // Erstelle Images
            $seatGrayOperator = $this->getImageOperator(__DIR__ . "/../images/seat_gray.png", $templateContentStream);
            if ($this->vorgang->bezahlung === "bezahlt") {
                $seatLightColoredOperator = $this->getImageOperator(__DIR__ . "/../images/seat_lightgreen.png", $templateContentStream);
                $seatColoredOperator = $this->getImageOperator(__DIR__ . "/../images/seat_green_selected.png", $templateContentStream);
            } else {
                $seatLightColoredOperator = $this->getImageOperator(__DIR__ . "/../images/seat_lightyellow.png", $templateContentStream);
                $seatColoredOperator = $this->getImageOperator(__DIR__ . "/../images/seat_yellow_selected.png", $templateContentStream);
            }

            // Daten aus plaetze.json in $platzStatusse hinzufügen
            $platzStatusseCount = count($platzStatusse);
            for ($i = 0; $i < $platzStatusseCount; ++$i) {
                $platzStatus = $platzStatusse[$i];
                foreach ($this->plaetze as $platz) {
                    if ($platzStatus->block === $platz->block
                        && $platzStatus->reihe === $platz->reihe
                        && $platzStatus->platz === $platz->platz) {
                        $platzStatus->xPos = $platz->xPos;
                        $platzStatus->yPos = $platz->yPos;
                        $platzStatus->rotation = $platz->rotation;
                        $platzStatus->eingang = $platz->eingang;
                    }
                }
            }
        }
        // QRCode Position
        if ($qrCodeConfig !== null)
            $qrCodeTransformationMatrix = $this->getTransformationMatrixForImageConfig($qrCodeConfig, 1, 1);


        // -----------------------------------------------------
        // | Erstelle Startkonfiguration und Standard-Sitzplan |
        // -----------------------------------------------------

        // Startkonfiguration setzen
        $sitzplanStartContentStream = GenerateContentStream::generateNew($analyzedContentStream->getLastGraphicsStateStack(), $templateContentStream->getResourceDictionary(), $pdfFile);
        $graphicsState = $sitzplanStartContentStream->getLastGraphicsStateStack()->getGraphicsState();
        $textState = $graphicsState->getTextState();
        $transformationMatrix = $graphicsState->getCurrentTransformationMatrix();
        if ($transformationMatrix != new TransformationMatrix())
            $sitzplanStartContentStream->addOperator(new ModifyTransformationMatrixOperator($transformationMatrix->invers()));
        if ($textState->getCharacterSpacing()->getValue() !== 0)
            $sitzplanStartContentStream->addOperator(new CharacterSpaceOperator(new PdfNumber(0)));
        if ($textState->getWordSpacing()->getValue() !== 0)
            $sitzplanStartContentStream->addOperator(new WordSpaceOperator(new PdfNumber(0)));
        if ($textState->getHorizontalScaling()->getValue() !== 100)
            $sitzplanStartContentStream->addOperator(new TextScaleOperator(new PdfNumber(100)));
        if ($textState->getTextRenderMode()->getValue() !== TextRenderModeOperator::fillText)
            $sitzplanStartContentStream->addOperator(new TextRenderModeOperator(new PdfNumber(TextRenderModeOperator::fillText)));
        if ($textState->getTextRise()->getValue() !== 0)
            $sitzplanStartContentStream->addOperator(new TextRiseOperator(new PdfNumber(0)));
        $extGraphicsState = new GraphicsStateParameterDictionary(new PdfDictionary(["Type" => new PdfName("ExtGState")]), $pdfFile);
        $extGraphicsState->generateIndirectObjectIfNotExists();
        if ($graphicsState->getLineWidth()->getValue() !== 1)
            $extGraphicsState->setLineWidth(new PdfNumber(1));
        if ($graphicsState->getLineCap() !== LineCapOperator::roundCap)
            $extGraphicsState->setLineCapStyle(new PdfNumber(LineCapOperator::roundCap));
        if ($graphicsState->getLineJoin() !== LineJoinOperator::roundJoin)
            $extGraphicsState->setLineJoinStyle(new PdfNumber(LineJoinOperator::roundJoin));
        if (!$graphicsState->getDashPatternArray()->equals(new PdfArray([])))
            $extGraphicsState->setDashPattern(new PdfArray([]), new PdfNumber(0));
        $extGraphicsStateName = $sitzplanStartContentStream->getContentStream()->getResourceDictionary()->addExtGState($extGraphicsState);
        $sitzplanStartContentStream->addOperator(new ExternalGraphicsStateOperator(new PdfName($extGraphicsStateName), $extGraphicsState));
        unset($graphicsState, $textState, $transformationMatrix, $extGraphicsState);

        if ($sitzplanConfig !== null) {
            // Transformiere, sodass Bildbereich exakt auf Raumdimensionen passt.
            $sitzplanStartContentStream->addOperator(new PushGraphicsStateOperator());
            $sitzplanTransformationMatrix = $this->getTransformationMatrixForImageConfig($sitzplanConfig, $this->veranstaltung->raumBreite, $this->veranstaltung->raumLaenge);
            $sitzplanStartContentStream->addOperator(new ModifyTransformationMatrixOperator($sitzplanTransformationMatrix));
            $sitzplanScale = $sitzplanTransformationMatrix->transformPoint(new Point(0, 0))->distanceTo($sitzplanTransformationMatrix->transformPoint(new Point(0, 1)));

            // Linienbreite, Schriftart und Schriftgröße
            $sitzplanStartContentStream->addOperator(new LineWidthOperator(new PdfNumber($sitzplanConfig->lineWidth / $sitzplanScale)));
            $font = $sitzplanStartContentStream->getContentStream()->getResourceDictionary()->getFont($sitzplanFontName);
            $currentTextState = $sitzplanStartContentStream->getLastGraphicsStateStack()->getGraphicsState()->getTextState();
            if ($currentTextState->getTextFont() !== $font || $currentTextState->getTextFontSize()->getValue() !== $sitzplanConfig->fontSize / $sitzplanScale)
                $sitzplanStartContentStream->addOperator(new TextFontOperator(new PdfName($sitzplanFontName), $font, new PdfNumber($sitzplanConfig->fontSize / $sitzplanScale)));

            // Zeichne Hintergrundfarbe und Clippe den Sitzplan
            $sitzplanStartContentStream->addOperator(new ColorRGBFillingOperator(new ColorRGB(245 / 255, 245 / 255, 220 / 255))); // HTML beige
            $sitzplanStartContentStream->addOperator(new PathRectangleOperator(new PdfNumber(0), new PdfNumber(0), new PdfNumber($this->veranstaltung->raumBreite), new PdfNumber($this->veranstaltung->raumLaenge)));
            $sitzplanStartContentStream->addOperator(new ClippingPathNonzeroOperator());
            $sitzplanStartContentStream->addOperator(new FillPathNonzeroOperator());

            // Zeichne alle Bereiche
            foreach ($this->bereiche as $bereich)
                $this->paintBereich($bereich, $sitzplanStartContentStream);
            // Zeichne alle Plätze
            foreach ($this->plaetze as $platz)
                $this->paintSeat($seatGrayOperator, $platz, $sitzplanStartContentStream);


            // Content Stream, um Sitzplan zu beenden
            $sitzplanEndContentStream = GenerateContentStream::generateNew($sitzplanStartContentStream->getLastGraphicsStateStack(), $sitzplanStartContentStream->getContentStream()->getResourceDictionary(), $pdfFile);

            $sitzplanEndContentStream->addOperator(new BeginTextObjectOperator());
            // Beschriftung für Sitzplätze
            if ($sitzplanConfig->seatNumbersVisible) {
                foreach ($this->plaetze as $platz)
                    $this->paintText($platz->reihe . $platz->platz, $platz->xPos, $platz->yPos, new ColorRGB(0, 0, 0), $sitzplanScale, $sitzplanEndContentStream);
            }
            // Beschriftung für Bereiche
            foreach ($this->bereiche as $bereich)
                $this->paintText($bereich->text, $bereich->xPos + $bereich->textXPos, $bereich->yPos + $bereich->textYPos, ColorRGB::fromHex($bereich->textFarbe), $sitzplanScale, $sitzplanEndContentStream);
            $sitzplanEndContentStream->addOperator(new EndTextObjectOperator());

            // Zurücktransformation von Koordinatensystem für Theaterkarte auf PDF-Koordinatensystem
            $sitzplanEndContentStream->addOperator(new PopGraphicsStateOperator());
        }


        // ------------------------------------------------
        // | Für jeden Sitzplatz eine PDF-Seite erstellen |
        // ------------------------------------------------

        foreach ($platzStatusse as $platzStatus) {
            // Erzeuge Seite und setze Metadaten
            $page = $templatePage->clonePage();
            $page->setContentStream($templateContentStream);
            $page->setPieceInfo("Theaterkarte", new PdfDictionary([
                "LastModified" => $lastModified,
                "Date" => new PdfString($platzStatus->date),
                "Time" => new PdfString($platzStatus->time),
                "Block" => new PdfString($platzStatus->block),
                "Reihe" => new PdfString($platzStatus->reihe),
                "Platz" => new PdfNumber($platzStatus->platz),
                "Preis" => $preisPdfObject,
                "Bezahlung" => new PdfString($this->vorgang->bezahlung),
                "VorgangsNummer" => new PdfNumber($this->vorgang->nummer)
            ]));

            // Content Stream zum Beginnen des Sitzplans
            $page->addContentStream($sitzplanStartContentStream->getContentStream());

            if ($sitzplanConfig !== null) {
                // Content Stream zum farbigen Markieren der Sitzplätze + richtiger Eingang
                $sitzplanPlaetzeContentStream = GenerateContentStream::generateNew($sitzplanStartContentStream->getLastGraphicsStateStack(), $sitzplanStartContentStream->getContentStream()->getResourceDictionary(), $pdfFile);
                foreach ($platzStatusse as $platz) {
                    if ($platzStatus->date === $platz->date && $platzStatus->time === $platz->time) {
                        if ($platz !== $platzStatus)
                            $this->paintSeat($seatLightColoredOperator, $platz, $sitzplanPlaetzeContentStream);
                        else {
                            $this->paintSeat($seatColoredOperator, $platz, $sitzplanPlaetzeContentStream);
                            if (isset($platz->eingang))
                                $this->paintEingang($platz->eingang, $sitzplanScale, $sitzplanPlaetzeContentStream);
                        }
                    }
                }
                $page->addContentStream($sitzplanPlaetzeContentStream->getContentStream());

                // Content Stream zum Beenden des Sitzplans
                $page->addContentStream($sitzplanEndContentStream->getContentStream());

                // Content Stream für Textbausteine
                $textbausteineContentStream = GenerateContentStream::generateNew($sitzplanEndContentStream->getLastGraphicsStateStack(), $sitzplanEndContentStream->getContentStream()->getResourceDictionary(), $pdfFile);
            } else {
                $textbausteineContentStream = GenerateContentStream::generateNew($sitzplanStartContentStream->getLastGraphicsStateStack(), $sitzplanStartContentStream->getContentStream()->getResourceDictionary(), $pdfFile);
            }

            // Textbausteine
            $textbausteineContentStream->addOperator(new BeginTextObjectOperator());
            if (isset($this->kartenConfig->dateTextConfig))
                $this->addText($this->kartenConfig->dateTextConfig, $dateTextFontName, $textbausteineContentStream, date("d.m.Y", strtotime($platzStatus->date)));
            if (isset($this->kartenConfig->timeTextConfig))
                $this->addText($this->kartenConfig->timeTextConfig, $timeTextFontName, $textbausteineContentStream, $platzStatus->time);
            if (isset($this->kartenConfig->blockTextConfig))
                $this->addText($this->kartenConfig->blockTextConfig, $blockTextFontName, $textbausteineContentStream, $platzStatus->block);
            if (isset($this->kartenConfig->reiheTextConfig))
                $this->addText($this->kartenConfig->reiheTextConfig, $reiheTextFontName, $textbausteineContentStream, $platzStatus->reihe);
            if (isset($this->kartenConfig->platzTextConfig))
                $this->addText($this->kartenConfig->platzTextConfig, $platzTextFontName, $textbausteineContentStream, (string)$platzStatus->platz);
            if (isset($this->kartenConfig->preisTextConfig)) {
                if ($preisPdfObject instanceof PdfNumber)
                    $preisText = number_format($preisPdfObject->getValue(), 2, ",", ".") . "€";
                else
                    $preisText = $preisPdfObject->getValue();
                $this->addText($this->kartenConfig->preisTextConfig, $preisTextFontName, $textbausteineContentStream, $preisText);
            }
            if (isset($this->kartenConfig->bezahlstatusTextConfig))
                $this->addText($this->kartenConfig->bezahlstatusTextConfig, $bezahlstatusTextFontName, $textbausteineContentStream, ($this->vorgang->bezahlung === "Abendkasse" ? "zahlt an der Abendkasse" : $this->vorgang->bezahlung));
            if (isset($this->kartenConfig->vorgangsNummerTextConfig)) {
                $nummerString = str_pad((string)$this->vorgang->nummer, 9, "0", STR_PAD_LEFT);
                $this->addText($this->kartenConfig->vorgangsNummerTextConfig, $vorgangsNummerTextFontName, $textbausteineContentStream, substr($nummerString, 0, 3) . " " . substr($nummerString, 3, 3) . " " . substr($nummerString, 6, 3));
            }
            $textbausteineContentStream->addOperator(new EndTextObjectOperator());
            // QR-Code kommt mit zu den Textbausteinen
            if (isset($this->kartenConfig->qrCodeConfig)) {
                $textbausteineContentStream->addOperator(new PushGraphicsStateOperator());
                $textbausteineContentStream->addOperator(new ModifyTransformationMatrixOperator($qrCodeTransformationMatrix));
                $textbausteineContentStream->addOperator(
                    InlineImageOperator::getFromQRCode(
                        \QRcode::text(json_encode([
                            "date" => $platzStatus->date,
                            "time" => $platzStatus->time,
                            "block" => $platzStatus->block,
                            "reihe" => $platzStatus->reihe,
                            "platz" => $platzStatus->platz,
                            "preis" => $preisPdfObject->getValue(),
                            "bezahlung" => $this->vorgang->bezahlung,
                            "vorgangsNr" => $this->vorgang->nummer
                        ]), false, QR_ECLEVEL_M, 1, 0)
                    )
                );
                $textbausteineContentStream->addOperator(new PopGraphicsStateOperator());
            }
            $page->addContentStream($textbausteineContentStream->getContentStream());

            // Füge Seite hinzu
            $pdfDocument->getPageList()->addPage($page);
        }


        // ----------------------------
        // | Theaterkarte abspeichern |
        // ----------------------------
        $this->generatedTicket = $pdfDocument->generatePdfFile($this->allowDataCompression);
    }

    /**
     * Liefert den Namen, unter dem die Font im ResourceDictionary zu finden ist.
     * Sollte die Font nicht im ResourceDictionary zu finden sein, aber zu den Standard 14 Fonts gehören, wird sie im Resource Dictionary angelegt.
     * @param object $textConfig Konfiguration für einen Textbaustein
     * @param ContentStream $templateContentStream ContentStream, in dessen ResourceDictionary die Font gesucht wird
     * @return string|null Name, unter dem die Font im ResourceDictionary zu finden ist.
     * @throws \Exception Wenn die Font nicht im ContentStream ist und auch keine der Standard 14 Fonts.
     */
    private function getFontName($textConfig, ContentStream $templateContentStream)
    {
        $fontName = $templateContentStream->getResourceDictionary()->getFontNameByBaseName($textConfig->font);
        if ($fontName === null) {
            $font = Font::getStandard14Font($textConfig->font, $templateContentStream->getPdfFile());
            $fontName = $templateContentStream->getResourceDictionary()->addFont($font);
        }
        return $fontName;
    }

    /**
     * Erzeugt aus eine PNG-Datei ein Image XObject, fügt diesen in das Resource Dictionary des Content Streams hinzu, und gibt den Operatoren zurück, welcher das Bild zeichnet
     * @param string $pngFile Pfad zu der PNG-Datei, die gelesen werden soll
     * @param ContentStream $templateContentStream ContentStream, in dessen ResourceDictionary das Bild eingefügt wird
     * @return ExternalObjectOperator Operator für den Content Stream, welcher das Bild zeichnet
     * @throws \Exception Wenn das PNG-Bild nicht gelesen oder konvertiert werden kann
     */
    private function getImageOperator(string $pngFile, ContentStream $templateContentStream): ExternalObjectOperator
    {
        $xObject = XObjectImage::createFromPNG($pngFile, $templateContentStream->getPdfFile());
        $xObjectName = new PdfName($templateContentStream->getResourceDictionary()->addXObject($xObject));
        return new ExternalObjectOperator($xObjectName, $xObject);
    }

    /**
     * Zeichnet einen Sitzplatz in den Saalplan
     * @param ExternalObjectOperator $seatOperator Operator, welcher ein XObject zeichnet
     * @param object $platz Einzelner Platz aus plaetze.json
     * @param GenerateContentStream $contentStream ContentStream, in welchem aktuell der Saalplan gezeichnet wird
     * @throws \Exception Wird theoretisch nicht geschmissen
     */
    private function paintSeat(ExternalObjectOperator $seatOperator, $platz, GenerateContentStream $contentStream)
    {
        $contentStream->addOperator(new PushGraphicsStateOperator());
        $transformationMatrix = TransformationMatrix::translation($platz->xPos, $this->veranstaltung->raumLaenge - $platz->yPos);
        $transformationMatrix = $transformationMatrix->addTransformation(TransformationMatrix::rotation(deg2rad(-$platz->rotation)));
        $transformationMatrix = $transformationMatrix->addTransformation(TransformationMatrix::scaling($this->veranstaltung->sitzBreite, $this->veranstaltung->sitzLaenge));
        $transformationMatrix = $transformationMatrix->addTransformation(TransformationMatrix::translation(-.5, -.5));
        $contentStream->addOperator(new ModifyTransformationMatrixOperator($transformationMatrix));
        $contentStream->addOperator($seatOperator);
        $contentStream->addOperator(new PopGraphicsStateOperator());
    }

    /**
     * Zeichnet einen Bereich (ohne Text) in den Saalplan
     * @param object $bereich zu zeichnender Bereich aus bereiche.json
     * @param GenerateContentStream $contentStream ContentStream, in welchem aktuell der Saalplan gezeichnet wird
     * @throws \Exception Wird theoretisch nicht geschmissen
     */
    private function paintBereich($bereich, GenerateContentStream $contentStream)
    {
        $color = ColorRGB::fromHex($bereich->farbe);
        if ($contentStream->getLastGraphicsStateStack()->getGraphicsState()->getColorFilling() != $color)
            $contentStream->addOperator(new ColorRGBFillingOperator($color));

        $contentStream->addOperator(new PathRectangleOperator(new PdfNumber($bereich->xPos), new PdfNumber($this->veranstaltung->raumLaenge - $bereich->yPos - $bereich->laenge), new PdfNumber($bereich->breite), new PdfNumber($bereich->laenge)));
        $contentStream->addOperator(new FillPathNonzeroOperator());
    }

    /**
     * Zeichnet einen Text in den Saalplan, mittig auf den mit x und y angegebenen Punkt.
     * Vorausgesetzt wird, dass ein TextObjekt mit dem BeginTextOperator vorher begonnen wurde.
     * @param string $text Einzuzeichnender Text
     * @param float $x X-Position, an den der Text gezeichnet werden soll
     * @param float $y Y-Position, an den der Text gezeichnet werden soll
     * @param ColorRGB $color Farbe, in der der Text gezeichnet werden soll
     * @param float $sitzplanScale Faktor, um den der Sitzplan größer skaliert wurde, damit Device Space mit Saalplan Längeneinheit übereinstimmt
     * @param GenerateContentStream $contentStream ContentStream, in welchem aktuell der Saalplan gezeichnet wird
     * @throws \Exception Wenn der Text nicht gezeichnet werden kann
     */
    private function paintText(string $text, float $x, float $y, ColorRGB $color, float $sitzplanScale, GenerateContentStream $contentStream)
    {
        $graphicsState = $contentStream->getLastGraphicsStateStack()->getGraphicsState();
        if ($graphicsState->getColorFilling() != $color)
            $contentStream->addOperator(new ColorRGBFillingOperator($color));

        $font = $graphicsState->getTextState()->getTextFont();
        $fontSize = $graphicsState->getTextState()->getTextFontSize();

        $textOperator = new TextOperator(new PdfString($font->fromUTF8($text)));
        $textOperator->calculateText($graphicsState);
        $textLength = ($textOperator->getEndPos()->x - $textOperator->getStartPos()->x) / $sitzplanScale;
        $contentStream->addOperator(new TextMatrixOperator(
            TransformationMatrix::translation($x - $textLength * 0.5, $this->veranstaltung->raumLaenge - $y - $fontSize->getValue() * 0.3)
        ));
        $contentStream->addOperator($textOperator);
    }

    /**
     * Zeichnet den Eingang in den Saalplan, durch den man den Platz am besten erreicht.
     * Sollten mehrere Eingangs-Objekte dazu gehören, werden sie rekursiv alle gezeichnet.
     * @param int $eingangID ID des Eingangs in eingaenge.json
     * @param float $sitzplanScale Faktor, um den der Sitzplan größer skaliert wurde, damit Device Space mit Saalplan Längeneinheit übereinstimmt
     * @param GenerateContentStream $contentStream ContentStream, in welchem aktuell der Saalplan gezeichnet wird
     * @throws \Exception Wenn der Eingang nicht gezeichnet werden kann.
     */
    private function paintEingang(int $eingangID, float $sitzplanScale, GenerateContentStream $contentStream)
    {
        // Finde Eingang
        $eingang = null;
        foreach ($this->eingaenge as $e) {
            if ($e->id === $eingangID) {
                $eingang = $e;
                break;
            }
        }
        if ($eingang === null)
            return;

        // Zeichne Eingang
        $color = new ColorRGB(1, 0, 0);
        if ($contentStream->getLastGraphicsStateStack()->getGraphicsState()->getColorStroking() != $color)
            $contentStream->addOperator(new ColorRGBStrokingOperator($color));
        // Pfeil am Ende der Kurve
        $endPoint = new Point($eingang->x3, $this->veranstaltung->raumLaenge - $eingang->y3);
        $previousPoint = new Point($eingang->x2, $this->veranstaltung->raumLaenge - $eingang->y2);
        if ($previousPoint == $endPoint) {
            $previousPoint = new Point($eingang->x1, $this->veranstaltung->raumLaenge - $eingang->y1);
            if ($previousPoint == $endPoint) {
                $previousPoint = new Point($eingang->x0, $this->veranstaltung->raumLaenge - $eingang->y0);
            }
        }
        if ($endPoint != $previousPoint) {
            $distance = $endPoint->distanceTo($previousPoint);
            $dx = ($previousPoint->x - $endPoint->x) / $distance / 2;
            $dy = ($previousPoint->y - $endPoint->y) / $distance / 2;
            $contentStream->addOperator(new PathBeginOperator(new PdfNumber($endPoint->x + $dx * $this->veranstaltung->sitzLaenge + $dy * $this->veranstaltung->sitzBreite), new PdfNumber($endPoint->y + $dy * $this->veranstaltung->sitzLaenge - $dx * $this->veranstaltung->sitzBreite)));
            $contentStream->addOperator(new PathLineOperator(new PdfNumber($endPoint->x), new PdfNumber($endPoint->y)));
            $contentStream->addOperator(new PathLineOperator(new PdfNumber($endPoint->x + $dx * $this->veranstaltung->sitzLaenge - $dy * $this->veranstaltung->sitzBreite), new PdfNumber($endPoint->y + $dy * $this->veranstaltung->sitzLaenge + $dx * $this->veranstaltung->sitzBreite)));
        }
        $contentStream->addOperator(new PathBeginOperator(new PdfNumber($eingang->x3), new PdfNumber($this->veranstaltung->raumLaenge - $eingang->y3)));
        $contentStream->addOperator(new PathBezierOperator(new PdfNumber($eingang->x2), new PdfNumber($this->veranstaltung->raumLaenge - $eingang->y2), new PdfNumber($eingang->x1), new PdfNumber($this->veranstaltung->raumLaenge - $eingang->y1), new PdfNumber($eingang->x0), new PdfNumber($this->veranstaltung->raumLaenge - $eingang->y0)));
        // StrokePathOperator wird noch nicht gezeichnet für den Fall, dass der Eingang sich mit dem vorherigen verbindet.

        // Zeichne vorher zu passierenden Eingang
        if (isset($eingang->eingang)) {
            if ($this->kartenConfig->sitzplanConfig->connectEntranceArrows) {
                $this->paintEingangConnected($eingang, $sitzplanScale, $contentStream);
            } else {
                $contentStream->addOperator(new StrokePathOperator());
                $this->paintEingang($eingang->eingang, $sitzplanScale, $contentStream);
            }
        } else {
            $contentStream->addOperator(new StrokePathOperator());
        }

        // Text für den Eingang (sofern definiert)
        if (isset($eingang->text)) {
            $contentStream->addOperator(new BeginTextObjectOperator());
            $this->paintText($eingang->text, $eingang->textXPos, $eingang->textYPos, new ColorRGB(0, 0, 0), $sitzplanScale, $contentStream);
            $contentStream->addOperator(new EndTextObjectOperator());
        }
    }

    /**
     * Zeichnet einen Eingang, der mit dem nachfolgenden Eingang verbunden ist und eine lange Linie erzeugt, durch den man den Platz am besten erreicht.
     * Auch dieser zeichnet rekursiv die vorherigen Eingangs-Objekte ein.
     * @param object $nextEingang In Pfeilrichtung nachfolgender Eingang, der mit diesem Eingang eine verbundene Linie bilden soll
     * @param float $sitzplanScale Faktor, um den der Sitzplan größer skaliert wurde, damit Device Space mit Saalplan Längeneinheit übereinstimmt
     * @param GenerateContentStream $contentStream ContentStream, in welchem aktuell der Saalplan gezeichnet wird
     * @throws \Exception Wenn der Eingang nicht gezeichnet werden kann.
     */
    private function paintEingangConnected($nextEingang, float $sitzplanScale, GenerateContentStream $contentStream)
    {
        // finde aktuellen Eingang
        $eingang = null;
        foreach ($this->eingaenge as $e) {
            if ($e->id === $nextEingang->eingang) {
                $eingang = $e;
                break;
            }
        }
        if ($eingang === null) {
            $contentStream->addOperator(new StrokePathOperator());
            return;
        }

        // Zeichne Verbindungslinie
        $x2 = $nextEingang->x0 * 2.0 - $nextEingang->x1;
        $y2 = $nextEingang->y0 * 2.0 - $nextEingang->y1;
        $x1 = $eingang->x3 * 2.0 - $eingang->x2;
        $y1 = $eingang->y3 * 2.0 - $eingang->y2;
        $contentStream->addOperator(new PathBezierOperator(new PdfNumber($x2), new PdfNumber($this->veranstaltung->raumLaenge - $y2), new PdfNumber($x1), new PdfNumber($this->veranstaltung->raumLaenge - $y1), new PdfNumber($eingang->x3), new PdfNumber($this->veranstaltung->raumLaenge - $eingang->y3)));

        // Zeichne Eingang
        $contentStream->addOperator(new PathBezierOperator(new PdfNumber($eingang->x2), new PdfNumber($this->veranstaltung->raumLaenge - $eingang->y2), new PdfNumber($eingang->x1), new PdfNumber($this->veranstaltung->raumLaenge - $eingang->y1), new PdfNumber($eingang->x0), new PdfNumber($this->veranstaltung->raumLaenge - $eingang->y0)));
        // StrokePathOperator wird noch nicht gezeichnet für den Fall, dass der Eingang sich mit dem vorherigen verbindet.

        // Zeichne vorher zu passierenden Eingang
        if (isset($eingang->eingang)) {
            $this->paintEingangConnected($eingang, $sitzplanScale, $contentStream);
        } else {
            $contentStream->addOperator(new StrokePathOperator());
        }

        // Text für den Eingang (sofern definiert)
        if (isset($eingang->text)) {
            $contentStream->addOperator(new BeginTextObjectOperator());
            $this->paintText($eingang->text, $eingang->textXPos, $eingang->textYPos, new ColorRGB(0, 0, 0), $sitzplanScale, $contentStream);
            $contentStream->addOperator(new EndTextObjectOperator());
        }
    }

    /**
     * Fügt einen Text hinzu. Vorausgesetzt wird, dass ein TextObjekt mit dem BeginTextOperator vorher begonnen wurde.
     * @param object $textConfig Konfiguration zum zeichnen dieses Textes
     * @param string $fontName Name der Font im ResourceDictionary des $targetContentStream
     * @param GenerateContentStream $targetContentStream ContentStream, in dem die benötigten Operatoren hinzugefügt werden sollen.
     * @param string $text Text, der gezeichnet werden soll.
     * @throws \Exception Wenn der Text nicht hinzugefügt werden kann
     */
    private function addText($textConfig, string $fontName, GenerateContentStream $targetContentStream, string $text)
    {
        $font = $targetContentStream->getContentStream()->getResourceDictionary()->getFont($fontName);
        $currentTextState = $targetContentStream->getLastGraphicsStateStack()->getGraphicsState()->getTextState();
        if ($currentTextState->getTextFont() !== $font || $currentTextState->getTextFontSize()->getValue() !== $textConfig->fontSize)
            $targetContentStream->addOperator(new TextFontOperator(new PdfName($fontName), $font, new PdfNumber($textConfig->fontSize)));

        $color = new ColorRGB($textConfig->color->r, $textConfig->color->g, $textConfig->color->b);
        if ($targetContentStream->getLastGraphicsStateStack()->getGraphicsState()->getColorFilling() != $color)
            $targetContentStream->addOperator(new ColorRGBFillingOperator($color));

        $textOperator = new TextOperator(new PdfString($font->fromUTF8($text)));
        switch ($textConfig->alignment) {
            case TextAlignment::alignLeft:
                $targetContentStream->addOperator(new TextMatrixOperator(
                    TransformationMatrix::translation($textConfig->position->x, $textConfig->position->y)
                ));
                break;
            case TextAlignment::alignCenter:
                $textOperator->calculateText($targetContentStream->getLastGraphicsStateStack()->getGraphicsState());
                $textLength = $textOperator->getEndPos()->x - $textOperator->getStartPos()->x;
                $targetContentStream->addOperator(new TextMatrixOperator(
                    TransformationMatrix::translation($textConfig->position->x - $textLength * 0.5, $textConfig->position->y)
                ));
                break;
            case TextAlignment::alignRight:
                $textOperator->calculateText($targetContentStream->getLastGraphicsStateStack()->getGraphicsState());
                $textLength = $textOperator->getEndPos()->x - $textOperator->getStartPos()->x;
                $targetContentStream->addOperator(new TextMatrixOperator(
                    TransformationMatrix::translation($textConfig->position->x - $textLength, $textConfig->position->y)
                ));
                break;
        }
        $targetContentStream->addOperator($textOperator);
    }

    /**
     * Liefert eine Transformationsmatrix, die vom Default User Space in ein Bildkoordinatensystem überführt.
     * Das Bildkoordinatensystem liegt danach innerhalb der Grenzen
     * @param object $imageConfig Konfiguration für QR-Code oder Saalplan
     * @param float $targetWidth Breite des Bildkoordinatensystems
     * @param float $targetHeight Höhe des Bildkoordinatensystems
     * @return TransformationMatrix Transformations-Matrix, die vom Default User Space in ein Bildkoordinatensystem überführt.
     */
    private function getTransformationMatrixForImageConfig($imageConfig, float $targetWidth, float $targetHeight)
    {
        // linke untere Ecke in Ursprung verschieben
        $transformationMatrix = TransformationMatrix::translation($imageConfig->lowerLeftCorner->x, $imageConfig->lowerLeftCorner->y);
        // Rotation, sodass X-Achsen aufeinanderliegen
        $wx = $imageConfig->lowerRightCorner->x - $imageConfig->lowerLeftCorner->x;
        $wy = $imageConfig->lowerRightCorner->y - $imageConfig->lowerLeftCorner->y;
        $originalWidth = sqrt($wx * $wx + $wy * $wy);
        $hx = $imageConfig->upperLeftCorner->x - $imageConfig->lowerLeftCorner->x;
        $hy = $imageConfig->upperLeftCorner->y - $imageConfig->lowerLeftCorner->y;
        $originalHeight = sqrt($hx * $hx + $hy * $hy);
        $transformationMatrix = $transformationMatrix->addTransformation(TransformationMatrix::rotation(atan2($wy, $wx)));
        // Abschrägung der Y-Achse herausrechnen
        $newUpperLeftCorner = $transformationMatrix->invers()->transformPoint(new Point($imageConfig->upperLeftCorner->x, $imageConfig->upperLeftCorner->y));
        $transformationMatrix = $transformationMatrix->addTransformation(new TransformationMatrix(1, 0, $newUpperLeftCorner->x / $newUpperLeftCorner->y, 1));
        $transformationMatrix = $transformationMatrix->addTransformation(TransformationMatrix::scaling(1, $newUpperLeftCorner->y / $originalHeight));
        // Skalieren und dabei Seitenverhältnis bewahren
        if ($originalWidth / $originalHeight > $targetWidth / $targetHeight) {
            $scaling = $originalHeight / $targetHeight;
            $transformationMatrix = $transformationMatrix->addTransformation(TransformationMatrix::scaling($scaling, $scaling));
            $xOffset = ($originalWidth / $scaling - $targetWidth) / 2.0;
            $transformationMatrix = $transformationMatrix->addTransformation(TransformationMatrix::translation($xOffset, 0));
        } else {
            $scaling = $originalWidth / $targetWidth;
            $transformationMatrix = $transformationMatrix->addTransformation(TransformationMatrix::scaling($scaling, $scaling));
            $yOffset = ($originalHeight / $scaling - $targetHeight) / 2.0;
            $transformationMatrix = $transformationMatrix->addTransformation(TransformationMatrix::translation(0, $yOffset));
        }
        // skalieren auf passende Größe
        return $transformationMatrix;
    }


    /**
     * Speichert die Theaterkarten in der Vorgesehenen PDF-Datei
     * @throws \Exception
     * @see TicketGenerator::getTicketURL() URL, unter der die Theaterkarte gespeichert wurde
     */
    public function saveTicket()
    {
        if ($this->generatedTicket === null)
            $this->generateTicket();

        global $ticketsFolder;
        $filePath = $ticketsFolder . $this->getTicketName();
        $writtenBytes = @file_put_contents($filePath, $this->generatedTicket);
        if ($writtenBytes === false)
            throw new \Exception("Failed to write PDF-File to {$filePath}");
    }

    /**
     * Liefert den Inhalt der PDF-Datei, der in einer beliebigen Datei gespeichert werden könnte.
     * @return string|null
     */
    public function getTicketContent(): ?string
    {
        return $this->generatedTicket;
    }

    /**
     * Löscht die bereits existierende Theaterkarte, sofern eine in $vorgang gespeichert ist.
     * Es wird nur die Theaterkarte gelöscht, nicht aber der Eintrag in Vorgang
     * @param string|null $theaterkarte URL der Theaterkarte. Wenn nicht angegeben, wird die URL aus dem im Konstruktor übergebenen $vorgang-Objekt genommen.
     */
    public function deleteExistingTicket(string $theaterkarte = null)
    {
        if ($theaterkarte === null)
            $theaterkarte = $this->vorgang->theaterkarte;

        global $ticketsFolder;
        if (isset($theaterkarte)) {
            $fileName = rawurldecode(substr($theaterkarte, strrpos($theaterkarte, "/") + 1));
            if (file_exists($ticketsFolder . $fileName))
                unlink($ticketsFolder . $fileName);
        }
    }

    /**
     * Liefert die URL zurück, unter der die generierte Theaterkarte zu finden ist
     * @return string vollständige URL der Theaterkarte
     */
    public function getTicketURL(): string
    {
        if ($this->veranstaltung === null)
            $this->veranstaltung = $this->databaseConnection->getVeranstaltungJson();

        $https = @$_SERVER["HTTPS"] !== null && $_SERVER["HTTPS"] !== "off";
        return ($https ? "https://" : "http://")
            . "{$_SERVER["HTTP_HOST"]}/"
            . "karten/"
            . rawurlencode($this->getTicketName());
    }

    /**
     * Liefert den Dateinamen für die Theaterkarte
     * @return string
     */
    private function getTicketName(): string
    {
        return "Theaterkarten_{$this->veranstaltung->veranstaltung}_{$this->vorgang->nummer}.pdf";
    }
}