<?php

namespace pdf\object;

use misc;

/**
 * Dies ist eine Helferklasse für den ObjectParser. Sie extrahiert einzelne Token, anhand derer der ObjectParser ein Objekt zusammensetzen kann.
 * Token sind prinzipiell entweder ein Steuerzeichen, mit welchem Beginn und Ende von Objekten markiert werden können, oder ein Text aus den anderen Zeichen.
 * Dabei wird jeglicher "white space", also eine Aneinanderreihung von "white space" Zeichen oder auch Kommentare bereits herausgerechnet.
 * @package pdf\object
 */
class Tokenizer
{
    /**
     * Maske mit allen Zeichen, die in PDF-Dateien den Zwischenraum zwischen einzelnen PDF-Symbolen darstellen können
     * @type string
     */
    public const whiteSpaceChars = "\x00\x09\x0a\x0c\x0D\x20";
    /**
     * Maske mit allen Zeichen, die Objekte oder ähnliches in PDF-Dateien kennzeichnen können.
     * @type string
     */
    public const delimiterChars = "()<>[]{}/%";
    /**
     * Maske mit allen Zeichen, die in PDF-Dateien nicht für Symbole zur Verfügung stehen.
     * @type string
     * @see Tokenizer::whiteSpaceChars
     * @see Tokenizer::delimiterChars
     */
    public const specialChars = self::whiteSpaceChars . self::delimiterChars;

    /**
     * Zu lesender String inklusive Konstrukt, um den String gezielt auszulesen.
     * @var misc\StringReader
     */
    private $stringReader;

    /**
     * Stack mit Token, die wiederverwendet werden sollen, bevor neue Token ausgelesen werden.
     * @var string[]
     */
    private $reuseTokenStack = [];

    /**
     * Erzeugt einen neuen Tokenizer.
     * Es wird angenommen, dass die Startposition im StringReader bereits gesetzt ist
     * @param misc\StringReader $stringReader Zum auslesen des Strings
     */
    public function __construct(misc\StringReader $stringReader)
    {
        $this->stringReader = $stringReader;
    }

    /**
     * Sollte ein Token mit getToken() geholt werden, danach aber festgestellt werden dass dieser nicht benötigt wird,
     * kann er mit dieser Methode wieder in den Tokenizer geschmissen werden. Der Tokenizer verhält sich dann so, als sei der Token nie geholt worden.<br/>
     * Sollten mehrere nicht benötigte Token vorliegen, bitte in umgekehrter Reihenfolge wieder zurückgeben, wie sie geholt wurden!
     * @param string|null $token Token, welcher nicht benötigt und somit beim nächsten Aufruf von getToken zurückgeliefert werden soll.
     */
    public function reuseToken(?string $token)
    {
        if ($token !== null)
            $this->reuseTokenStack[] = $token;
    }

    /**
     * Liefert den nächsten Token im String.
     * Wenn kein nächster Token gefunden wurde, liefert die Methode null zurück
     * @return string|null
     */
    public function getToken(): ?string
    {
        // reuse Token if available
        $token = array_pop($this->reuseTokenStack);
        if ($token !== null) {
            return $token;
        }

        // skip White Space Characters
        $this->stringReader->skipOnlyMask(self::whiteSpaceChars);

        try {
            $byte = $this->stringReader->readByte();
        } catch (\Exception $exception) {
            return null; // Ende des Strings, kein Token
        }
        switch ($byte) {
            // one of the special Chars
            case "(":
            case ")":
            case "<":
            case ">":
            case "[":
            case "]":
            case "{":
            case "}":
            case "/":
                return $byte;
            case "%": // it's a comment!
                $this->stringReader->skipLine();
                return $this->getToken();
        }

        // retrieve last byte and read until Token definitively ends
        return $byte . $this->stringReader->readUntilMask(self::specialChars);
    }
}