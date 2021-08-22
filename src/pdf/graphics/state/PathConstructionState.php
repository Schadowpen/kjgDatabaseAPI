<?php


namespace pdf\graphics\state;

use pdf\graphics\operator\PathBezierOperator;
use pdf\graphics\operator\PathConstructionOperator;
use pdf\graphics\operator\PathLineOperator;
use pdf\graphics\Point;

/**
 * Zusätzlicher Status, der beim Path Construction genutzt wird.
 * In diesem Kontext wird nur der letzte Punkt des Pfades benötigt, die gezeichneten Unterpfade werden nicht getrackt.
 * @package pdf\graphics\state
 */
class PathConstructionState extends AbstractAdditionalGraphicsState
{
    /**
     * Letzer Punkt des Pfades, von dem aus der Pfad weitergeht
     * @var null|Point
     */
    protected $currentPoint;

    public function __construct()
    {
        $this->currentPoint = null;
    }

    /**
     * Letzer Punkt des Pfades, von dem aus der Pfad weitergeht
     * @return null|Point
     */
    public function getCurrentPoint() :?Point
    {
        return $this->currentPoint;
    }

    /**
     * Reagiert auf einen Operatoren, der den Pfad weiter erzeugt.
     * Dabei wird das aktuelle Objekt nicht geändert, sondern ein neuer PathConstructionState erzeugt
     * @param PathConstructionOperator $operator Operator, welcher den Pfad verändert
     * @return PathConstructionState
     * @throws \Exception Wenn ein Unterpfad gezeichnet werden soll, ohne dass ein Unterpfad begonnen oder bereits abgeschlossen wurde.
     */
    public function reactToOperator(PathConstructionOperator $operator)
    {
        if ($this->currentPoint === null && ($operator instanceof PathLineOperator || $operator instanceof PathBezierOperator))
            throw new \Exception("A Path Construction Operator was found in a Content Stream with no Path to be added to");
        $pathConstructionState = new PathConstructionState();
        $pathConstructionState->currentPoint = $operator->getLastPoint();
        return $pathConstructionState;
    }
}