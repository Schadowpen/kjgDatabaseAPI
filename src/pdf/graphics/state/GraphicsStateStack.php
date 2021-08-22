<?php
namespace pdf\graphics\state;

use pdf\document\PdfRectangle;
use pdf\graphics\operator\AbstractOperator;
use pdf\graphics\operator\PopGraphicsStateOperator;
use pdf\graphics\operator\PushGraphicsStateOperator;
use pdf\graphics\TransformationMatrix;

/**
 * Stack mit den GraphicsStates, genutzt während Rendering
 * @package pdf\graphics
 */
class GraphicsStateStack
{
    /**
     * Stack mit den Graphics States
     * @var GraphicsState[]
     */
    protected $graphicsStateStack;
    /**
     * Position des höchsten Elements im GraphicsStateStack
     * @var int
     */
    protected $graphicsStateStackPos;

    /**
     * Erzeugt einen GraphicsStateStack mit einem GraphicsState, welcher aus den übergebenen Parametern erzeugt wird.
     * @param TransformationMatrix $transformationMatrix
     * @param PdfRectangle $cropBox
     * @throws \Exception
     */
    public function __construct(TransformationMatrix $transformationMatrix, PdfRectangle $cropBox)
    {
        $this->graphicsStateStack = [
            new GraphicsState($transformationMatrix, $cropBox)
        ];
        $this->graphicsStateStackPos = 0;
    }

    /**
     * Liefert den aktuellen GraphicsState
     * @return GraphicsState
     */
    public function getGraphicsState() : GraphicsState {
        return $this->graphicsStateStack[$this->graphicsStateStackPos];
    }

    /**
     * Reagiert auf einen Operatoren, der den GraphicsStateStack beeinflusst.
     * @param AbstractOperator $operator Den GraphicsStateStack beeinflussender Operator
     * @throws \Exception Wenn dieser Operator nicht angewendet werden kann
     */
    public function reactToOperator(AbstractOperator $operator) {
        switch (get_class($operator)) {
            case PushGraphicsStateOperator::class:
                array_push($this->graphicsStateStack, clone $this->graphicsStateStack[$this->graphicsStateStackPos]);
                ++ $this->graphicsStateStackPos;
                break;
            case PopGraphicsStateOperator::class:
                if ($this->graphicsStateStackPos === 0)
                    throw new \Exception("Cannot delete Last Element in GraphicsStateStack");
                array_pop($this->graphicsStateStack);
                -- $this->graphicsStateStackPos;
                break;
            default:
                $this->graphicsStateStack[$this->graphicsStateStackPos] = $this->graphicsStateStack[$this->graphicsStateStackPos]->reactToOperator($operator);
        }
    }
}