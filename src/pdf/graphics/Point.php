<?php
namespace pdf\graphics;

/**
 * Ein Punkt in einem 2-Dimensionalen Koordinatensystem
 * @package pdf\graphics
 */
class Point
{
    /**
     * X-Wert des Punktes im Koordinatensystem
     * @var float
     */
    public $x;
    /**
     * Y-Wert des Punktes im Koordinatensystem
     * @var float
     */
    public $y;

    /**
     * Point constructor.
     * @param float $x X-Wert des Punktes im Koordinatensystem
     * @param float $y Y-Wert des Punktes im Koordinatensystem
     */
    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Berechnet die Distanz zwischen diesem Punkt und dem gegebenen Punkt
     * @param Point $p
     * @return float
     */
    public function distanceTo(Point $p) : float
    {
        $dx = $this->x - $p->x;
        $dy = $this->y - $p->y;
        return sqrt($dx * $dx + $dy * $dy);
    }
}