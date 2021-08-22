<?php

namespace pdf\document;

use pdf\graphics\operator\ExternalGraphicsStateOperator;
use pdf\object\PdfAbstractObject;
use pdf\object\PdfArray;
use pdf\object\PdfDictionary;
use pdf\object\PdfName;
use pdf\PdfFile;

/**
 * Dictionary mit den in einem Content Stream genutzten Ressourcen.
 * @package pdf\document
 */
class ResourceDictionary extends AbstractDocumentObject
{
    /**
     * Array mit allen externen Objekten in diesem ResourceDictionary
     * @var XObject[]
     */
    protected $externalObjects;
    /**
     * Array mit allen Schriftarten in diesem ResourceDictionary
     * @var Font[]
     */
    protected $fonts;

    public static function objectType(): ?string
    {
        return null;
    }

    public static function objectSubtype(): ?string
    {
        return null;
    }

    /**
     * Erzeugt ein ResourceDictionary
     * @param PdfDictionary $dictionary Dictionary mit den Ressource-Daten
     * @param PdfFile $pdfFile PdfFile, in welchem das ResourceDictionary verwendet wird
     * @throws \Exception Wenn das übergebene Dictionary kein Dictionary ist oder nicht dem richtigen Objekttyp entspricht.
     */
    public function __construct(PdfDictionary $dictionary, PdfFile $pdfFile)
    {
        parent::__construct($dictionary, $pdfFile);

        // Finde Externe Objekte
        /** @var PdfDictionary $xObjectDictionary */
        $xObjectDictionary = $this->get("XObject");
        if ($xObjectDictionary === null) {
            $xObjectDictionary = new PdfDictionary([]);
            $this->dictionary->setObject("XObject", $xObjectDictionary);
        }
        $this->externalObjects = [];
        foreach ($xObjectDictionary->getValue() as $key => $value)
            $this->externalObjects[$key] = XObject::parseReference($value, $pdfFile);

        // Finde Schriftarten
        /** @var PdfDictionary $fontDictionary */
        $fontDictionary = $this->get("Font");
        if ($fontDictionary === null) {
            $fontDictionary = new PdfDictionary([]);
            $this->dictionary->setObject("Font", $fontDictionary);
        }
        $this->fonts = [];
        foreach ($fontDictionary->getValue() as $key => $value)
            $this->fonts[$key] = Font::getFont($value, $pdfFile);
    }

    public function getExtGStateDictionary(): ?PdfDictionary
    {
        return $this->get("ExtGState");
    }

    /**
     * Erzeugt einen neuen Namen für ein GraphicsStateDictionary und fügt den übergebenen externen GraphicsState unter diesem Namen hinzu
     * @param GraphicsStateParameterDictionary $externalGraphicsState das hinzuzufügende GraphicsStateDictionary
     * @return string Name, unter dem der Externe GraphicsState im ResourceDictionary abgespeichert wurde.
     */
    public function addExtGState(GraphicsStateParameterDictionary $externalGraphicsState): string
    {
        /** @var PdfDictionary $extGStateDictionary */
        $extGStateDictionary = $this->get("ExtGState");
        if ($extGStateDictionary === null) {
            $extGStateDictionary = new PdfDictionary([]);
            $this->dictionary->setObject("ExtGState", $extGStateDictionary);
        }
        $i = 0;
        while ($extGStateDictionary->hasObject("GS" . $i))
            ++$i;
        $extGStateName = "GS" . $i;
        $extGStateDictionary->setObject($extGStateName, $externalGraphicsState->getIndirectReference());
        return $extGStateName;
    }

    public function getColorSpaceDictionary(): ?PdfDictionary
    {
        return $this->get("ColorSpace");
    }

    public function getPatternDictionary(): ?PdfDictionary
    {
        return $this->get("Pattern");
    }

    public function getShadingDictionary(): ?PdfDictionary
    {
        return $this->get("Shading");
    }

    /**
     * @param string $xObjectName Name des externen Objektes im Resource Dictionary
     * @return null|XObject das gefundene Externe Objekt
     */
    public function getXObject(string $xObjectName): ?XObject
    {
        return $this->externalObjects[$xObjectName];
    }

