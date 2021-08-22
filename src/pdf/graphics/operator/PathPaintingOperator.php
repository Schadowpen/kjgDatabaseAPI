<?php


namespace pdf\graphics\operator;

/**
 * Operator zum Zeichnen eines vorher konstruierten Pfades.
 * Da es nicht relevant ist, werden hier mehrere Operatoren mit einer Klasse zusammengefasst.
 * @package pdf\graphics\operator
 */
abstract class PathPaintingOperator extends AbstractOperator
{
    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    public function isRenderingOperator(): bool
    {
        return true;
    }
}