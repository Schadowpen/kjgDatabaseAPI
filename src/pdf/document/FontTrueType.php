<?php


namespace pdf\document;

/**
 * Eine Schriftart von TrueType.
 * @package pdf\document
 */
class FontTrueType extends SimpleFont
{
    public static function objectSubtype(): ?string
    {
        return "TrueType";
    }
}