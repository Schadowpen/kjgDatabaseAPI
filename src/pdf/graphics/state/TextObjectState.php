<?php


namespace pdf\graphics\state;

use pdf\graphics\operator\AbstractOperator;
use pdf\graphics\operator\TextNewLineAndLeadingOperator;
use pdf\graphics\operator\TextNewLineOperator;
use pdf\graphics\TransformationMatrix;

/**
 * AdditionalGraphicsState, der nur innerhalb eines Text Objekts Verwendung finden soll.
 * @package pdf\graphics\state
 */
class TextObjectState extends AbstractAdditionalGraphicsState
{
    /**
     * Die Transformationsmatrix, wo das nächste Zeichen gezeichnet werden soll
     * @var TransformationMatrix
     */
    protected $textMatrix;
    /**
     * Die Transformationsmatrix, wo die Textzeile begonnen hat
     * @var TransformationMatrix
     */
    protected $textLineMatrix;

    public function __construct()
    {
        $this->textMatrix = new TransformationMatrix();
        $this->textLineMatrix = new TransformationMatrix();
    }

    /**
     * @return TransformationMatrix
     */
    public function getTextMatrix(): TransformationMatrix
    {
        return $this->textMatrix;
    }

    /**
     * @param TransformationMatrix $textMatrix
     */
    public function setTextMatrix(TransformationMatrix $textMatrix): void
    {
        $this->textMatrix = $textMatrix;
    }

    /**
     * @return TransformationMatrix
     */
    public function getTextLineMatrix(): TransformationMatrix
    {
        return $this->textLineMatrix;
    }

    /**
     * @param TransformationMatrix $textLineMatrix
     */
    public function setTextLineMatrix(TransformationMatrix $textLineMatrix): void
    {
        $this->textLineMatrix = $textLineMatrix;
    }

    /**
     * @param TransformationMatrix $matrix
     * @see TextObjectState::setTextMatrix()
     * @see TextObjectState::setTextLineMatrix()
     */
    public function setTextMatrixAndTextLineMatrix(TransformationMatrix $matrix): void {
        $this->textMatrix = $matrix;
        $this->textLineMatrix = $matrix;
    }

    /**
     * @param GraphicsState $graphicsState GraphicsState, in dem der TextObjectState vorkommt.
     * @return TransformationMatrix
     */
    public function getTextRenderingMatrix(GraphicsState $graphicsState): TransformationMatrix
    {
        $textState = $graphicsState->getTextState();
        $additionalMatrix = new TransformationMatrix(
            $textState->getTextFontSize()->getValue() * $textState->getHorizontalScaling()->getValue(),
            0,
            0,
            $textState->getTextFontSize()->getValue(),
            0,
            $textState->getTextRise()->getValue()
        );
        return $graphicsState->getCurrentTransformationMatrix()->addTransformation($this->textMatrix)->addTransformation($additionalMatrix);
    }
}