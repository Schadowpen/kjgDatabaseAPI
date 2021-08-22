<?php
namespace pdf\object;
use Exception;
use misc;

/**
 * Parser zum Einlesen von PDF Objekten, ausgenommen indirekte Objekte
 * @package pdf\object
 */
class ObjectParser
{
    /**
     * Zum Einlesen des Strings, beinhaltet den String.
     * @var misc\StringReader
     */
    private $stringReader;
    /**
     * Zum Erstellen der Token während des Einlesens
     * @var Tokenizer
     */
    private $tokenizer;

    /**
     * Erzeugt einen neuen ObjectParser und setzt ihn an die Stelle des Strings, wo der Parser beginnen soll zu Parsen.
     * @param misc\StringReader $stringReader Reader zum fortlaufenden Lesen des Strings. Seine Position wird während des Parsens verwendet.
     * @param int|null $objectStartPos Bei welcher Position im String der Parser anfangen soll zu lesen. Wenn nicht angegeben, wird die im StringReader gespeicherte Position verwendet.
     */
    public function __construct(misc\StringReader $stringReader, int $objectStartPos = null)
    {
        $this->stringReader = $stringReader;
        if ($objectStartPos != null)
            $this->stringReader->setReaderPos($objectStartPos);
        $this->tokenizer = new Tokenizer($this->stringReader);
    }

    /**
     * Liefert den von dem ObjectParser genutzten StringReader
     * @return misc\StringReader
     */
    public function getStringReader(): misc\StringReader
    {
        return $this->stringReader;
    }

    /**
     * Liefert den von dem ObjectParser genutzten Tokenizer
     * @return Tokenizer
     */
    public function getTokenizer(): Tokenizer
    {
        return $this->tokenizer;
    }

    /**
     * Liest ein Objekt beginnend ab der Position, wo der StringReader gerade positioniert ist, ein.
     * Nach dem Einlesen des Objektes ist der StringReader exakt hinter dem Objekt positioniert.
     * @param bool $allowSingleToken Ob einzelne Token, die nicht zu einem Objekt gehören, erlaubt sein sollen. Wenn nicht angegeben, ist der Wert auf false eingestellt
     * @return PdfAbstractObject ausgelesenes Objekt
     * @throws Exception Wenn kein Objekt ausgelesen werden kann
     */
    public function parseObject($allowSingleToken = false) {
        $token = $this->tokenizer->getToken();

        switch ($token) {
            case null:
                throw new Exception("Tokenizer did not find any Token");

            case "(":
                // Erkenne literal Strings
                return PdfString::parse($this);
            case "<":
                if ($this->stringReader->getByte($this->stringReader->getReaderPos()) === "<") {
                    // Erkenne Dictionary, lese auch das zweite <
                    $this->stringReader->skipByte();
                    return PdfDictionary::parse($this);
                } else {
                    // Erkenne Hexadezimale Strings
                    return PdfHexString::parse($this);
                }
            case "[":
                // Erkenne PDF Arrays
                return PdfArray::parse($this);
            case "/":
                // Erkenne Name objects
                return PdfName::parse($this);

                // Erkenne Boolean
            case "true":
                return new PdfBoolean(true);
            case "false":
                return new PdfBoolean(false);

                // Erkenne Null Object
            case "null":
                return new PdfNull();
        }

        // Erkenne Zahlen.
        // indirect References bestehen aus zwei Zahlen und einem R dahinter, dies muss auch erkannt werden
        if (is_numeric($token)) {
            $token2 = $this->tokenizer->getToken();
            if (is_numeric($token2)) {
                $token3 = $this->tokenizer->getToken();
                if ($token3 === "R") {
                    // Referenz auf ein Indirektes Objekt erkannt
                    return new PdfIndirectReference((int) $token, (int) $token2);
                }
                $this->tokenizer->reuseToken($token3);
            }
            $this->tokenizer->reuseToken($token2);

            // Einfache Zahl erkannt
            return new PdfNumber($token);
        }

        // Wenn Token keinem gängigen PDF-Objekt angehört.
        if ($allowSingleToken)
            return new PdfToken($token);

        throw new Exception("Token '".$token."' could not be identified to an Object Type by ObjectParser");
    }
}

