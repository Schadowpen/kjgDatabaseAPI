<?php


namespace pdf\graphics\operator;


use pdf\graphics\Point;
use pdf\graphics\state\GraphicsState;
use pdf\graphics\TransformationMatrix;
use pdf\object\PdfArray;
use pdf\object\PdfNumber;
use pdf\object\PdfString;

/**
 * Operator zum zeichnen von Text.
 * Bei diesem Text ist zwischen einzelnen Textstücken angegeben, wie weit ein horizontaler Abstand zwischen den beiden Textstücken sein soll.
 * Diese Abstände sind in Glyph Space (also tausendstel Text Space) angegeben und so zu lesen, dass positive Werte die Textstücke näher zusammenrücken.
 * Die Textstücke und Abstände bilden zusammen ein Array, bestehend aus strings und numbers.
 * @package pdf\graphics\operator
 */
class TextWithSpacesOperator extends AbstractTextOperator
{
    /**
     * Array mit Text, welcher mit diesem TextOperator gezeichnet werden soll, oder Zahlen, die einen negativen(!) Abstand zwischen zwei Textstücken hinzufügen
     * @var PdfArray
     */
    protected $textArray;

    /**
     * TextWithSpacesOperator constructor.
     * @param PdfArray $textArray Array mit Text, welcher mit diesem TextOperator gezeichnet werden soll, oder Zahlen, die einen negativen(!) Abstand zwischen zwei Textstücken hinzufügen
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfArray $textArray, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->textArray = $textArray;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "TJ";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt.
     * Dieser Beinhaltet auch einen EOL-Marker am Ende des Operatoren
     * @return string
     */
    function __toString(): string
    {
        return "{$this->textArray->toString()} TJ\n";
    }

    /**
     * Berechnet die Positionen der einzelnen Glyphen und damit auch den $startPoint und $endPoint.
     * Dafür wird ein GraphicsState benötigt, welcher im Durchlauf der Funktion verändert wird.
     * @param GraphicsState|null $graphicsState GraphicsState, welcher während der Funktion verändert wird. Ist kein GraphicsState angegeben, wird der GraphicsState aus den OperatorMetadata geklont.
     * @return GraphicsState GraphicsState nach der Ausführung des Operatoren
     * @throws \Exception Wenn kein GraphicsState und keine Metadaten im Konstruktor angegeben wurden
     */
    public function calculateText(GraphicsState $graphicsState = null): GraphicsState
    {
        if ($graphicsState === null) {
            $graphicsState = clone $this->getGraphicsState();
        }
        $textObjectState = $graphicsState->cloneTextObjectState();
        $fontSize = $graphicsState->getTextState()->getTextFontSize()->getValue();
        $horizontalScaling = $graphicsState->getTextState()->getHorizontalScaling()->getValue() / 100.0;

        $this->startPos = $textObjectState->getTextRenderingMatrix($graphicsState)->transformPoint(new Point(0, 0));

        $textArraySize = $this->textArray->getArraySize();
        for ($i = 0; $i < $textArraySize; ++$i) {
            $textArrayEntry = $this->textArray->getObject($i);
            if ($textArrayEntry instanceof PdfNumber) {
                $updateMatrix = TransformationMatrix::translation((($textArrayEntry->getValue() / -1000.0) * $fontSize) * $horizontalScaling, 0);
                $textObjectState->setTextMatrix($textObjectState->getTextMatrix()->addTransformation($updateMatrix));
            } else
                $this->calculateString($textArrayEntry->getValue(), $graphicsState);
        }
        $this->endPos = $textObjectState->getTextRenderingMatrix($graphicsState)->transformPoint(new Point(0, 0));

        return $graphicsState;
    }

    /**
     * Der Text, welcher mit diesem Operator gezeichnet wird
     * @return string
     */
    public function getText(): string
    {
        $text = "";
        $textArraySize = $this->textArray->getArraySize();
        for ($i = 0; $i < $textArraySize; ++$i) {
            $textPiece = $this->textArray->getObject($i);
            if (!$textPiece instanceof PdfNumber)
                $text .= $textPiece->getValue();
        }
        return $text;
    }
}