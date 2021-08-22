<?php

namespace pdf\object;

/**
 * Ein PDF-Objekt, welches einen String beinhaltet
 * @package pdf\object
 */
class PdfString extends PdfAbstractObject
{
    /**
     * In dem PdfString enthaltener, bereits geparster String
     * @var string
     */
    private $value;

    /**
     * PdfString constructor.
     * @param string $value In dem PdfString enthaltener String
     */
    public function __construct(string $value)
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
     * Liefert den Wert dieses Objektes zurück.
     * @return string
     */
    public function getValue(): string
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

        $string = "(";
        for ($i = 0; $i < $valueLength; ++$i) {
            $byte = $this->value[$i];

            switch ($byte) {
                // besondere Zeichen besonders Handhaben
                case "(":
                case ")":
                case "\\":
                    $string .= "\\" . $byte;
                    break;
                case "\r":
                    $string .= "\\r";
                    break;
                case "\n":
                    $string .= "\\n";
                    break;
                case "\t":
                    $string .= "\\t";
                    break;
                case "\x08":
                    $string .= "\\b";
                    break;
                case "\x0C":
                    $string .= "\\f";
                    break;

                default:
                    $string .= $byte;
                    break;
            }
        }
        return $string . ")";
    }

    /**
     * Wenn der ObjectParser ein bestimmtes Objekt anhand des letzten Tokens erkannt hat, kann mit dieser Funktion das Objekt erzeugt werden.
     * Es wird angenommen, dass die Delimiter am Anfang des Objektes bereits vom Tokenizer des ObjectParsers genutzt wurden, der Inhalt und die Delimiter am Ende jedoch nicht.
     * Zudem wird angenommen, dass der reuseTokenStack des Tokenizers leer ist.
     * @param ObjectParser $objectParser ObjectParser, welcher dieses Objekt erkannt hat
     * @return PdfString ein neues Objekt
     * @throws \Exception Wenn beim Parsen ein Fehler auftritt
     */
    public static function parse(ObjectParser $objectParser): PdfAbstractObject
    {
        $stringReader = $objectParser->getStringReader();
        $string = "";
        $numberOfOpenBrackets = 1;
        do {
            $string .= $stringReader->readUntilMask("\\()\r");
            $byte = $stringReader->readByte();
            switch ($byte) {
                // erlaube Balancierte Klammern
                case "(":
                    ++$numberOfOpenBrackets;
                    $string .= "(";
                    break;
                case ")":
                    --$numberOfOpenBrackets;
                    if ($numberOfOpenBrackets > 0)
                        $string .= ")";
                    break;

                // Zeilenumbrüche werden alle zu \n konvertiert.    Hier wird nur \r abgefangen, da \n nicht mehr zu \n konvertiert werden muss
                case "\r":
                    if ($stringReader->getByte($stringReader->getReaderPos()) === "\n")
                        $stringReader->skipByte();
                    $string .= "\n";
                    break;

                // Diverse Sequenzen, die mit einem \ beginnen
                case "\\":
                    $byte = $stringReader->readByte();
                    switch ($byte) {
                        case "(":
                        case ")":
                        case "\\":
                            $string .= $byte;
                            break;

                        case "n":
                            $string .= "\n";
                            break;

                        case "r":
                            $string .= "\r";
                            break;

                        case "t":
                            $string .= "\t";
                            break;

                        case "b":
                            $string .= "\x08";
                            break;

                        case "f":
                            $string .= "\x0C";
                            break;

                        case "0":
                        case "1":
                        case "2":
                        case "3":
                        case "4":
                        case "5":
                        case "6":
                        case "7":
                            $octalString = $byte . $stringReader->readOnlyMaskWithMaxLength("01234567", 2);
                            $string .= chr(octdec($octalString));
                            break;

                        // Zeilenumbrüche nach einem \ sollen ignoriert werden
                        case "\r":
                            if ($stringReader->getByte($stringReader->getReaderPos()) === "\n")
                                $stringReader->skipByte();
                            break;
                        case "\n":
                            break;

                        // bei nicht zuordnenbaren Zeichen soll der \ ignoriert werden
                        default:
                            $string .= $byte;
                            break;
                    }
                    break;
            }
        } while ($numberOfOpenBrackets > 0);

        return new PdfString($string);
    }

    /**
     * Erzeugt eine (tiefe) Kopie dieses Objektes
     * @return PdfString
     */
    public function clone(): PdfAbstractObject
    {
        return new PdfString($this->value);
    }
}