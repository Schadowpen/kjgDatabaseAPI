<?php

namespace pdf\object;

/**
 * Repräsentiert ein Boolean Objekt aus einer PDF-Datei
 * @package pdf\object
 */
class PdfBoolean extends PdfAbstractObject
{
    /**
     * Wert dieses Boolean
     * @var bool
     */
    private $value;

    /**
     * Erzeugt einen PdfBoolean.
     * @param bool $value true oder false
     */
    public function __construct(bool $value)
    {
        $this->value = $value;
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
     * Liefert den Wert dieses Objektes zurück.
     * @return bool
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Erstellt für dieses Objekt einen String zum einbetten in eine PDF-Datei
     * @return string
     */
    public function toString(): string
    {
        return $this->value ? "true" : "false";
    }

    /**
     * Wenn der ObjectParser ein bestimmtes Objekt anhand des letzten Tokens erkannt hat, kann mit dieser Funktion das Objekt erzeugt werden.
     * Es wird angenommen, dass die Delimiter am Anfang des Objektes bereits vom Tokenizer genutzt wurden, der Inhalt und die Delimiter am Ende jedoch nicht.
     * @param ObjectParser $objectParser ObjectParser, welcher dieses Objekt erkannt hat
     * @throws \Exception Wenn beim Parsen ein Fehler auftritt
     * @return PdfBoolean ein neues Objekt
     */
    public static function parse(ObjectParser $objectParser): PdfAbstractObject
    {
        $token = $objectParser->getTokenizer()->getToken();
        switch ($token) {
            case "true":
                return new PdfBoolean(true);
            case "false":
                return new PdfBoolean(false);
            default:
                throw new \Exception("could not parse Boolean Pdf Object");
        }
    }

    /**
     * Erzeugt eine (tiefe) Kopie dieses Objektes
     * @return PdfBoolean
     */
    public function clone(): PdfAbstractObject
    {
        return new PdfBoolean($this->value);
    }
}