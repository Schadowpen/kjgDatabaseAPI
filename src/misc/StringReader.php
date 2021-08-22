<?php

namespace misc;
/**
 * Diese Klasse dient dazu, einen String konsekutiv lesen zu können.
 * Man kann den Reader jederzeit an eine gewünschte Stelle im String positionieren und dann Byte- oder Zeilenweise lesen.<br/>
 * Dazu sind die Funktionen zu nutzen, die mit "read" beginnen. Diese setzen dann die Leseposition entsprechend, wie viele Bytes gelesen wurden.
 * Mit Funktionen, die mit "skip" beginnen, können die entsprechenden Bytes übersprungen werden, ohne sie zu lesen.
 */
class StringReader
{
    /**
     * String, welcher mit dem StringReader gelesen werden soll, ist als immutable zu behandeln
     * @var string
     */
    private $string;
    /**
     * Länge von $string, um nicht immer strlen() aufrufen zu müssen
     * @var int
     */
    private $stringLength;
    /**
     * Aktuelle Leseposition, wird in den read...-Funtkionen verwendet
     * @var int
     */
    private $readerPos;

    /**
     * Erzeugt einen neuen StringReader
     * @param string $string welcher gelesen werden soll
     * @param int $readerPos bei welcher Byteposition der String angefangen werden soll zu lesen, wenn nicht angegeben Null
     */
    public function __construct(string $string, int $readerPos = 0)
    {
        $this->string = $string;
        $this->stringLength = strlen($string);
        $this->readerPos = $readerPos;
    }

    /**
     * Liefert den String, welchen der StringReader liest
     * @return mixed
     * @see StringReader::getStringLength()
     */
    public function getString(): string
    {
        return $this->string;
    }

    /**
     * Liefert die Länge des Strings
     * @return int
     * @see StringReader::getString()
     */
    public function getStringLength(): int
    {
        return $this->stringLength;
    }

    /**
     * Liefert die aktuelle Leseposition
     * @return int
     */
    public function getReaderPos(): int
    {
        return $this->readerPos;
    }

    /**
     * Setzt die Leseposition neu.
     * @param int $readerPos
     */
    public function setReaderPos(int $readerPos): void
    {
        $this->readerPos = $readerPos;
    }


    /**
     * Liefert das Byte an der angegebenen Position.
     * Wenn sich die Position außerhalb des Strings befindet, wird null zurückgegeben
     * @param int $pos
     * @return string|null
     */
    public function getByte(int $pos): string
    {
        return @$this->string[$pos];
    }

    /**
     * Liefert einen Teilstring dieses Strings
     * @param int $start Start des Teilstrings
     * @param int $length Länge des Teilstrings
     * @return string|false
     * @see substr()
     */
    public function getSubstring(int $start, int $length): string
    {
        return substr($this->string, $start, $length);
    }


    /**
     * Liest ein einzelnes Byte/Zeichen
     * @return string
     * @throws \Exception Wenn der String das Ende Erreicht hat
     */
    public function readByte(): string
    {
        if ($this->isAtEndOfString())
            throw new \Exception("unable to read Byte, StringReader already at End of String");

        $byte = $this->string[$this->readerPos];
        ++$this->readerPos;
        return $byte;
    }

    /**
     * Liest einen Teilstring mit anzugebener Länge
     * @param int $length Länge des zu lesenden Strings
     * @return string
     * @throws \Exception Wenn der String nicht mehr lang genug ist zum Lesen des Substrings
     */
    public function readSubstring(int $length): string
    {
        if ($this->exceedsEndOfString($length))
            throw new \Exception("unable to read Bytes, StringReader would exceed End of String");

        $str = substr($this->string, $this->readerPos, $length);
        $this->readerPos += $length;
        return $str;
    }

    /**
     * Liest bis zum Ende der Zeile. Der Zeilenumbruch ist in der Rückgabe nicht enthalten.
     * Die Leseposition wird hinter das Ende der Zeile gesetzt
     * @return string
     */
    public function readLine(): string
    {
        // finde nächstes Zeilenumbruchzeichen
        $lineLength = strcspn($this->string, "\r\n", $this->readerPos);
        $lineEnd = $this->readerPos + $lineLength;
        $lineContent = substr($this->string, $this->readerPos, $lineLength);

        // Überprüfe welche Art von Zeilenumbruch
        if ($lineEnd === $this->stringLength)
            $this->readerPos = $lineEnd;
        else if ($this->string[$lineEnd] === "\r" && @$this->string[$lineEnd + 1] === "\n")
            $this->readerPos = $lineEnd + 2;
        else
            $this->readerPos = $lineEnd + 1;

        return $lineContent;
    }

