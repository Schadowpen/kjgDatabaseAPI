<?php


namespace pdf\graphics\operator;


use pdf\graphics\Point;


/**
 * Abstrakte Superklasse für alle Bild-Operatoren, die in einem Content Stream vorkommen können.
 * Das können InlineImages, aber auch XObjects sein.
 * @package pdf\graphics\operator
 */
abstract class AbstractImageOperator extends AbstractOperator
{

    public function isRenderingOperator(): bool
    {
        return true;
    }

    /**
     * Liefert den Punkt unten links in Device Space
     * @return Point
     */
    abstract function getLowerLeftCorner(): Point;

    /**
     * Liefert den Punkt unten rechts in Device Space
     * @return Point
     */
    abstract function getLowerRightCorner(): Point;

    /**
     * Liefert den Punkt oben links in Device Space
     * @return Point
     */
    abstract function getUpperLeftCorner(): Point;

    /**
     * Liefert den Punkt oben rechts in Device Space
     * @return Point
     */
    abstract function getUpperRightCorner(): Point;

    /**
     * Liefert den Namen des Bildes. Namen müssen in einem Content Stream nicht einzigartig sein.
     * @return string
     */
    abstract function getName(): string;
}