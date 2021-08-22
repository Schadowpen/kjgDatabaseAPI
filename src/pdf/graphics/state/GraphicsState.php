<?php

namespace pdf\graphics\state;

use pdf\document\GraphicsStateParameterDictionary;
use pdf\graphics\Color;
use pdf\graphics\ColorGray;
use pdf\graphics\operator\AbstractOperator;
use pdf\graphics\operator\AbstractTextOperator;
use pdf\graphics\operator\BeginTextObjectOperator;
use pdf\graphics\operator\ClippingPathEvenOddOperator;
use pdf\graphics\operator\ClippingPathNonzeroOperator;
use pdf\graphics\operator\CloseAndStrokePathOperator;
use pdf\graphics\operator\CloseFillAndStrokePathEvenOddOperator;
use pdf\graphics\operator\CloseFillAndStrokePathNonzeroOperator;
use pdf\graphics\operator\ColorFillingOperator;
use pdf\graphics\operator\ColorRenderingIntentOperator;
use pdf\graphics\operator\ColorRGBFillingOperator;
use pdf\graphics\operator\ColorRGBStrokingOperator;
use pdf\graphics\operator\ColorStrokingOperator;
use pdf\graphics\operator\ComplexTextOperator;
use pdf\graphics\operator\EndTextObjectOperator;
use pdf\graphics\operator\ExternalGraphicsStateOperator;
use pdf\graphics\operator\FillAndStrokePathEvenOddOperator;
use pdf\graphics\operator\FillAndStrokePathNonzeroOperator;
use pdf\graphics\operator\FillPathEvenOddOperator;
use pdf\graphics\operator\FillPathNonzeroOperator;
use pdf\graphics\operator\FlatnessOperator;
use pdf\graphics\operator\LineCapOperator;
use pdf\graphics\operator\LineDashPatternOperator;
use pdf\graphics\operator\LineJoinOperator;
use pdf\graphics\operator\LineWidthOperator;
use pdf\graphics\operator\MiterLimitOperator;
use pdf\graphics\operator\ModifyTransformationMatrixOperator;
use pdf\graphics\operator\PathBeginOperator;
use pdf\graphics\operator\PathBezierOperator;
use pdf\graphics\operator\PathCloseOperator;
use pdf\graphics\operator\PathEndingOperator;
use pdf\graphics\operator\PathLineOperator;
use pdf\graphics\operator\PathPaintingOperator;
use pdf\graphics\operator\PathRectangleOperator;
use pdf\graphics\operator\StrokePathOperator;
use pdf\graphics\operator\TextInNewLineOperator;
use pdf\graphics\operator\TextMatrixOperator;
use pdf\graphics\operator\TextNewLineAndLeadingOperator;
use pdf\graphics\operator\TextNewLineOperator;
use pdf\graphics\operator\TextNextLineOperator;
use pdf\graphics\operator\TextOperator;
use pdf\graphics\operator\TextWithSpacesOperator;
use pdf\graphics\TransformationMatrix;
use pdf\object\PdfArray;
use pdf\object\PdfBoolean;
use pdf\object\PdfDictionary;
use pdf\object\PdfName;
use pdf\object\PdfNumber;

/**
 * Der (Device Independent) Graphics State, mit welchem ContentStreams arbeiten
 * @package pdf\graphics
 */
class GraphicsState
{
    /**
     * Transformationsmatrix, um von User Space in Device Space umzurechnen
     * @var TransformationMatrix
     */
    protected $currentTransformationMatrix;

