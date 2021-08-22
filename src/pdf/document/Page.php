<?php

namespace pdf\document;

use pdf\indirectObject\PdfStream;
use pdf\object\PdfAbstractObject;
use pdf\object\PdfArray;
use pdf\object\PdfDictionary;
use pdf\object\PdfIndirectReference;
use pdf\object\PdfName;
use pdf\object\PdfNumber;
use phpDocumentor\Reflection\Types\Mixed_;

/**
 * Eine Seite eines Pdf-Dokumentes.
 * @package pdf\document
 */
class Page extends AbstractDocumentObject
{
    /**
     * Attribute, die vom darüberliegenden PageTree weitervererbt werden können
     */
    public const inheritableAttributes = ["Resources", "MediaBox", "CropBox", "Rotate"];

    public static function objectType(): string
    {
        return "Page";
    }

    public static function objectSubtype(): ?string
    {
        return null;
    }


    /**
     * Erzeugt eine Kopie dieser Seite.
     * Die neue Seite hat noch kein zugewiesenes IndirectObject, dies wird beim Hinzufügen der Seite zum PageTree erstellt.
     * @return Page
     * @throws \Exception
     */
    public function clonePage(): Page
    {
        return new Page($this->dictionary->clone(), $this->pdfFile);
    }

    public function getParent(): PdfIndirectReference
    {
        return $this->dictionary->getObject("Parent");
    }

    /**
     * @return \DateTime
     * @see Page::getPieceInfo()
     */
    public function getLastModified(): \DateTime
    {
        return PdfDate::parsePdfString($this->get("LastModified"));
    }

    public function getResources(): ResourceDictionary
    {
        return new ResourceDictionary($this->get("Resources"), $this->pdfFile);
    }

    public function getMediaBox(): PdfRectangle
    {
        return PdfRectangle::parsePdfArray($this->get("MediaBox"));
    }

    public function getCropBox(): PdfRectangle
    {
        $pdfArray = $this->get("CropBox");
        if ($pdfArray === null)
            return $this->getMediaBox();
        return PdfRectangle::parsePdfArray($pdfArray);
    }

    public function getBleedBox(): PdfRectangle
    {
        $pdfArray = $this->get("BleedBox");
        if ($pdfArray === null)
            return $this->getCropBox();
        return PdfRectangle::parsePdfArray($pdfArray);
    }

    public function getTrimBox(): PdfRectangle
    {
        $pdfArray = $this->get("TrimBox");
        if ($pdfArray === null)
            return $this->getCropBox();
        return PdfRectangle::parsePdfArray($pdfArray);
    }

    public function getArtBox(): PdfRectangle
    {
        $pdfArray = $this->get("ArtBox");
        if ($pdfArray === null)
            return $this->getCropBox();
        return PdfRectangle::parsePdfArray($pdfArray);
    }

    public function getBoxColorInfo(): ?PdfDictionary
    {
        return $this->get("BoxColorInfo");
    }

    /**
     * Gibt die Anzahl der Content Streams in der Page
     * @return int
     */
    public function getContentsCount(): int
    {
        $contents = $this->get("Contents");
        if ($contents instanceof PdfArray)
            return $contents->getArraySize();
        else if ($contents instanceof PdfIndirectReference)
            return 1;
        else
            return 0;
    }

    /**
     * Gibt einen Content Stream zurück. Wenn in der Page mehrere Content Streams verwendet werden, werden diese zu einem zusammengeführt.
     * @return ContentStream|null
     * @throws \Exception Wenn die ContentStreams nicht korrekt referenziert werden können
     */
    public function getContents(): ?ContentStream
    {
        $contents = $this->dictionary->getObject("Contents");
        $resources = $this->getResources();
        if ($contents instanceof PdfArray) {
            $decompressedStream = "";
            $streamCount = $contents->getArraySize();
            for ($i = 0; $i < $streamCount; ++$i) {
                /** @var PdfStream $pdfStream */
                $pdfStream = $this->pdfFile->getIndirectObject($contents->getObject($i));
                $decompressedStream .= $pdfStream->getDecompressedStream();
            }
            $crossReferenceTableEntry = $this->pdfFile->generateNewCrossReferenceTableEntry();
            $pdfStream = PdfStream::fromCrossReferenceTableEntry($crossReferenceTableEntry, $this->pdfFile, $decompressedStream);
            return new ContentStream($pdfStream, $this->pdfFile, $resources);
        } else if ($contents instanceof PdfIndirectReference) {
            return new ContentStream($contents, $this->pdfFile, $resources);
        } else {
            return null;
        }
    }

    /**
     * Gibt ein Array mit allen genutzten Content Streams zurück.
     * @return ContentStream[]
     * @throws \Exception Wenn die ContentStreams nicht korrekt referenziert werden können
     */
    public function getContentsArray(): array
    {
        $contents = $this->dictionary->getObject("Contents");
        $resources = $this->getResources();
        if ($contents instanceof PdfArray) {
            $contentStreamArray = [];
            $streamCount = $contents->getArraySize();
            for ($i = 0; $i < $streamCount; ++$i) {
                $contentStreamArray[$i] = new ContentStream($contents->getObject($i), $this->pdfFile, $resources);
            }
            return $contentStreamArray;
        } else if ($contents instanceof PdfIndirectReference) {
            return [new ContentStream($contents, $this->pdfFile, $resources)];
        } else {
            return [];
        }
    }