    /**
     * Lese den String, aber nur solange die in $mask angegebenen Zeichen auftauchen.
     * Sobald ein anderes Zeichen auftritt, wird das Lesen beendet.
     * @param string $mask Maske bestehend aus allen Zeichen, die gelesen werden dürfen
     * @return string
     * @see strspn()
     */
    public function readOnlyMask(string $mask): string
    {
        $length = strspn($this->string, $mask, $this->readerPos);
        $str = substr($this->string, $this->readerPos, $length);
        $this->readerPos += $length;
        return $str;
    }

    /**
     * Lese den String, aber nur solange die in $mask angegebenen Zeichen auftauchen.
     * Sobald ein anderes Zeichen auftritt oder die Maximalzahl an Zeichen überschritten wird, wird das Lesen beendet.
     * @param string $mask Maske bestehend aus allen Zeichen, die gelesen werden dürfen
     * @param int $maxLength Maximale Anzahl an Bytes, die gelesen werden dürfen.
     * @return string
     * @see strspn()
     */
    public function readOnlyMaskWithMaxLength(string $mask, int $maxLength)
    {
        $length = strspn($this->string, $mask, $this->readerPos, $maxLength);
        $str = substr($this->string, $this->readerPos, $length);
        $this->readerPos += $length;
        return $str;
    }

    /**
     * Lese den String, bis eines der in $mask angegebenen Zeichen auftaucht
     * @param string $mask Maske bestehend aus allen Zeichen, die das Lesen beenden sollen
     * @return string
     * @see strcspn()
     */
    public function readUntilMask(string $mask): string
    {
        $length = strcspn($this->string, $mask, $this->readerPos);
        $str = substr($this->string, $this->readerPos, $length);
        $this->readerPos += $length;
        return $str;
    }


    /**
     * Überspringt ein einzelnes Byte/Zeichen
     */
    public function skipByte()
    {
        ++$this->readerPos;
    }

    /**
     * Überspringt einen Teilstring mit anzugebener Länge.
     * Wird eine negative Länge angegeben, werden die letzten Zeichen quasi wiederhergestellt.
     * @param int $length Länge des zu lesenden Strings
     */
    public function skipSubstring(int $length)
    {
        $this->readerPos += $length;
    }

    /**
     * Überspringt die aktuelle Zeile
     */
    public function skipLine()
    {
        // finde nächstes Zeilenumbruchzeichen
        $lineLength = strcspn($this->string, "\r\n", $this->readerPos);
        $lineEnd = $this->readerPos + $lineLength;

        // Überprüfe welche Art von Zeilenumbruch
        if ($lineEnd === $this->stringLength)
            $this->readerPos = $lineEnd;
        else if ($this->string[$lineEnd] === "\r" && @$this->string[$lineEnd + 1] === "\n")
            $this->readerPos = $lineEnd + 2;
        else
            $this->readerPos = $lineEnd + 1;
    }

    /**
     * Überspringe den String, aber nur die in $mask angegebenen Zeichen.
     * @param string $mask Maske bestehend aus allen Zeichen, die übersprungen werden sollen
     * @see strspn()
     */
    public function skipOnlyMask(string $mask)
    {
        $length = strspn($this->string, $mask, $this->readerPos);
        $this->readerPos += $length;
    }

    /**
     * Überspringe den String, bis eines der in $mask angegebenen Zeichen auftaucht
     * @param string $mask Maske bestehend aus allen Zeichen, die das Überspringen beenden sollen
     * @see strcspn()
     */
    public function skipUntilMask(string $mask)
    {
        $length = strcspn($this->string, $mask, $this->readerPos);
        $this->readerPos += $length;
    }

    /**
     * Macht das letzte Byte ungelesen, indem die Leseposition ein Byte in Richtung Anfang des Strings gesetzt wird.
     */
    public function retrieveLastByte() {
        --$this->readerPos;
    }


    /**
     * Liefert zurück, ob der StringReader am Ende des Strings angekommen ist.
     * @return bool true, wenn das Ende erreicht wurde
     */
    public function isAtEndOfString(): bool
    {
        return $this->readerPos >= $this->stringLength;
    }

    /**
     * Liefert zurück, ob diese Anzahl an zu lesenden Bytes das Ende des Strings überschreiten würde.
     * @param int $bytesToRead Anzahl an zu lesenden Zeichen von der aktuellen Leseposition an
     * @return bool true wenn Überschreitung droht
     */
    public function exceedsEndOfString(int $bytesToRead): bool
    {
        return $this->readerPos + $bytesToRead > $this->stringLength;
    }
}