    /**
     * Der aktuelle Clipping Path.
     * Wird nicht korrekt gesetzt, daher auch nicht verwendbar.
     * @var mixed
     */
    protected $clippingPath;
    /**
     * Farbraum, in welchem Farbwerte interpretiert werden sollen.
     * Bei nicht unterstützten Farbräumen wird der Wert auf null gesetzt.
     * @var PdfName|PdfArray|null
     * @see GraphicsState::colorStroking
     */
    protected $colorSpaceStroking;
    /**
     * Farbe, welche für Umrisse verwendet werden sollen.
     * Bei nicht unterstützten Farbräumen wird der Wert auf null gesetzt.
     * @var Color|null
     */
    protected $colorStroking;
    /**
     * Farbraum, in welchem Farbwerte interpretiert werden sollen.
     * Bei nicht unterstützten Farbräumen wird der Wert auf null gesetzt.
     * @var PdfName|PdfArray|null
     * @see GraphicsState::colorFilling
     */
    protected $colorSpaceFilling;
    /**
     * Farbe, welche für Flächen verwendet werden sollen.
     * Bei nicht unterstützten Farbräumen wird der Wert auf null gesetzt.
     * @var Color|null
     */
    protected $colorFilling;
    /**
     * TextState zum schreiben von Text
     * @var TextState
     */
    protected $textState;
    /**
     * Die Breite einer Linie in User Space Units
     * @var PdfNumber
     */
    protected $lineWidth;
    /**
     * Wie eine Linie grafisch abgeschlossen werden soll
     * @var int
     */
    protected $lineCap;
    /**
     * Wie zwei Linien grafisch zusammengefügt werden sollen
     * @var int
     */
    protected $lineJoin;
    /**
     * Maximale Länge der Gehrung, wenn zwei Linien in spitzen Winkel aufeinanderstossen
     * @var PdfNumber
     */
    protected $miterLimit;
    /**
     * Pattern für gestrichelte Linien
     * @var PdfArray
     */
    protected $dashPatternArray;
    /**
     * Offset, bei welchem die gestrichelte Linie anfängt
     * @var PdfNumber
     */
    protected $dashPatternPhase;
    /**
     * Zum Konvertieren von CIE-basierten Farben zu Device Farben
     * @var PdfName
     */
    protected $renderingIntent;
    /**
     * Ob mögliche Rasterisierungseffekte kompensiert werden sollen, wenn die zu zeichnende Linie kleiner als die Pixelgröße ist
     * @var PdfBoolean
     */
    protected $strokeAdjustment;
    /**
     * Genutzt vom Transparent Imaging Model (PDF 1.4)
     * @var PdfName|PdfArray
     */
    protected $blendMode;
    /**
     * Genutzt vom Transparent Imaging Model (PDF 1.4)
     * @var PdfName|PdfDictionary
     */
    protected $softMask;
    /**
     * Genutzt vom Transparent Imaging Model (PDF 1.4)
     * @var PdfNumber
     */
    protected $alphaConstantPainting;
    /**
     * Genutzt vom Transparent Imaging Model (PDF 1.4)
     * @var PdfNumber
     */
    protected $alphaConstantStroking;
    /**
     * Genutzt vom Transparent Imaging Model (PDF 1.4)
     * @var PdfBoolean
     */
    protected $alphaSource;
    /**
     * Zusätzlicher Graphics State zum Zeichnen bestimmter Objekte.
     * Wird er nicht benötigt, ist er Null
     * @var AbstractAdditionalGraphicsState|null
     */
    protected $additionalGraphicsState;

    /**
     * Initialisiert einen GraphicsState mit Standardwerten
     * @param TransformationMatrix $startTransformationMatrix Anfängliche Transformationsmatrix, die Device Space in Default User Space umrechnet
     * @param mixed $clippingPath Anfänglicher Clipping Path für den sichtbaren Bereich
     * @throws \Exception Wo auch immer da eine Exception geworfen wird, whatever
     */
    public function __construct(TransformationMatrix $startTransformationMatrix, $clippingPath)
    {
        $this->currentTransformationMatrix = $startTransformationMatrix;
        $this->clippingPath = $clippingPath;
        $this->colorSpaceStroking = new PdfName("DeviceGray");
        $this->colorStroking = new ColorGray(0);
        $this->colorSpaceFilling = new PdfName("DeviceGray");
        $this->colorFilling = new ColorGray(0);
        $this->textState = new TextState();
        $this->lineWidth = new PdfNumber(1.0);
        $this->lineCap = 0;
        $this->lineJoin = 0;
        $this->miterLimit = new PdfNumber(10.0);
        $this->dashPatternArray = new PdfArray([]);
        $this->dashPatternPhase = new PdfNumber(0);
        $this->renderingIntent = new PdfName("RelativeColorimetric");
        $this->strokeAdjustment = new PdfBoolean(false);
        $this->blendMode = new PdfName("Normal");
        $this->softMask = new PdfName("None");
        $this->alphaConstantPainting = new PdfNumber(1.0);
        $this->alphaConstantStroking = new PdfNumber(1.0);
        $this->alphaSource = new PdfBoolean(false);
        $this->additionalGraphicsState = null;
    }

