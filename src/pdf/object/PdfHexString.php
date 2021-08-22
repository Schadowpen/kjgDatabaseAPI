<?php

namespace pdf\object;

/**
 * Ein PDF-Objekt, welches einen Hexadezimal codierten String beinhaltet.
 * @package pdf\object
 */
class PdfHexString extends PdfAbstractObject
{
    /**
     * Inhalt dieses HexStrings, als Hexadezimaler Wert gespeichert
     * @var string
     */
    private $value;

    /**
     * Erzeugt einen neuen PdfHexString
     * @param string $value Hexadezimaler String
     */
    public function __construct($value)
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
        return false;
    }

    /**
     * Ob nach dem Objekt ein "white space" benötigt wird, weil das Objekt nicht mit einem Trennzeichen endet.
     * @return bool
     * @see PdfAbstractObject::needsWhiteSpaceBefore() Ein Trennzeichen wird nur benötigt, wenn beim nachfolgenden Objekt ebenfalls ein Trennzeichen benötigt wird.
     */
    public function needsWhiteSpaceAfter(): bool
    {
        return false;
    }

    /**
     * Liefert den Hexadezimalen String zurück.
     * @return string
     */
    public function getHexValue()
    {
        return $this->value;
    }

    /**
     * Transformiert die Hexadezimale Darstellung in einen Byte-String
     * @return string
     */
    public function getValue(): string
    {
        $stringLength = strlen($this->value);
        $value = $this->value . ($stringLength % 2 === 1 ? "0" : ""); // Bei einer ungeraden Anzahl an Hexadezimalen Ziffern wird angenommen, dass die letzte Ziffer 0 ist
        $result = "";
        for ($i = 0; $i < $stringLength; $i += 2)
            $result .= chr(hexdec(substr($value, $i, 2)));
        return $result;
    }

    /**
     * Erstellt für dieses Objekt einen String zum einbetten in eine PDF-Datei
     * @return string
     */
    public function toString(): string
    {
        return "<" . $this->value . ">";
    }

    /**
     * Wenn der ObjectParser ein bestimmtes Objekt anhand des letzten Tokens erkannt hat, kann mit dieser Funktion das Objekt erzeugt werden.
     * Es wird angenommen, dass die Delimiter am Anfang des Objektes bereits vom Tokenizer genutzt wurden, der Inhalt und die Delimiter am Ende jedoch nicht.
     * @param ObjectParser $objectParser ObjectParser, welcher dieses Objekt erkannt hat
     * @return PdfHexString ein neues Objekt
     */
    public static function parse(ObjectParser $objectParser): PdfAbstractObject
    {
        $tokenizer = $objectParser->getTokenizer();
        $value = "";
        $token = $tokenizer->getToken();
        while ($token !== ">") {
            $value .= $token;
            $token = $tokenizer->getToken();
        }
        return new PdfHexString($value);
    }

    /**
     * Transformiert einen Byte-String in seine Hexadezimale Darstellung
     * @param string $string Repräsentation als Byte-String
     * @return PdfHexString
     */
    public static function parseString(string $string): PdfHexString
    {
        $stringLength = strlen($string);
        $value = "";
        for ($i = 0; $i < $stringLength; ++$i) {
            $value .= str_pad(dechex(ord($string[$i])), 2, "0", STR_PAD_LEFT);
        }
        return new PdfHexString($value);
    }

    /**
     * Erzeugt eine (tiefe) Kopie dieses Objektes
     * @return PdfHexString
     */
    public function clone(): PdfAbstractObject
    {
        return new PdfHexString($this->value);
    }
}