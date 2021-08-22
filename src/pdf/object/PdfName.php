<?php

namespace pdf\object;

use misc\StringReader;
use \Exception;

/**
 * Ein PDF-Objekt, welches einen Namen beinhaltet
 * @package pdf\object
 */
class PdfName extends PdfAbstractObject
{
    /**
     * In diesem Objekt gespeicherter Name
     * @var string
     */
    private $value;

    /**
     * PdfName constructor.
     * @param string $value In dem PdfName gespeicherter Name
     * @throws Exception Wenn in dem Namen ein nicht erlaubtes Zeichen vorkommt.
     */
    public function __construct(string $value)
    {
        if (strpos($value, "\x00") !== false)
            throw new Exception("no null Character allowed in Pdf Names");
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
        return true;
    }

    /**
     * Liefert den Wert dieses Objektes zurück.
     * @return string
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
        $valueLength = strlen($this->value);
        $string = "/";

        for ($i = 0; $i < $valueLength; ++$i) {
            $byte = $this->value[$i];
            $byteCode = ord($byte);
            $isSpecialChar = strspn($byte, Tokenizer::delimiterChars . "#") === 1;
            if ($byteCode < 33 || $byteCode > 126 || $isSpecialChar)
                $string .= "#" . dechex($byteCode);
            else
                $string .= $byte;
        }
        return $string;
    }

    /**
     * Wenn der ObjectParser ein bestimmtes Objekt anhand des letzten Tokens erkannt hat, kann mit dieser Funktion das Objekt erzeugt werden.
     * Es wird angenommen, dass die Delimiter am Anfang des Objektes bereits vom Tokenizer des ObjectParsers genutzt wurden, der Inhalt und die Delimiter am Ende jedoch nicht.
     * Zudem wird angenommen, dass der reuseTokenStack des Tokenizers leer ist.
     * @param ObjectParser $objectParser ObjectParser, welcher dieses Objekt erkannt hat
     * @return PdfName ein neues Objekt
     */
    public static function parse(ObjectParser $objectParser): PdfAbstractObject
    {
        $stringReader = $objectParser->getStringReader();
        $objectName = "";
        do {
            $objectName .= $stringReader->readUntilMask(Tokenizer::specialChars . "#");
            try {
                $byte = $stringReader->readByte();
                if ($byte === "#") {
                    $hexString = $stringReader->readSubstring(2);
                    $objectName .= chr(hexdec($hexString));
                } else {
                    // Dieses Byte könnte zum nächsten Token gehören, daher wieder als ungelesen markiereen
                    $stringReader->retrieveLastByte();
                }
            } catch (\Exception $exception) {
                // Das Ende des Strings wurde erreicht
                $byte = null;
            }
        } while ($byte === "#");

        // Exception würde geschmissen, wenn \x00 in $objectName vorkommt. Das ist aber nicht möglich.
        return new PdfName($objectName);
    }

    /**
     * Erzeugt eine (tiefe) Kopie dieses Objektes
     * @return PdfName
     */
    public function clone(): PdfAbstractObject
    {
        // Exception würde geschmissen, wenn der Namenswert illegale Zeichen beinhaltet. Das kann beim Klonen nicht passieren.
        return new PdfName($this->value);
    }
}