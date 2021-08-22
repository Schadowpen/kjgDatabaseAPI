<?php


namespace pdf\graphics\operator;

use pdf\graphics\Point;

/**
 * Operator zum konstruieren eines Pfades.
 * Hier werden mehrere Operatoren in einer Klasse zusammengefasst.
 * @package pdf\graphics\operator
 */
abstract class PathConstructionOperator extends AbstractOperator
{
    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    public function isRenderingOperator(): bool
    {
        return false;
    }

    /**
     * Liefert den letzten Punkt des Operators, der dann als Startpunkt für den nächsten Operatoren genutzt wird.
     * Sollte der Operator einen Unterpfad beenden, wird null zurückgeliefert
     * @return null|Point
     */
    public abstract function getLastPoint() : ?Point;
}