    /**
     * Reagiert auf einen Operatoren, der den GraphicsStateStack beeinflusst.
     * Gibt den neuen GraphicsState zurück, der jetzige wird nicht beeinflusst.
     * @param AbstractOperator $operator
     * @return GraphicsState
     * @throws \Exception Wenn der Operator definitiv nicht erlaubt ist.
     */
    public function reactToOperator(AbstractOperator $operator): GraphicsState
    {
        $newGraphicsState = clone $this;
        switch (get_class($operator)) {
            case ModifyTransformationMatrixOperator::class:
                $newGraphicsState->currentTransformationMatrix = $this->currentTransformationMatrix->addTransformation($operator->getTransformationMatrix());
                break;

            case LineWidthOperator::class:
                $newGraphicsState->lineWidth = $operator->getLineWidth();
                break;

            case LineCapOperator::class:
                $newGraphicsState->lineCap = $operator->getLineCap();
                break;

            case LineJoinOperator::class:
                $newGraphicsState->lineJoin = $operator->getLineJoin();
                break;

            case MiterLimitOperator::class:
                $newGraphicsState->miterLimit = $operator->getMiterLimit();
                break;

            case LineDashPatternOperator::class:
                $newGraphicsState->dashPatternArray = $operator->getDashArray();
                $newGraphicsState->dashPatternPhase = $operator->getDashPhase();
                break;

            case ColorRenderingIntentOperator::class:
                $newGraphicsState->renderingIntent = $operator->getRenderingIntent();
                break;

            case FlatnessOperator::class;
                // Flatness Operator i ist Device-Abhängig und daher irrelevant
                break;

            case ExternalGraphicsStateOperator::class:
                /** @var GraphicsStateParameterDictionary $extGState */
                $extGState = $operator->getExternalGraphicsState();
                $tmp = $extGState->getLineWidth();
                if ($tmp !== null)
                    $newGraphicsState->lineWidth = $tmp;
                $tmp = $extGState->getLineCapStyle();
                if ($tmp !== null)
                    $newGraphicsState->lineCap = $tmp->getValue();
                $tmp = $extGState->getLineJoinStyle();
                if ($tmp !== null)
                    $newGraphicsState->lineJoin = $tmp->getValue();
                $tmp = $extGState->getMiterLimit();
                if ($tmp !== null)
                    $newGraphicsState->miterLimit = $tmp;
                $tmp = $extGState->getDashPattern();
                if ($tmp !== null) {
                    $newGraphicsState->dashPatternArray = $tmp->getObject(0);
                    $newGraphicsState->dashPatternPhase = $tmp->getObject(1);
                }
                $tmp = $extGState->getRenderingIntent();
                if ($tmp !== null)
                    $newGraphicsState->renderingIntent = $tmp;
                $tmp = $extGState->getAutomaticStrokeAdjustment();
                if ($tmp !== null)
                    $newGraphicsState->strokeAdjustment = $tmp;
                $tmp = $extGState->getBlendMode();
                if ($tmp !== null)
                    $newGraphicsState->blendMode = $tmp;
                $tmp = $extGState->getSoftMask();
                if ($tmp !== null)
                    $newGraphicsState->softMask = $tmp;
                $tmp = $extGState->getStrokingAlphaConstant();
                if ($tmp !== null)
                    $newGraphicsState->alphaConstantStroking = $tmp;
                $tmp = $extGState->getNonstrokingAlphaConstant();
                if ($tmp !== null)
                    $newGraphicsState->alphaConstantPainting = $tmp;
                $tmp = $extGState->getAlphaSource();
                if ($tmp !== null)
                    $newGraphicsState->alphaSource = $tmp;
                $newGraphicsState->textState = $newGraphicsState->textState->reactToExternalGraphicsState($extGState);
                break;

            // AdditionalGraphicsState
            case PathBeginOperator::class:
            case PathRectangleOperator::class:
                if (!$this->additionalGraphicsState instanceof PathConstructionState)
                    $newGraphicsState->additionalGraphicsState = new PathConstructionState();
            case PathLineOperator::class:
            case PathBezierOperator::class:
            case PathCloseOperator::class:
                $newGraphicsState->additionalGraphicsState = $newGraphicsState->getPathConstructionState()->reactToOperator($operator);
                break;

            case BeginTextObjectOperator::class:
                $newGraphicsState->additionalGraphicsState = new TextObjectState();
                break;

            case PathEndingOperator::class:
            case StrokePathOperator::class:
            case CloseAndStrokePathOperator::class:
            case FillPathNonzeroOperator::class:
            case FillPathEvenOddOperator::class:
            case FillAndStrokePathNonzeroOperator::class:
            case FillAndStrokePathEvenOddOperator::class:
            case CloseFillAndStrokePathNonzeroOperator::class:
            case CloseFillAndStrokePathEvenOddOperator::class:
            case EndTextObjectOperator::class:
                $newGraphicsState->additionalGraphicsState = null;
                break;

            // Text Operators
            case TextNewLineAndLeadingOperator::class:
                $newGraphicsState->textState = clone $this->textState;
                $newGraphicsState->textState->setLeading(new PdfNumber(-$operator->getTy()->getValue()));
            case TextNewLineOperator::class:
                $textObjectState = $this->getTextObjectState();
                $newGraphicsState->additionalGraphicsState = clone $textObjectState;
                $newGraphicsState->additionalGraphicsState->setTextMatrixAndTextLineMatrix($textObjectState->getTextLineMatrix()->addTransformation(TransformationMatrix::translation($operator->getTx()->getValue(), $operator->getTy()->getValue())));
                break;
            case TextMatrixOperator::class:
                $newGraphicsState->additionalGraphicsState = clone $this->getTextObjectState();
                $newGraphicsState->additionalGraphicsState->setTextMatrixAndTextLineMatrix($operator->getTextMatrix());
                break;
            case TextNextLineOperator::class:
                $textObjectState = $this->getTextObjectState();
                $newGraphicsState->additionalGraphicsState = clone $textObjectState;
                $newGraphicsState->additionalGraphicsState->setTextMatrixAndTextLineMatrix($textObjectState->getTextLineMatrix()->addTransformation(TransformationMatrix::translation(0, -$this->textState->getLeading()->getValue())));
                break;
            case TextOperator::class:
            case TextInNewLineOperator::class:
            case ComplexTextOperator::class:
            case TextWithSpacesOperator::class:
                $operator->calculateText($newGraphicsState);
                break;

            // Clipping
            case ClippingPathNonzeroOperator::class:
            case ClippingPathEvenOddOperator::class:
                // Keine Aktion, da Clipping nicht benötigt wird.
                return $this;

            // Color
            case ColorStrokingOperator::class:
                $newGraphicsState->colorSpaceStroking = null;
                $newGraphicsState->colorStroking = null;
                break;
            case ColorRGBStrokingOperator::class:
                $newGraphicsState->colorSpaceStroking = new PdfName("DeviceRGB");
                $newGraphicsState->colorStroking = $operator->getColor();
                break;
            case ColorFillingOperator::class:
                $newGraphicsState->colorSpaceFilling = null;
                $newGraphicsState->colorFilling = null;
                break;
            case ColorRGBFillingOperator::class:
                $newGraphicsState->colorSpaceFilling = new PdfName("DeviceRGB");
                $newGraphicsState->colorFilling = $operator->getColor();
                break;

            // Text State Operators
            default:
                $newGraphicsState->textState = $this->textState->reactToOperator($operator);
                break;
        }
        return $newGraphicsState;
    }


