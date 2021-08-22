<?php


namespace pdf\document;

use pdf\indirectObject\PdfStream;
use pdf\object\PdfAbstractObject;
use pdf\object\PdfArray;
use pdf\object\PdfBoolean;
use pdf\object\PdfDictionary;
use pdf\object\PdfIndirectReference;
use pdf\object\PdfName;
use pdf\PdfDocument;

/**
 * Repräsentiert den Document Catalog.
 * Es werden nur die benötigten Möglichkeiten des Document Catalog abgebildet.
 * @package pdf\document
 */
class DocumentCatalog extends AbstractDocumentObject
{

    /**
     * Liefert den Type dieser Klasse an Dokument Objekten.
     * Er muss in einem zugehörigen Dictionary immer unter Type enthalten sein.
     * @return string
     */
    public static function objectType(): string
    {
        return "Catalog";
    }

    /**
     * Liefert den Subtype dieser Klasse an Dokument Objekten.
     * Sollte für die Dokumentklasse kein SubType benötigt werden, wird null zurückgeliefert.
     * @return string|null
     */
    public static function objectSubtype(): ?string
    {
        return null;
    }

    public function getExtensions(): ?PdfDictionary
    {
        return $this->get("Extensions");
    }

    public function getPages(): PdfIndirectReference
    {
        return $this->dictionary->getObject("Pages");
    }

    public function getNames(): PdfDictionary
    {
        return $this->get("Names");
    }

    public function getDests(): PdfDictionary
    {
        return $this->get("Dests");
    }

    public function getViewerPreferences(): ?PdfDictionary
    {
        return $this->get("ViewerPreferences");
    }

    public function getOutlines(): ?PdfDictionary {
        return $this->get("Outlines");
    }

    public function getThreads(): ?PdfArray {
        return $this->get("Threads");
    }

    /**
     * @return null|PdfArray|PdfDictionary
     */
    public function getOpenAction(): ?PdfAbstractObject
    {
        return $this->get("OpenAction");
    }

    public function getAA(): ?PdfDictionary
    {
        return $this->get("AA");
    }

    public function getURI(): ?PdfDictionary
    {
        return $this->get("URI");
    }

    public function getAcroForm(): ?PdfDictionary {
        return $this->get("AcroForm");
    }

    public function getMetadata(): ?PdfStream
    {
        /** @var PdfIndirectReference $metadata */
        $metadata = $this->dictionary->getObject("Metadata");
        if ($metadata === null)
            return null;
        return $this->pdfFile->getIndirectObject($metadata);
    }

    public function getStructTreeRoot() : ?PdfDictionary {
        return $this->get("StructTreeRoot");
    }

    public function getMarkInfo(): ?PdfDictionary {
        return $this->get("MarkInfo");
    }

    public function getLang(): ?string
    {
        $langObj = $this->get("Lang");
        if ($langObj === null)
            return null;
        return $langObj->getValue();
    }

    public function getSpiderInfo(): ?PdfDictionary {
        return $this->get("SpiderInfo");
    }

    public function getOutputIntents(): PdfArray
    {
        return $this->get("OutputIntents");
    }

    public function getPieceInfo(): PdfDictionary
    {
        return $this->get("PieceInfo");
    }

    public function getOptionalContentProperties(): PdfDictionary {
        return $this->get("OCProperties");
    }

    public function getLegal(): PdfDictionary {
        return $this->get("Legal");
    }

    public function getRequirements(): PdfArray
    {
        return $this->get("Requirements");
    }

    public function getCollection(): PdfDictionary {
        return $this->get("Collection");
    }
    public function getNeedsRendering(): PdfBoolean {
        return $this->get("NeedsRendering");
    }

    /**
     * Setzt einige Einträge, die für die Generierung einer Theaterkarte definitiv gesetzt oder entfernt werden sollten, auf die benötigten Standardwerte
     */
    public function setDefaults() {
        $this->remove("PageLabels"); // Keine Seitennummerierung
        $this->set("PageLayout", new PdfName("OneColumn"));
        $this->remove("PageMode"); // Standardwert UseNone ist der Beste Wert
        $this->remove("Metadata"); // Metadaten sind nach der Bearbeitung der PDF veraltet und nicht notwendig für die PDF
        $this->remove("Perms"); // Keine Zugriffbeschränkungen
    }

    /**
     * Setzt die Produktinformationen für das gesamte Dokument.
     * Dabei werden jegliche bisherigen Produktinformationen entfernt
     * @param string $productName Name des Produktes
     * @param PdfDictionary $pieceData Informationen über das Produkt. Ist kein LastModified-Eintrag vorhanden, wird er hinzugefügt.
     * @throws \Exception
     */
    public function setPieceInfo(string $productName, PdfDictionary $pieceData) {
        if (!$pieceData->hasObject("LastModified"))
            $pieceData->setObject("LastModified", PdfDate::parseDateTime(new \DateTime('now')));
        $pieceInfo = new PdfDictionary([$productName => $pieceData]);
        $this->dictionary->setObject("PieceInfo", $pieceInfo);
    }
}