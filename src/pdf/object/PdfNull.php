<?php

namespace pdf\object;

/**
 * Repräsentiert ein Null Objekt in einer PDF-Datei
 * @package pdf\object
 */
class PdfNull extends PdfAbstractObject
{

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
     * Liefert den Wert dieses Objektes zurück.
     * @return null
     */
    public function getValue()
    {
        return null;
    }

    /**
     * Erstellt für dieses Objekt einen String zum einbetten in eine PDF-Datei
     * @return string
     */
    public function toString(): string
    {
        return "null";
    }

    /**
     * Wenn der ObjectParser ein bestimmtes Objekt anhand des letzten Tokens erkannt hat, kann mit dieser Funktion das Objekt erzeugt werden.
     * Es wird angenommen, dass die Delimiter am Anfang des Objektes bereits vom Tokenizer genutzt wurden, der Inhalt und die Delimiter am Ende jedoch nicht.
     * @param ObjectParser $objectParser ObjectParser, welcher dieses Objekt erkannt hat
     * @throws \Exception Wenn der Token nicht "null" Enthält
     * @return PdfNull ein neues Objekt
     */
    public static function parse(ObjectParser $objectParser): PdfAbstractObject
    {
        $token = $objectParser->getTokenizer()->getToken();
        if ($token !== "null")
            throw new \Exception("Could not parse null Pdf Object");

        return new PdfNull();
    }

    /**
     * Erzeugt eine (tiefe) Kopie dieses Objektes
     * @return PdfNull
     */
    public function clone(): PdfAbstractObject
    {
        return new PdfNull();
    }
}