    /**
     * @return TransformationMatrix
     */
    public function getCurrentTransformationMatrix(): TransformationMatrix
    {
        return $this->currentTransformationMatrix;
    }

    /**
     * @return PdfArray|PdfName|null
     */
    public function getColorSpaceStroking()
    {
        return $this->colorSpaceStroking;
    }


    /**
     * @return Color|null
     */
    public function getColorStroking(): ?Color
    {
        return $this->colorStroking;
    }

    /**
     * @return PdfArray|PdfName|null
     */
    public function getColorSpaceFilling()
    {
        return $this->colorSpaceFilling;
    }

    /**
     * @return Color|null
     */
    public function getColorFilling(): ?Color
    {
        return $this->colorFilling;
    }

    /**
     * @return TextState
     */
    public function getTextState(): TextState
    {
        return $this->textState;
    }

    /**
     * @return PdfNumber
     */
    public function getLineWidth(): PdfNumber
    {
        return $this->lineWidth;
    }

    /**
     * @return int
     */
    public function getLineCap(): int
    {
        return $this->lineCap;
    }

    /**
     * @return int
     */
    public function getLineJoin(): int
    {
        return $this->lineJoin;
    }

    /**
     * @return PdfNumber
     */
    public function getMiterLimit(): PdfNumber
    {
        return $this->miterLimit;
    }

