<?php
namespace pdf\object;

/**
 * Diese Klasse repräsentiert einen einzelnen Token, der nicht zu einem PDF-Objekt zugeordnet werden kann.
 * Normale PDF-Objekte dürfen keine solcher reinen Token beinhalten, sie können aber beispielsweise in Content Streams auftreten.
 * @package pdf\object
 */
class PdfToken extends PdfAbstractObject
{
    /**
     * String-Wert dieses Token.
     * @var string
     */
    private $value;

    /**
     * Erzeugt einen neuen PdfToken
     * @param string $value Inhalt dieses Token
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
     * Manche Objekte liefern darüber hinaus bessere Methoden, um den Objektinhalt abzufragen.
     * @return mixed
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
        return $this->value;
    }

    /**
     * Wenn der ObjectParser ein bestimmtes Objekt anhand des letzten Tokens erkannt hat, kann mit dieser Funktion das Objekt erzeugt werden.
     * Es wird angenommen, dass die Delimiter am Anfang des Objektes bereits vom Tokenizer genutzt wurden, der Inhalt und die Delimiter am Ende jedoch nicht.
     * @param Tokenizer $tokenizer Tokenizer des Objectparser um weitere Token zu erhalten
     * @param ObjectParser $objectParser ObjectParser, welcher dieses Objekt erkannt hat
     * @throws \Exception Wenn beim Parsen ein Fehler auftritt
     * @return PdfToken ein neues Objekt
     */
    public static function parse(ObjectParser $objectParser): PdfAbstractObject
    {
        return new PdfToken($objectParser->getTokenizer()->getToken());
    }

    /**
     * Erzeugt eine (tiefe) Kopie dieses Objektes
     * @return PdfToken
     */
    public function clone(): PdfAbstractObject
    {
        return new PdfToken($this->value);
    }
}