    /**
     * @param string $xObjectName Name des externen Objektes im Resource Dictionary
     * @param XObject $xObject Das hinzuzufügende Externe Objekt
     */
    public function setXObject(string $xObjectName, XObject $xObject)
    {
        /** @var PdfDictionary $xObjects */
        $xObjects = $this->get("XObject");
        $xObjects->setObject($xObjectName, $xObject->getIndirectReference());
        $this->externalObjects[$xObjectName] = $xObject;
    }

    /**
     * Erzeugt einen neuen Namen für ein XObjekt und fügt das übergebene XObjekt unter diesem Namen hinzu
     * @param XObject $xObject hinzuzufügendes externes Objekt
     * @return string Name der Font im Resource Dictionary
     */
    public function addXObject(XObject $xObject): string
    {
        // Name je nach Typ
        if ($xObject instanceof XObjectForm)
            $xObjectName = "Form";
        else
            $xObjectName = "Im";

        $i = 0;
        while (isset($this->externalObjects[$xObjectName . $i]))
            ++$i;
        $xObjectName = $xObjectName . $i;
        $this->setXObject($xObjectName, $xObject);
        return $xObjectName;
    }

    /**
     * Entfernt das externe Objekt aus dem Resource Dictionary
     * @param string $xObjectName Name des zu entfernenden externen Objektes
     */
    public function removeXObject(string $xObjectName)
    {
        /** @var PdfDictionary $xObjects */
        $xObjects = $this->get("XObject");
        $xObjects->removeObject($xObjectName);
        unset($this->externalObjects[$xObjectName]);
    }

    /**
     * @param string $fontName Name der Font-Ressource im Resource Dictionary
     * @return Font|null die gefundene Font
     */
    public function getFont(string $fontName): ?Font
    {
        return $this->fonts[$fontName];
    }

    /**
     * @param string $baseName Name der Schriftart, wie sie in dem Font-Objekt angegeben ist
     * @return Font|null die gefundene Font
     */
    public function getFontByBaseName(string $baseName): ?Font
    {
        foreach ($this->fonts as $font) {
            if ($font->getBaseFontName() === $baseName)
                return $font;
        }
        return null;
    }

    /**
     * @param string $baseName Name der Schriftart, wie sie in dem Font-Objekt angegeben ist
     * @return string|null der Name der gefundenen Font in diesem ResourceDictionary
     */
    public function getFontNameByBaseName(string $baseName): ?string
    {
        foreach ($this->fonts as $fontName => $font) {
            if ($font->getBaseFontName() === $baseName)
                return $fontName;
        }
        return null;
    }

    /**
     * Liefert ein assoziatives Array der Form Ressourcenname => Font
     * @return Font[]
     */
    public function getAllFonts(): array {
        return $this->fonts;
    }

    /**
     * @param string $fontName Name der Schriftart im Resource Dictionary
     * @param Font $font Hinzuzufügende Schriftart
     */
    public function setFont(string $fontName, Font $font)
    {
        /** @var PdfDictionary $fontDictionary */
        $fontDictionary = $this->get("Font");
        $fontDictionary->setObject($fontName, $font->getIndirectReference());
        $this->fonts[$fontName] = $font;
    }

    /**
     * Erzeugt einen neuen Namen für eine Schriftart und fügt die übergebene Schriftart unter diesem Namen hinzu
     * @param Font $font Hinzuzufügende Schriftart
     * @return string Name der Font im Resource Dictionary
     */
    public function addFont(Font $font): string
    {
        $i = 0;
        while (isset($this->fonts["F" . $i]))
            ++$i;
        $fontName = "F" . $i;
        $this->setFont($fontName, $font);
        return $fontName;
    }

    public function getProcSetArray(): ?PdfArray
    {
        return $this->get("ProcSet");
    }

    public function addProcSet(PdfName $procedureSet)
    {
        /** @var PdfArray $procSetArray */
        $procSetArray = $this->get("ProcSet");
        if ($procSetArray === null) {
            $procSetArray = new PdfArray([$procedureSet]);
            $this->dictionary->setObject("ProcSet", $procSetArray);
            return;
        }
        foreach ($procSetArray->getValue() as $procSet)
            if ($procedureSet->equals($procSet))
                return; // Bereits definiert
        $procSetArray->addObject($procedureSet);
    }

