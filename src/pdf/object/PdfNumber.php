<?php
namespace pdf\object;

/**
 * Ein PDF-Objekt, welches eine Nummer (float oder int) beinhaltet.
 * @package pdf\object
 */
class PdfNumber extends PdfAbstractObject
{
    /**
     * @var int|float
     */
    private $value;

    /**
     * Erzeugt ein Pdf Nummer Objekt.
     * Bei der Übergabe wird überprüft, ob der Wert eine Ganze Zahl oder eine Fließkommazahl ist, und entsprechend geparst.
     * @param mixed $value
     */
    public function __construct($value)
    {
        $intval = (int)$value;
        if ($value == $intval)
            $this->value = $intval;
        else
            $this->value = (float)$value;
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
     * @return int|float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Überprüft, ob der Wert eine ganze Zahl ist
     * @return bool
     */
    public function isInt()
    {
        return is_int($this->value);
    }

    /**
     * Überprüft, ob der Wert eine Fließkommazahl ist.
     * @return bool
     */
    public function isFloat()
    {
        return is_float($this->value);
    }

    /**
     * Erstellt für dieses Objekt einen String zum einbetten in eine PDF-Datei
     * @return string
     */
    public function toString(): string
    {
        return (string)$this->value;
    }

    /**
     * Wenn der ObjectParser ein bestimmtes Objekt anhand des letzten Tokens erkannt hat, kann mit dieser Funktion das Objekt erzeugt werden.
     * Es wird angenommen, dass die Delimiter am Anfang des Objektes bereits vom Tokenizer genutzt wurden, der Inhalt und die Delimiter am Ende jedoch nicht.
     * @param Tokenizer $tokenizer Tokenizer des Objectparser um weitere Token zu erhalten
     * @param ObjectParser $objectParser ObjectParser, welcher dieses Objekt erkannt hat
     * @throws \Exception Wenn beim Parsen ein Fehler auftritt
     * @return PdfNumber ein neues Objekt
     */
    public static function parse(ObjectParser $objectParser): PdfAbstractObject
    {
        $token = $objectParser->getTokenizer()->getToken();
        if (!is_numeric($token))
            throw new \Exception("could not parse Number Pdf Object");

        return new PdfNumber($token);
    }

    /**
     * Erzeugt eine (tiefe) Kopie dieses Objektes
     * @return PdfNumber
     */
    public function clone(): PdfAbstractObject
    {
        return new PdfNumber($this->value);
    }
}