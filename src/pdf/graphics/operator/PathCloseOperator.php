<?php
namespace pdf\graphics\operator;


use pdf\graphics\Point;

/**
 * Operator zum beenden des aktuellen Unterpfades, indem eine gerade Linie vom letzten Punkt zum Startpunkt des Unterpfades gezeichnet wird.
 * Danach muss ein neuer Unterpfad mit dem m oder re Operatoren geöffnet werden
 * @package pdf\graphics\operator
 */
class PathCloseOperator extends PathConstructionOperator
{

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "h";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt.
     * Dieser Beinhaltet auch einen EOL-Marker am Ende des Operatoren
     * @return string
     */
    function __toString(): string
    {
        return "h\n";
    }

    /**
     * Liefert den letzten Punkt des Operators, der dann als Startpunkt für den nächsten Operatoren genutzt wird.
     * Sollte der Operator einen Unterpfad beenden, wird null zurückgeliefert
     * @return null|Point
     */
    public function getLastPoint(): ?Point
    {
        return null;
    }
}