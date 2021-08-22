<?php


namespace pdf\graphics\operator;

/**
 * Operator, der den aktuellen Pfad zuerst zeichnet und dann füllt. Welche Flächen zu füllen sind und welche nicht, wird nach der Even-Odd-Regel bestimmt.
 * Offene Unterpfade, also welche die einen Anfang und ein Ende haben, werden nur für das Füllen geschlossen
 * @package pdf\graphics\operator
 */
class FillAndStrokePathEvenOddOperator extends PathPaintingOperator
{

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "B*";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt.
     * Dieser Beinhaltet auch einen EOL-Marker am Ende des Operatoren
     * @return string
     */
    function __toString(): string
    {
        return "B*\n";
    }
}