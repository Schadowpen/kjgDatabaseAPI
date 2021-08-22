<?php

namespace pdf\object;

use pdf\PdfFile;

/**
 * Referenz auf ein anderes Indirect Object.
 * @package pdf\object
 * @see PdfFile::getIndirectObject() Um das IndirectObject zu einer IndirectReference zu erhalten
 * @see PdfFile::parseReference() Um ein Objekt, das über eine IndirectReference ausgelagert wurde, zu erhalten.
 */
class PdfIndirectReference extends PdfAbstractObject
{
    /**
     * Nummer des Indirect Objects, auf den die PdfIndirectReference verweist.
     * @var int
     */
    private $objectNumber;
    /**
     * Generierungsnummer des Indirect Objects, auf den die PdfIndirectReference verweist
     * @var int
     */
    private $generationNumber;

    /**
     * PdfIndirectReference constructor.
     * @param int $objectNumber Nummer des Indirect objects
     * @param int $generationNumber Generierungsnummer des Indirect Objects
     */
    public function __construct(int $objectNumber, int $generationNumber)
    {
        $this->objectNumber = $objectNumber;
        $this->generationNumber = $generationNumber;
    }

    /**
     * Ob vor dem Objekt ein "white space" benötigt wird, weil das Objekt nicht mit einem Trennzeichen beginnt.
     * @return bool
     * @see PdfAbstractObject::needsWhiteSpaceAfter() Ein Trennzeichen wird nur benötigt, wenn beim vorherigen Objekt ebenfalls ein Trennzeichen benötigt wird.
     */
    public function needsWhiteSpaceBefore(): bool
    {
        return true;
    }

    /**
     * Ob nach dem Objekt ein "white space" benötigt wird, weil das Objekt nicht mit einem Trennzeichen endet.
     * @return bool
     * @see PdfAbstractObject::needsWhiteSpaceBefore() Ein Trennzeichen wird nur benötigt, wenn beim nachfolgenden Objekt ebenfalls ein Trennzeichen benötigt wird.
     */
    public function needsWhiteSpaceAfter(): bool
    {
        return true;
    }

    /**
     * Liefert objectNumber und generationNumber in Form eines assoziativen Arrays zurück.
     * @return array
     */
    public function getValue()
    {
        return [
            "objectNumber" => $this->objectNumber,
            "generationNumber" => $this->generationNumber
        ];
    }

    /**
     * Liefert die Objekt Nummer des referenzierten Indirect Objects
     * @return int
     */
    public function getObjectNumber(): int
    {
        return $this->objectNumber;
    }

    /**
     * Liefert die Generierungs Nummer des referenzierten Indirect Objects
     * @return int
     */
    public function getGenerationNumber(): int
    {
        return $this->generationNumber;
    }

    /**
     * Erstellt für dieses Objekt einen String zum einbetten in eine PDF-Datei
     * @return string
     */
    public function toString(): string
    {
        return $this->objectNumber . " " . $this->generationNumber . " R";
    }

    /**
     * Wenn der ObjectParser ein bestimmtes Objekt anhand des letzten Tokens erkannt hat, kann mit dieser Funktion das Objekt erzeugt werden.
     * Es wird angenommen, dass die Delimiter am Anfang des Objektes bereits vom Tokenizer genutzt wurden, der Inhalt und die Delimiter am Ende jedoch nicht.
     * @param ObjectParser $objectParser ObjectParser, welcher dieses Objekt erkannt hat
     * @return PdfIndirectReference ein neues Objekt
     * @throws \Exception Wenn beim Parsen ein Fehler auftritt
     */
    public static function parse(ObjectParser $objectParser): PdfAbstractObject
    {
        $tokenizer = $objectParser->getTokenizer();
        $objectNumber = (int)$tokenizer->getToken();
        $generationNumber = (int)$tokenizer->getToken();
        if ($tokenizer->getToken() !== "R")
            throw new \Exception("PdfIndirectReference does not end with 'R'-Token");
        return new PdfIndirectReference($objectNumber, $generationNumber);
    }

    /**
     * Erzeugt eine (tiefe) Kopie dieses Objektes
     * @return PdfIndirectReference
     */
    public function clone(): PdfAbstractObject
    {
        return new PdfIndirectReference($this->objectNumber, $this->generationNumber);
    }
}