    public function getPropertiesDictionary(): ?PdfDictionary
    {
        return $this->get("Properties");
    }


    /**
     * Verbindet zwei Resource Dictionaries zu einem Neuen
     * @param ResourceDictionary $dict1
     * @param ResourceDictionary $dict2
     * @return ResourceDictionary
     * @throws \Exception
     */
    public static function merge(ResourceDictionary $dict1, ResourceDictionary $dict2)
    {
        if ($dict1 === $dict2)
            return $dict1->clone();

        $dictionary = new PdfDictionary([]);
        self::mergeSubdictionaries($dict1, $dict2, "ExtGState", $dictionary);
        self::mergeSubdictionaries($dict1, $dict2, "ColorSpace", $dictionary);
        self::mergeSubdictionaries($dict1, $dict2, "Pattern", $dictionary);
        self::mergeSubdictionaries($dict1, $dict2, "Shading", $dictionary);
        self::mergeSubdictionaries($dict1, $dict2, "XObject", $dictionary);
        self::mergeSubdictionaries($dict1, $dict2, "Font", $dictionary);
        self::mergeSubArrays($dict1, $dict2, "ProcSet", $dictionary);
        self::mergeSubdictionaries($dict1, $dict2, "Properties", $dictionary);

        return new ResourceDictionary($dictionary, $dict1->pdfFile);
    }

    /**
     * Verbindet zwei Subdictionaries zu einem neuen Dictionary.
     * Dabei werden null-Werte oder IndirectReferences der Beiden berücksichtigt.
     * @param string $subDictKey Name des Dictionary-Eintrages in dem ResourceDictionary
     * @param ResourceDictionary $dict1
     * @param ResourceDictionary $dict2
     * @param PdfDictionary $targetDictionary
     */
    private static function mergeSubdictionaries(ResourceDictionary $dict1, ResourceDictionary $dict2, string $subDictKey, PdfDictionary &$targetDictionary)
    {
        /** @var PdfDictionary $subDict1 */
        $subDict1 = $dict1->get($subDictKey);
        /** @var PdfDictionary $subDict2 */
        $subDict2 = $dict2->get($subDictKey);

        if ($subDict1 === null) {
            if ($subDict2 !== null)
                $targetDictionary->setObject($subDictKey, $subDict2->clone());
        } else {
            if ($subDict2 === null)
                $targetDictionary->setObject($subDictKey, $subDict1->clone());
            else
                $targetDictionary->setObject($subDictKey, new PdfDictionary(array_merge($subDict2->getValue(), $subDict1->getValue())));
        }
    }

    /**
     * Verbindet zwei Subarrays zu einem neuen Array.
     * Dabei werden null-Werte oder IndirectReferences der Beiden berücksichtigt.
     * @param string $subArrayKey Name des Dictionary-Eintrages in dem ResourceDictionary
     * @param ResourceDictionary $dict1
     * @param ResourceDictionary $dict2
     * @param PdfDictionary $targetDictionary
     */
    private static function mergeSubArrays(ResourceDictionary $dict1, ResourceDictionary $dict2, string $subArrayKey, PdfDictionary &$targetDictionary)
    {
        /** @var PdfArray $subArray1 */
        $subArray1 = $dict1->get($subArrayKey);
        /** @var PdfArray $subArray2 */
        $subArray2 = $dict2->get($subArrayKey);

        if ($subArray1 === null) {
            if ($subArray2 !== null)
                $targetDictionary->setObject($subArrayKey, $subArray2->clone());
        } else {
            if ($subArray2 === null)
                $targetDictionary->setObject($subArrayKey, $subArray1->clone());
            else
                $targetDictionary->setObject($subArrayKey, new PdfArray($subArray1->getValue() + $subArray2->getValue()));
        }
    }

    /**
     * Erzeugt einen tiefen Klon von dem ResourceDictionary
     * @return ResourceDictionary
     * @throws \Exception Kann theoretisch nicht passieren.
     */
    public function clone()
    {
        return new ResourceDictionary($this->dictionary->clone(), $this->pdfFile);
    }
}