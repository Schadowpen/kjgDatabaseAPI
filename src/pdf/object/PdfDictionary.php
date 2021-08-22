<?php

namespace pdf\object;

/**
 * Ein Pdf-Objekt, welches ein Dictionary beinhaltet.
 * Dies wird in PHP mit einem assoziativen Array realisiert.
 * @package pdf\object
 */
class PdfDictionary extends PdfAbstractObject
{
    /**
     * Assoziatives Array mit den Schlüssel-Wert Paaren, die das Dictionary beinhaltet
     * @var array
     */
    private $value;

    /**
     * Erzeugt ein Dictionary anhand eines assoziativen Arrays, entfernt alle PdfNull-Objekte
     * @param PdfAbstractObject[] $associativeArray Assoziatives Array mit den Name-Wert Paaren, die das Dictionary beinhaltet
     */
    public function __construct(array $associativeArray)
    {
        foreach ($associativeArray as $key => $value) {
            if ($value instanceof PdfNull)
                unset($associativeArray[$key]);
        }
        $this->value = $associativeArray;
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
     * Liefert den Inhalt dieses Objektes als assoziatives Array zurück.
     * @return array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Liefert ein einzelnes Objekt aus dem Dictionary, identifiziert anhand des Namens, unter welchem es eingespeichert ist.
     * @param string $key Name, unter welchem das Pdf Objekt gespeichert ist
     * @return PdfAbstractObject|null
     */
    public function getObject(string $key): ?PdfAbstractObject
    {
        return @$this->value[$key];
    }

    /**
     * Liefert zurück, ob ein Eintrag unter diesem Namen in dem Dictionary existiert
     * @param string $key Name, unter welchem das Pdf Objekt gespeichert ist
     * @return bool
     */
    public function hasObject(string $key): bool
    {
        return @$this->value[$key] !== null;
    }

    /**
     * Fügt hinzu oder überschreibt ein einzelnes Objekt in dem Dictionary
     * @param string $key Name, unter welchem das Objekt gespeichert werden soll
     * @param PdfAbstractObject|null $object Objekt, welches hinzugefügt werden soll
     */
    public function setObject(string $key, ?PdfAbstractObject $object)
    {
        if ($object === null)
            unset($this->value[$key]);
        else
            $this->value[$key] = $object;
    }

    /**
     * Entfernt ein Objekt aus dem Dictionary
     * @param string $key Name, unter welchem das Pdf Objekt gespeichert ist
     */
    public function removeObject(string $key)
    {
        unset($this->value[$key]);
    }

    /**
     * Liefert die Namen zurück, unter welchen in dem Dictionary Einträge vorhanden sind
     * @return string[]
     */
    public function getKeys(): array
    {
        return array_keys($this->value);
    }

    /**
     * Erstellt für dieses Objekt einen String zum einbetten in eine PDF-Datei.
     * Die Einträge in dem String sind nach Name sortiert.
     * @return string
     * @throws \Exception Wenn einer der Namen nicht geparst werden kann
     */
    public function toString(): string
    {
        ksort($this->value);
        $string = "<<";

        foreach ($this->value as $key => $valueObj) {
            // add Key
            $string .= (new PdfName($key))->toString();

            // add Value
            if ($valueObj->needsWhiteSpaceBefore())
                $string .= " ";
            $string .= $valueObj->toString();
        }
        return $string . ">>";
    }

    /**
     * Wenn der ObjectParser ein bestimmtes Objekt anhand des letzten Tokens erkannt hat, kann mit dieser Funktion das Objekt erzeugt werden.
     * Es wird angenommen, dass die Delimiter am Anfang des Objektes bereits vom Tokenizer des ObjectParsers genutzt wurden, der Inhalt und die Delimiter am Ende jedoch nicht.
     * @param ObjectParser $objectParser ObjectParser, welcher dieses Objekt erkannt hat
     * @return PdfAbstractObject ein neues Objekt
     * @throws \Exception Wenn beim Parsen ein Fehler auftritt
     */
    public static function parse(ObjectParser $objectParser): PdfAbstractObject
    {
        $associativeArray = [];
        $keyObj = $objectParser->parseObject(true);

        // Solange das Erste Objekt ein Name ist, lese weiter
        while ($keyObj instanceof PdfName) {
            $valueObj = $objectParser->parseObject(false);
            $associativeArray[$keyObj->getValue()] = $valueObj;

            $keyObj = $objectParser->parseObject(true);
        }

        // Prüfe auf Korrektes Beenden des Dictionary und dass key ein PdfName ist
        if (!($keyObj instanceof PdfToken && $keyObj->getValue() === ">"))
            throw new \Exception("PdfDictionary does not contain Name Object, neither ends with >>");
        if ($objectParser->getStringReader()->readByte() !== ">")
            throw new \Exception("PdfDictionary does not end with >>");

        return new PdfDictionary($associativeArray);
    }

    public function equals($another): bool
    {
        if (self::class !== get_class($another))
            return false;

        // Überprüfe Menge der Schlüssel
        if ($this->getKeys() != $another->getKeys())
            return false;

        // Für jeden Schlüssel, verlgeiche Wert
        foreach ($this->getKeys() as $key) {
            if (!$this->getObject($key)->equals($another->getObject($key)))
                return false;
        }

        return true;
    }

    /**
     * Erzeugt eine (tiefe) Kopie dieses Objektes
     * @return PdfDictionary
     */
    public function clone(): PdfAbstractObject
    {
        $newValue = [];
        foreach ($this->value as $key => $value) {
            $newValue[$key] = $value->clone();
        }
        return new PdfDictionary($newValue);
    }

    /**
     * Baut das Übergebene PdfDictionary in dieses Dictionary ein.
     * @param PdfDictionary $another
     */
    public function merge(PdfDictionary $another)
    {
        $this->value = array_merge($another->value, $this->value);
    }
}