<?php


namespace pdf\document;


use pdf\object\PdfAbstractObject;
use pdf\object\PdfArray;
use pdf\object\PdfBoolean;
use pdf\object\PdfName;
use pdf\object\PdfNumber;

class GraphicsStateParameterDictionary extends AbstractDocumentObject
{

    /**
     * Liefert den Type dieser Klasse an Dokument Objekten.
     * Er sollte in einem zugehörigen Dictionary immer unter Type enthalten sein.
     * <br/>
     * Sollte für die Objektklasse kein Type-Attribut benötigt werden, wird null zurückgeliefert.
     * @return string|null
     */
    public static function objectType(): ?string
    {
        return "ExtGState";
    }

    /**
     * Liefert den Subtype dieser Klasse an Dokument Objekten.
     * Sollte für die Dokumentklasse kein SubType benötigt werden, wird null zurückgeliefert.
     * @return string|null
     */
    public static function objectSubtype(): ?string
    {
        return null;
    }

    public function getLineWidth() : ?PdfNumber {
        return $this->get("LW");
    }
    public function getLineCapStyle() : ?PdfNumber {
        return $this->get("LC");
    }
    public function getLineJoinStyle() : ?PdfNumber {
        return $this->get("LJ");
    }
    public function getMiterLimit() : ?PdfNumber {
        return $this->get("ML");
    }
    /**
     * Dash Pattern ist ein Array bestehend aus einem Array mit dem Pattern und einer Nummer mit dem Offset
     * @return PdfArray|null
     */
    public function getDashPattern() : ?PdfArray {
        return $this->get("D");
    }
    public function getRenderingIntent() : ?PdfName {
        return $this->get("RI");
    }
    public function getOverprintForPainting() : ?PdfBoolean {
        $value = $this->get("op");
        if ($value === null)
            return $this->get("OP");
        return $value;
    }
    public function getOverprintForStroking() : ?PdfBoolean {
        return $this->get("OP");
    }
    public function getOverprintMode() : ?PdfNumber {
        return $this->get("OPM");
    }
    /**
     * Font ist ein Array bestehend aus einer Indirect Reference auf ein Font Objekt und einer Nummer mit der Font Size
     * @return PdfArray|null
     */
    public function getFont() : ?PdfArray {
        return $this->get("Font");
    }
    public function getBlackGenerationFunction() : ?PdfAbstractObject {
        $value = $this->get("BG2");
        if ($value === null)
            return $this->get("BG");
        return $value;
    }
    public function getUndercolorRemovalFunction() : ?PdfAbstractObject {
        $value = $this->get("UCR2");
        if ($value === null)
            return $this->get("UCR");
        return $value;
    }
    public function getColorTransferFunction() : ?PdfAbstractObject {
        $value = $this->get("TR2");
        if ($value === null)
            return $this->get("TR");
        return $value;
    }
    public function getHalftone() : ?PdfAbstractObject {
        return $this->get("HT");
    }
    public function getFlatnessTolerance(): ?PdfNumber {
        return $this->get("FL");
    }
    public function getSmoothnessTolerance(): ?PdfNumber {
        return $this->get("SM");
    }
    public function getAutomaticStrokeAdjustment(): ?PdfBoolean {
        return $this->get("SA");
    }
    /**
     * @return PdfName|PdfArray|null
     */
    public function getBlendMode(): ?PdfAbstractObject {
        return $this->get("BM");
    }
    public function getSoftMask(): ?PdfAbstractObject {
        return $this->get("SMask");
    }
    public function getStrokingAlphaConstant(): ?PdfNumber {
        return $this->get("CA");
    }
    public function getNonstrokingAlphaConstant(): ?PdfNumber {
        return $this->get("ca");
    }
    public function getAlphaSource(): ?PdfBoolean {
        return $this->get("AIS");
    }
    public function getTextKnockout() : ?PdfBoolean {
        return $this->get("TK");
    }


    public function setLineWidth(PdfNumber $lineWidth) {
        $this->dictionary->setObject("LW", $lineWidth);
    }
    public function setLineCapStyle(PdfNumber $lineCapStyle) {
        $this->dictionary->setObject("LC", $lineCapStyle);
    }
    public function setLineJoinStyle(PdfNumber $lineJoinStyle) {
        $this->dictionary->setObject("LJ", $lineJoinStyle);
    }
    public function setMiterLimit(PdfNumber $miterLimit) {
        $this->dictionary->setObject("ML", $miterLimit);
    }
    public function setDashPattern(PdfArray $dashArray, PdfNumber $dashPhase) {
        $this->dictionary->setObject("D", new PdfArray([
            $dashArray,
            $dashPhase
        ]));
    }
    public function setRenderingIntent(PdfName $renderingIntent) {
        $this->dictionary->setObject("RI", $renderingIntent);
    }
    public function setOverprintForPainting(PdfBoolean $overprint) {
        $this->dictionary->setObject("op", $overprint);
    }
    public function setOverprintForStroking(PdfBoolean $overprint) {
        $this->dictionary->setObject("OP", $overprint);
    }
    public function setOverprintMode(PdfNumber $overprintMode) {
        $this->dictionary->setObject("OPM", $overprintMode);
    }
    public function setFont(Font $font, PdfNumber $fontSize) {
        $this->dictionary->setObject("Font", new PdfArray([
            $font->getIndirectReference(),
            $fontSize
        ]));
    }
    public function setBlackGenerationFunction(PdfAbstractObject $blackGenerationFunction) {
        $this->dictionary->setObject("BG2", $blackGenerationFunction);
    }
    public function setUndercolorRemovalFunction(PdfAbstractObject $undercolorRemovalFunction) {
        $this->dictionary->setObject("UCR2", $undercolorRemovalFunction);
    }
    public function setColorTransferFunction(PdfAbstractObject $colorTransferFunction) {
        $this->dictionary->setObject("TR2", $colorTransferFunction);
    }
    public function setHalftone(PdfAbstractObject $halftone) {
        $this->dictionary->setObject("HT", $halftone);
    }
    public function setFlatnessTolerance(PdfNumber $flatnessTolerance) {
        $this->dictionary->setObject("FL", $flatnessTolerance);
    }
    public function setSmoothnessTolerance(PdfNumber $smoothnessTolerance) {
        $this->dictionary->setObject("SM", $smoothnessTolerance);
    }
    public function setAutomaticStrokeAdjustmen(PdfBoolean $strokeAdjustment) {
        $this->dictionary->setObject("SA", $strokeAdjustment);
    }
    /**
     * @param PdfName|PdfArray $blendMode
     */
    public function setBlendMode(PdfAbstractObject $blendMode) {
        $this->dictionary->setObject("BM", $blendMode);
    }
    public function setSoftMask(PdfAbstractObject $softMask) {
        $this->dictionary->setObject("SMask", $softMask);
    }
    public function setStrokingAlphaConstant(PdfNumber $strokingAlphaConstant) {
        $this->dictionary->setObject("CA", $strokingAlphaConstant);
    }
    public function setNonstrokingAlphaConstant(PdfNumber $nonstrokingAlphaConstant) {
        $this->dictionary->setObject("ca", $nonstrokingAlphaConstant);
    }
    public function setAlphaSource(PdfBoolean $alphaSource) {
        $this->dictionary->setObject("AIS", $alphaSource);
    }
    public function setTextKnockout(PdfBoolean $textKnockout) {
        $this->dictionary->setObject("TK", $textKnockout);
    }
}