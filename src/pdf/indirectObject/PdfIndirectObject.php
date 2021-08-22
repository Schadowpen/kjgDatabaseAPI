<?php
namespace pdf\indirectObject;

use pdf\object\PdfAbstractObject;
use pdf\object\PdfIndirectReference;

/**
 * Indirect Object einer Pdf-Datei
 * @package pdf\indirectObject
 */
class PdfIndirectObject
{
    /**
     * Positive Nummer des Indirekten Objektes, Teil des Objekt Identifiers
     * @var int
     */
    protected $objectNumber;
    /**
     * Generierungsnummer des Indirekten Objektes, Teil des Objekt Identifiers
     * @var int
     */
    protected $generationNumber;
    /**
     * Inhalt des IndirectObjects
     * @var PdfAbstractObject
     */
    protected $containingObject;

    /**
     * Erzeugt ein PdfIndirectObject
     * @param int $objectNumber Positive Nummer des Indirekten Objektes, Teil des Objekt Identifiers
     * @param int $generationNumber Generierungsnummer des Indirekten Objektes, Teil des Objekt Identifiers
     * @param PdfAbstractObject $containingObject Inhalt des IndirectObjects
     */
    public function __construct(int $objectNumber, int $generationNumber, PdfAbstractObject $containingObject)
    {
        $this->objectNumber = $objectNumber;
        $this->generationNumber = $generationNumber;
        $this->containingObject = $containingObject;
    }

    /**
     * @return int
     */
    public function getObjectNumber(): int
    {
        return $this->objectNumber;
    }

    /**
     * @return int
     */
    public function getGenerationNumber(): int
    {
        return $this->generationNumber;
    }

    /**
     * @return PdfAbstractObject
     */
    public function getContainingObject(): PdfAbstractObject
    {
        return $this->containingObject;
    }

    /**
     * @param int $objectNumber
     */
    public function setObjectNumber(int $objectNumber): void
    {
        $this->objectNumber = $objectNumber;
    }

    /**
     * @param int $generationNumber
     */
    public function setGenerationNumber(int $generationNumber): void
    {
        $this->generationNumber = $generationNumber;
    }

    /**
     * @param PdfAbstractObject $containingObject
     */
    public function setContainingObject(PdfAbstractObject $containingObject): void
    {
        $this->containingObject = $containingObject;
    }

    /**
     * Erstellt für dieses Objekt einen String zum Einbetten in eine PDF-Datei
     * @return string
     */
    public function toString(): string
    {
        return "{$this->objectNumber} {$this->generationNumber} obj\n"
            . $this->containingObject->toString() . "\n"
            . "endobj\n";
    }

    /**
     * Liefert eine IndirectReference, die auf dieses IndirectObject weist
     * @return PdfIndirectReference
     */
    public function getIndirectReference(): PdfIndirectReference
    {
        return new PdfIndirectReference($this->objectNumber, $this->generationNumber);
    }
}