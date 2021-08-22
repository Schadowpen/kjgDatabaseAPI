<?php


namespace pdf\graphics\operator;


use pdf\graphics\Point;
use pdf\graphics\state\GraphicsState;
use pdf\graphics\TransformationMatrix;

/**
 * Abstrakte Superklasse für alle Text-Operatoren, die in einem Content Stream vorkommen können.
 * sie enthält Funktionen, welche für alle TextOperatoren gleich sind.
 * @package pdf\graphics\operator
 */
abstract class AbstractTextOperator extends AbstractOperator
{
    /**
     * Startpunkt des Textes.
     * @var Point|null
     * @see AbstractTextOperator::calculateText() Wird während dieser Funktion erst gesetzt
     */
    protected $startPos = null;
    /**
     * Endpunkt des Textes
     * @var Point|null
     * @see AbstractTextOperator::calculateText() Wird während dieser Funktion erst gesetzt
     */
    protected $endPos = null;

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    public function isRenderingOperator(): bool
    {
        $renderMode = $this->getGraphicsState()->getTextState()->getTextRenderMode()->getValue();
        return $renderMode !== TextRenderModeOperator::invisibleText
            && $renderMode !== TextRenderModeOperator::addTextToPathForClipping;
    }

    /**
     * Der Name der Schriftart, mit der der Text gezeichnet wird.
     * @return string
     * @throws \Exception Wenn keine Metadaten im Operatoren Konstruktor angegeben wurden
     */
    public function getFont(): string
    {
        return $this->getGraphicsState()->getTextState()->getTextFont()->getBaseFontName();
    }

    /**
     * Die Schriftgröße, mit welcher der Text gezeichnet wird.
     * Diese ist in Device Space angegeben, kann sich also von der Font Size im TextState unterscheiden, sollten Transformationsmatrix oder TextMatrix skalieren.
     * @return float
     * @throws \Exception Wenn keine Metadaten im Operatoren Konstruktor angegeben wurden
     */
    public function getFontSize(): float
    {
        $graphicsState = $this->getGraphicsState();
        $textRenderingMatrix = $graphicsState->getTextObjectState()->getTextRenderingMatrix($graphicsState);

        // In der TextRenderingMatrix ist bereits die FontSize eingearbeitet, Text ist nun also exakt 1 hoch.
        $basePoint = $textRenderingMatrix->transformPoint(new Point(0, 0));
        $upPoint = $textRenderingMatrix->transformPoint(new Point(0, 1));
        return $basePoint->distanceTo($upPoint);
    }

    /**
     * Der Startpunkt des Textes im Device Space
     * @return Point
     * @throws \Exception Wenn keine Metadaten im Operatoren Konstruktor angegeben wurden
     */
    public function getStartPos(): Point
    {
        if ($this->startPos === null)
            $this->calculateText();
        return $this->startPos;
    }

    /**
     * Der Endpunkt des Textes im Device Space
     * @return Point
     * @throws \Exception Wenn keine Metadaten im Operatoren Konstruktor angegeben wurden
     */
    public function getEndPos(): Point
    {
        if ($this->endPos === null)
            $this->calculateText();
        return $this->endPos;
    }

    /**
     * Berechnet die Positionen der einzelnen Glyphen und damit auch den $startPoint und $endPoint.
     * Dafür wird ein GraphicsState benötigt, welcher im Durchlauf der Funktion verändert wird.
     * @param GraphicsState|null $graphicsState GraphicsState, welcher während der Funktion verändert wird. Ist kein GraphicsState angegeben, wird der GraphicsState aus den OperatorMetadata geklont.
     * @return GraphicsState GraphicsState nach der Ausführung des Operatoren
     * @throws \Exception Wenn kein GraphicsState und keine Metadaten im Konstruktor angegeben wurden
     */
    public abstract function calculateText(GraphicsState $graphicsState = null): GraphicsState;

    /**
     * Berechnet die Textmatrix für das Zeichnen des Strings.
     * @param string $string Text oder Teiltext, welcher verändert wird.
     * @param GraphicsState $graphicsState GraphicsState, welcher während der Berechnung des Strings verändert wird.
     * @throws \Exception Wenn gerade kein TextObject gezeichnet wird
     */
    protected function calculateString(string $string, GraphicsState &$graphicsState)
    {
        $textObjectState = $graphicsState->getTextObjectState();
        $textState = $graphicsState->getTextState();
        $font = $textState->getTextFont();
        $fontSize = $textState->getTextFontSize()->getValue();
        $characterSpacing = $textState->getCharacterSpacing()->getValue();
        $wordSpacing = $textState->getWordSpacing()->getValue();
        $horizontalScaling = $textState->getHorizontalScaling()->getValue() / 100.0;

        $stringLength = strlen($string);
        for ($i = 0; $i < $stringLength; ++$i) {
            $char = $string[$i];
            $charCode = ord($char);
            $width = $font->getCharWidth($charCode);
            $currentWordSpacing = ($char === " " ? $wordSpacing : 0);
            $updateMatrix = TransformationMatrix::translation((($width / 1000.0) * $fontSize + $characterSpacing + $currentWordSpacing) * $horizontalScaling, 0);
            $textObjectState->setTextMatrix($textObjectState->getTextMatrix()->addTransformation($updateMatrix));
        }
    }

    /**
     * Der Text, welcher mit diesem Operator gezeichnet wird, in UTF-8 Codierung.
     * Wird die Kodierung nicht unterstützt, wird das Ergebnis von getText() zurückgegeben.
     * Somit wird auf jeden Fall ein String zurückgegeben.
     * @return string
     */
    public function getTextUTF8(): string
    {
        $text = $this->getText();
        try {
            $font = $this->getGraphicsState()->getTextState()->getTextFont();
            return $font->toUTF8($text);
        } catch (\Exception $exception) {
            return $text;
        }
    }
    /**
     * Der Text, welcher mit diesem Operator gezeichnet wird
     * @return string
     */
    abstract public function getText(): string;
}