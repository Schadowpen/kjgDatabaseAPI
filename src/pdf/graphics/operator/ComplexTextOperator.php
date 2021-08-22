<?php


namespace pdf\graphics\operator;


use pdf\graphics\Point;
use pdf\graphics\state\GraphicsState;
use pdf\graphics\TransformationMatrix;
use pdf\object\PdfAbstractObject;
use pdf\object\PdfHexString;
use pdf\object\PdfNumber;
use pdf\object\PdfString;

/**
 * Operator zum Zeichnen von Text in einer neuen Zeile.
 * Vorher werden aber noch CharacterSpacing und WordSpacing im TextState gesetzt.
 * @package pdf\graphics\operator
 */
class ComplexTextOperator extends AbstractTextOperator
{
    /**
     * Neu zu setzendes WordSpacing im TextState
     * @var PdfNumber
     */
    protected $wordSpacing;
    /**
     * Neu zu setzendes CharacterSpacing im TextState
     * @var PdfNumber
     */
    protected $characterSpacing;
    /**
     * Text, welcher mit diesem TextOperator gezeichnet werden soll
     * @var PdfString|PdfHexString
     */
    protected $text;

    /**
     * ComplexTextOperator constructor.
     * @param PdfNumber $wordSpacing Neu zu setzendes WordSpacing im TextState
     * @param PdfNumber $characterSpacing Neu zu setzendes CharacterSpacing im TextState
     * @param PdfString|PdfHexString $text Text, welcher mit diesem TextOperator gezeichnet werden soll
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     * @throws \Exception If the text given is no PdfString or PdfHexString
     */
    public function __construct(PdfNumber $wordSpacing, PdfNumber $characterSpacing, PdfAbstractObject $text, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->wordSpacing = $wordSpacing;
        $this->characterSpacing = $characterSpacing;
        if (!($text instanceof PdfString) && !($text instanceof  PdfHexString)) {
            $textClassName = get_class($text);
            throw new \Exception("Argument 1 passed to pdf\graphics\operator\ComplexTextOperator::__construct() must be an instance of pdf\object\PdfString or pdf\object\PdfHexString, instance of {$textClassName} given");
        }
        $this->text = $text;
    }


    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "\"";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt.
     * Dieser Beinhaltet auch einen EOL-Marker am Ende des Operatoren
     * @return string
     */
    function __toString(): string
    {
        return $this->wordSpacing->toString() . " "
            . $this->characterSpacing->toString() . " "
            . $this->text->toString() . " \"\n";
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

        // Vorherige Aktionen
        $textState = $graphicsState->cloneTextState();
        $textState->setWordAndCharacterSpacing($this->wordSpacing, $this->characterSpacing);
        $textObjectState->setTextMatrixAndTextLineMatrix($textObjectState->getTextLineMatrix()->addTransformation(TransformationMatrix::translation(0, -$graphicsState->getTextState()->getLeading()->getValue())));

        $this->startPos = $textObjectState->getTextRenderingMatrix($graphicsState)->transformPoint(new Point(0, 0));
        $this->calculateString($this->text->getValue(), $graphicsState);
        $this->endPos = $textObjectState->getTextRenderingMatrix($graphicsState)->transformPoint(new Point(0, 0));

        return $graphicsState;
    }

    /**
     * Der Text, welcher mit diesem Operator gezeichnet wird
     * @return string
     */
    public function getText(): string
    {
        return $this->text->getValue();
    }
}