    /**
     * @return PdfArray
     */
    public function getDashPatternArray(): PdfArray
    {
        return $this->dashPatternArray;
    }

    /**
     * @return PdfNumber
     */
    public function getDashPatternPhase(): PdfNumber
    {
        return $this->dashPatternPhase;
    }

    /**
     * @return PdfName
     */
    public function getRenderingIntent(): PdfName
    {
        return $this->renderingIntent;
    }

    /**
     * @return PdfBoolean
     */
    public function getStrokeAdjustment(): PdfBoolean
    {
        return $this->strokeAdjustment;
    }

    /**
     * @return PdfArray|PdfName
     */
    public function getBlendMode()
    {
        return $this->blendMode;
    }

    /**
     * @return PdfDictionary|PdfName
     */
    public function getSoftMask()
    {
        return $this->softMask;
    }

    /**
     * @return PdfNumber
     */
    public function getAlphaConstantPainting(): PdfNumber
    {
        return $this->alphaConstantPainting;
    }

    /**
     * @return PdfNumber
     */
    public function getAlphaConstantStroking(): PdfNumber
    {
        return $this->alphaConstantStroking;
    }

    /**
     * @return PdfBoolean
     */
    public function getAlphaSource(): PdfBoolean
    {
        return $this->alphaSource;
    }

    /**
     * Liefert den PathConstructionState, mit dem gerade ein Pfad-Objekt gezeichnet wird
     * @return PathConstructionState
     * @throws \Exception Wenn gerade kein Pfad gezeichnet wird
     */
    public function getPathConstructionState(): PathConstructionState
    {
        if ($this->additionalGraphicsState instanceof PathConstructionState)
            return $this->additionalGraphicsState;
        throw new \Exception("There is no Path under construction, so no PathConstructionState is applied");
    }

    /**
     * Liefert den TextObjectState, mit dem gerade ein Text-Objekt gezeichnet wird
     * @return TextObjectState
     * @throws \Exception Wenn gerade kein Text Objekt gezeichnet wird
     */
    public function getTextObjectState(): TextObjectState
    {
        if ($this->additionalGraphicsState instanceof TextObjectState)
            return $this->additionalGraphicsState;
        throw new \Exception("There is no text object under construction, so no TextObjectState is applied");
    }

    /**
     * Kopiert den TextObjectState, mit dem gerade ein Text-Objekt gezeichnet wird, und liefert ihn zurück.
     * Diese Funktion ergibt nur für die calculateText()-Funktionen der Text Rendering Operatoren Sinn, da diese den TextObjectState verändern müssen.
     * @return TextObjectState
     * @throws \Exception Wenn gerade kein Text Objekt gezeichnet wird
     */
    public function cloneTextObjectState(): TextObjectState
    {
        if ($this->additionalGraphicsState instanceof TextObjectState) {
            $this->additionalGraphicsState = clone $this->additionalGraphicsState;
            return $this->additionalGraphicsState;
        }
        throw new \Exception("There is no text object under construction, so no TextObjectState is applied");
    }

    /**
     * Kopiert den TextState und liefert ihn zurück.
     * Diese Funktion ergibt nur für die calculateText()-Funktion im ComplexTextOperator Sinn, da dieser den TextState verändern muss.
     * @return TextState
     */
    public function cloneTextState() : TextState {
        $this->textState = clone $this->textState;
        return $this->textState;
    }
}