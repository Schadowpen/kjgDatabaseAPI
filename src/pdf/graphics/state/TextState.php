<?php
namespace pdf\graphics\state;

use pdf\document\Font;
use pdf\document\GraphicsStateParameterDictionary;
use pdf\graphics\operator\AbstractOperator;
use pdf\graphics\operator\CharacterSpaceOperator;
use pdf\graphics\operator\TextFontOperator;
use pdf\graphics\operator\TextLeadingOperator;
use pdf\graphics\operator\TextRenderModeOperator;
use pdf\graphics\operator\TextRiseOperator;
use pdf\graphics\operator\TextScaleOperator;
use pdf\graphics\operator\WordSpaceOperator;
use pdf\object\PdfAbstractObject;
use pdf\object\PdfBoolean;
use pdf\object\PdfDictionary;
use pdf\object\PdfIndirectReference;
use pdf\object\PdfNumber;

/**
 * TextState innerhalb eines GraphicsState
 * @package pdf\graphics
 */
class TextState
{
    /**
     * Platz zwischen zwei Charakteren, in Text Space
     * @var PdfNumber
     */
    protected $characterSpacing;
    /**
     * Platz zwischen zwei Wörtern, wird zur Breite des ASCII Space hinzugerechnet, in Text Space
     * @var PdfNumber
     */
    protected $wordSpacing;
    /**
     * Wie breit die Zeichen sein sollen, in Prozent gegenüber normaler Breite
     * @var PdfNumber
     */
    protected $horizontalScaling;
    /**
     * Abstand zwischen zwei Zeilen, in Text Space
     * @var PdfNumber
     */
    protected $leading;
    /**
     * Referenz auf die Schriftart
     * @var Font|null
     */
    protected $textFont;
    /**
     * Schriftgrösse
     * @var PdfNumber
     */
    protected $textFontSize;
    /**
     * Modus, wie der Text gerendert werden soll. <br/>
     * 0: Fill text <br/>
     * 1: Stroke text <br/>
     * 2: Fill and stroke text <br/>
     * 3: invisible <br/>
     * 4: Fill Text and add to path for Clipping <br/>
     * 5: Stroke Text and add to Path for Clipping <br/>
     * 6: Fill and Stroke Text and add to path for Clipping <br/>
     * 7: add to Path for Clipping <br/>
     * @var PdfNumber
     */
    protected $textRenderMode;
    /**
     * Hoch- oder Tiefstellung des Textes
     * @var PdfNumber
     */
    protected $textRise;
    /**
     * Genutzt für Transparent Imaging Model (PDF 1.4)
     * @var PdfBoolean
     */
    protected $textKnockout;

    /**
     * Erzeugt einen neuen TextState mit Standardwerten
     */
    public function __construct()
    {
        $this->characterSpacing = new PdfNumber(0);
        $this->wordSpacing = new PdfNumber(0);
        $this->horizontalScaling = new PdfNumber(100);
        $this->leading = new PdfNumber(0);
        $this->textFont = null;
        $this->textFontSize = null;
        $this->textRenderMode = new PdfNumber(0);
        $this->textRise = new PdfNumber(0);
        $this->textKnockout = new PdfBoolean(true);
    }

    public function reactToOperator(AbstractOperator $operator) : TextState {
        $newTextState = clone $this;
        switch (get_class($operator)) {

            case CharacterSpaceOperator::class:
                $newTextState->characterSpacing = $operator->getCharSpace();
                break;

            case WordSpaceOperator::class:
                $newTextState->wordSpacing = $operator->getWordSpace();
                break;

            case TextScaleOperator::class:
                $newTextState->horizontalScaling = $operator->getScale();
                break;

            case TextLeadingOperator::class:
                $newTextState->leading = $operator->getLeading();
                break;

            case TextFontOperator::class:
                $newTextState->textFont = $operator->getFontResource();
                $newTextState->textFontSize = $operator->getFontSize();
                break;

            case TextRenderModeOperator::class:
                $newTextState->textRenderMode = $operator->getRenderMode();
                break;

            case TextRiseOperator::class:
                $newTextState->textRise = $operator->getTextRise();
                break;
        }
        return $newTextState;
    }

    public function reactToExternalGraphicsState(GraphicsStateParameterDictionary $extGState): TextState {
        $newTextState = clone $this;
        $tmp = $extGState->getFont();
        if ($tmp !== null) {
            $newTextState->textFont = $tmp->getObject(0);
            $newTextState->textFontSize = $tmp->getObject(1);
        }
        $tmp = $extGState->getTextKnockout();
        if ($tmp !== null)
            $newTextState->textKnockout = $tmp;
        return $newTextState;
    }


    /**
     * @return PdfNumber
     */
    public function getCharacterSpacing(): PdfNumber
    {
        return $this->characterSpacing;
    }

    /**
     * @return PdfNumber
     */
    public function getWordSpacing(): PdfNumber
    {
        return $this->wordSpacing;
    }

    /**
     * @return PdfNumber
     */
    public function getHorizontalScaling(): PdfNumber
    {
        return $this->horizontalScaling;
    }

    /**
     * @return PdfNumber
     */
    public function getLeading(): PdfNumber
    {
        return $this->leading;
    }

    /**
     * @return Font|null
     */
    public function getTextFont(): ?Font
    {
        return $this->textFont;
    }

    /**
     * @return PdfNumber
     */
    public function getTextFontSize(): PdfNumber
    {
        return $this->textFontSize;
    }

    /**
     * @return PdfNumber
     */
    public function getTextRenderMode(): PdfNumber
    {
        return $this->textRenderMode;
    }

    /**
     * @return PdfNumber
     */
    public function getTextRise(): PdfNumber
    {
        return $this->textRise;
    }

    /**
     * @return PdfBoolean
     */
    public function getTextKnockout(): PdfBoolean
    {
        return $this->textKnockout;
    }


    /**
     * @param PdfNumber $leading Abstand zwischen zwei Zeilen, in Text Space
     */
    public function setLeading(PdfNumber $leading)
    {
        $this->leading = $leading;
    }

    /**
     * Setzt Word-Spacint und Character-Spacing im TextState.
     * Diese Funktion sollte nur vom ComplexTextOperator genutzt werden.
     * @param PdfNumber $wordSpacing
     * @param PdfNumber $characterSpacing
     */
    public function setWordAndCharacterSpacing(PdfNumber $wordSpacing, PdfNumber $characterSpacing) {
        $this->wordSpacing = $wordSpacing;
        $this->characterSpacing = $characterSpacing;
    }
}