    public function getRotate(): int
    {
        return $this->get("Rotate")->getValue();
    }

    public function getPageGroup(): ?PdfDictionary
    {
        return $this->get("Group");
    }

    public function getArticleBeads(): ?PdfArray {
        return $this->get("B");
    }

    public function getAnnotations() : ?PdfArray {
        return $this->get("Annots");
    }

    public function getAA(): ?PdfDictionary
    {
        return $this->get("AA");
    }

    public function getMetadata(): ?PdfStream
    {
        return $this->pdfFile->getIndirectObject($this->dictionary->getObject("Metadata"));
    }

    /**
     * Liefert das PieceInfo-Dictionary zurück oder - wenn $productname angegeben ist - das gewünschte Produkt Dictionary
     * @param string|null $productName Name des Produktes auf dieser Seite. Wenn dieser Wert nicht angegeben wird, wird nicht das Product Dictionary, sondern das Gesamte PieceInfo-Dictionary zurückgeliefert.
     * @return PdfDictionary|null
     */
    public function getPieceInfo(string $productName = null): ?PdfDictionary
    {
        /** @var PdfDictionary $pieceInfoDictionary */
        $pieceInfoDictionary = $this->get("PieceInfo");
        if ($pieceInfoDictionary == null || $productName == null)
            return $pieceInfoDictionary;
        else
            return $this->pdfFile->parseReference($pieceInfoDictionary->getObject($productName));
    }

    public function getStructParents(): PdfNumber {
        return $this->get("StructParents");
    }

    public function getID(): ?PdfAbstractObject {
        return $this->get("ID");
    }

    public function getPreferredZoom(): ?PdfNumber {
        return $this->get("PZ");
    }

    public function getSeparationInfo(): ?PdfDictionary {
        return $this->get("SeparationInfo");
    }

    public function getTabs(): ?PdfName {
        return $this->get("Tabs");
    }

    public function getTemplateInstantiated(): ?PdfName {
        return $this->get("TemplateInstantiated");
    }

    public function getPresSteps(): ?PdfDictionary {
        return $this->get("PresSteps");
    }

    public function getUserUnit(): ?PdfNumber
    {
        return $this->get("UserUnit");
    }

    public function getViewPorts(): ?PdfDictionary {
        return $this->get("VP");
    }


    public function setParent(PdfIndirectReference $pdfIndirectReference)
    {
        $this->dictionary->setObject("Parent", $pdfIndirectReference);
    }

    /**
     * Setzt den übergebenen ContentStream als den einzigen Content Stream dieser Seite
     * @param ContentStream $contentStream ContentStream für diese Seite
     */
    public function setContentStream(ContentStream $contentStream)
    {
        $this->set("Contents", $contentStream->getIndirectReference());
        $this->set("Resources", $contentStream->getResourceDictionary()->getDictionary());
    }

    /**
     * Fügt den übergebenen ContentStream am Ende der existierenden Liste an ContentStreams hinzu.
     * Ist Contents kein Array, wird es zu einem Array gemacht.
     * Das Resource Dictionary des ContentStreams wird mit dem Resource Dictionary der Seite zusammengeführt.
     * @param ContentStream $contentStream Anzufügender ContentStream
     * @throws \Exception
     */
    public function addContentStream(ContentStream $contentStream)
    {
        $contents = $this->dictionary->getObject("Contents");
        if (!($contents instanceof PdfArray)) {
            if ($contents instanceof PdfIndirectReference)
                $contents = new PdfArray([$contents]);
            else
                $contents = new PdfArray([]);
            $this->dictionary->setObject("Contents", $contents);
        }
        $contents->addObject($contentStream->getIndirectReference());
        $this->set("Resources", ResourceDictionary::merge($this->getResources(), $contentStream->getResourceDictionary())->getDictionary());
    }

    /**
     * Setzt einige Einträge, die für die Generierung einer Theaterkarte definitiv gesetzt oder entfernt werden sollten, auf die benötigten Standardwerte.
     * Nebenbei werden nicht benötigte Einträge entfernt
     */
    public function setDefaults() {
        $this->remove("Thumb");
        $this->remove("Dur");
        $this->remove("Trans");

        if ($this->dictionary->getObject("ArtBox") !== null && $this->getArtBox() == $this->getCropBox())
            $this->remove("ArtBox");
        if ($this->dictionary->getObject("TrimBox") !== null && $this->getTrimBox() == $this->getCropBox())
            $this->remove("TrimBox");
        if ($this->dictionary->getObject("BleedBox") !== null && $this->getBleedBox() == $this->getCropBox())
            $this->remove("BleedBox");
        if ($this->dictionary->getObject("CropBox") !== null && $this->getCropBox() == $this->getMediaBox())
            $this->remove("CropBox");
    }

    /**
     * Setzt die Produktinformationen für diese Seite
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