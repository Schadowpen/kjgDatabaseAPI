<?php


namespace pdf\indirectObject;

use pdf\object\PdfDictionary;

/**
 * Diese Klasse implementiert das kodieren und dekodieren des FlateDecode-Filters in Streams
 * @package pdf\indirectObject
 */
class FlateDecode
{
    /**
     * Komprimiert einen Streaminhalt mit dem FlateDecode Filter
     * @param string $uncompressed Originaler Stream Inhalt
     * @param PdfDictionary|null $decodeParms Decode-Parameter, sofern verfügbar
     * @return string Komprimierten Stream Inhalt
     * @throws \Exception Wenn die Decode-Parameter nicht unterstützt werden.
     */
    public static function compress(string $uncompressed, ?PdfDictionary $decodeParms = null): string
    {
        if ($decodeParms === null)
            return gzcompress($uncompressed);

        $predictor = 1;
        if ($decodeParms->hasObject("Predictor"))
            $predictor = $decodeParms->getObject("Predictor")->getValue();

        if ($predictor === 1) {
            return gzcompress($uncompressed);

            // PNG Prediktoren
        } else if ($predictor >= 10 && $predictor <= 15) {
            $columns = $decodeParms->getObject("Columns")->getValue();
            $uncompressedLength = strlen($uncompressed);
            if ($uncompressedLength % $columns !== 0)
                throw new \Exception("FlateDecode Error: Length of Stream to compress is not multiple of Columns");
            // Initialisiere erste Reihe als 0-en
            $previousRowData = array_fill(0, $columns, 0);

            // gehe allen Reihen durch
            $predicted = "";
            for ($rowStart = 0; $rowStart < $uncompressedLength; $rowStart += $columns) {
                // Kopiere kodierte Reihendaten
                $rowData = [];
                for ($column = 0; $column < $columns; ++$column) {
                    array_push($rowData, ord($uncompressed[$rowStart + $column]));
                }
                // Berechne Reihendaten anhand Predictor-Funktion
                $usedPredictor = 0;
                $predictedRowData = [];
                switch ($predictor) {
                    case 10:
                        $usedPredictor = 0;
                        $predictedRowData = $rowData;
                        break;
                    case 11:
                        $usedPredictor = 1;
                        $predictedRowData = self::pngPredictSub($rowData, $previousRowData);
                        break;
                    case 12:
                        $usedPredictor = 2;
                        $predictedRowData = self::pngPredictUp($rowData, $previousRowData);
                        break;
                    case 13:
                        $usedPredictor = 3;
                        $predictedRowData = self::pngPredictAverage($rowData, $previousRowData);
                        break;
                    case 14:
                        $usedPredictor = 4;
                        $predictedRowData = self::pngPredictPaeth($rowData, $previousRowData);
                        break;
                    case 15:
                        // PNG None
                        $predictedRowData = self::pngPredictNone($rowData, $previousRowData);
                        $predictedDistance = self::calcPngPredictDistance($predictedRowData);
                        $usedPredictor = 0;
                        // PNG Sub
                        $betterRowData = self::pngPredictSub($rowData, $previousRowData);
                        $betterDistance = self::calcPngPredictDistance($betterRowData);
                        if ($betterDistance < $predictedDistance) {
                            $predictedRowData = $betterRowData;
                            $predictedDistance = $betterDistance;
                            $usedPredictor = 1;
                        }
                        // PNG Up
                        $betterRowData = self::pngPredictUp($rowData, $previousRowData);
                        $betterDistance = self::calcPngPredictDistance($betterRowData);
                        if ($betterDistance < $predictedDistance) {
                            $predictedRowData = $betterRowData;
                            $predictedDistance = $betterDistance;
                            $usedPredictor = 2;
                        }
                        // PNG Average
                        $betterRowData = self::pngPredictAverage($rowData, $previousRowData);
                        $betterDistance = self::calcPngPredictDistance($betterRowData);
                        if ($betterDistance < $predictedDistance) {
                            $predictedRowData = $betterRowData;
                            $predictedDistance = $betterDistance;
                            $usedPredictor = 3;
                        }
                        // PNG Paeth
                        $betterRowData = self::pngPredictPaeth($rowData, $previousRowData);
                        $betterDistance = self::calcPngPredictDistance($betterRowData);
                        if ($betterDistance < $predictedDistance) {
                            $predictedRowData = $betterRowData;
                            $usedPredictor = 4;
                        }
                        break;
                    default:
                        throw new \Exception("Unsupported PNG Predictor Algorithm for FlateDecode Filter");
                }
                // Speichere Reihendaten zu unkomprimierten String
                $predicted .= chr($usedPredictor);
                for ($column = 0; $column < $columns; ++$column)
                    $predicted .= chr($predictedRowData[$column]);

                // Vorbereiten für nächste Reihe
                $previousRowData = $rowData;
            }

            // Komprimierung
            return gzcompress($predicted);

        } else
            throw new \Exception("FlateDecode with DecodeParms not supported yet");
    }

    private static function pngPredictNone(array $rowData, array $previousRowData): array {
        return $rowData;
    }
    private static function pngPredictSub(array $rowData, array $previousRowData): array {
        $columns = count($rowData);
        $predictedRowData = [$rowData[0]];
        for ($column = 1; $column < $columns; ++$column)
            $predictedRowData[$column] = self::mod256($rowData[$column] - $rowData[$column - 1]);
        return $predictedRowData;
    }
    private static function pngPredictUp(array $rowData, array $previousRowData): array {
        $columns = count($rowData);
        $predictedRowData = [];
        for ($column = 0; $column < $columns; ++$column)
            $predictedRowData[$column] = self::mod256($rowData[$column] - $previousRowData[$column]);
        return $predictedRowData;
    }
    private static function pngPredictAverage(array $rowData, array $previousRowData): array {
        $columns = count($rowData);
        $predictedRowData = [self::mod256($rowData[0] - ($previousRowData[0] >> 1))];
        for ($column = 1; $column < $columns; ++$column)
            $predictedRowData[$column] = self::mod256($rowData[$column] - (int)(($previousRowData[$column] + $rowData[$column - 1]) / 2));
        return $predictedRowData;
    }
    private static function pngPredictPaeth(array $rowData, array $previousRowData): array {
        $columns = count($rowData);
        $predictedRowData = [self::mod256($rowData[0] - $previousRowData[0])];
        for ($column = 1; $column < $columns; ++$column)
            $predictedRowData[$column] = self::mod256($rowData[$column] - self::paethPredictor($rowData[$column - 1], $previousRowData[$column], $previousRowData[$column - 1]));
        return $predictedRowData;
    }
    private static function calcPngPredictDistance(array $rowData): int {
        $predictDistance = 0;
        foreach ($rowData as $byte)
            $predictDistance += $byte > 128 ? 256 - $byte : $byte;
        return $predictDistance;
    }

    private static function mod256(int $int): int {
        while ($int < 0)
            $int += 256;
        return $int % 256;
    }

    /**
     * Dekomprimiert einen Streaminhalt mit dem FlateDecode Filter
     * @param string $compressed Komprimierter Stream Inhalt
     * @param PdfDictionary|null $decodeParms Decode-Parameter, sofern verfügbar
     * @return string Unkomprimierter Stream Inhalt
     * @throws \Exception Wenn die Decode-Parameter nicht unterstützt werden.
     */
    public static function decompress(string $compressed, ?PdfDictionary $decodeParms = null): string
    {
        $string = gzuncompress($compressed);
        if ($decodeParms === null)
            return $string;

        $predictor = 1;
        if ($decodeParms->hasObject("Predictor"))
            $predictor = $decodeParms->getObject("Predictor")->getValue();

        switch ($predictor) {
            case 1:
                return $string;
            case 10:
            case 11:
            case 12:
            case 13:
            case 14: // PNG Prediktoren
            case 15:
                $columns = $decodeParms->getObject("Columns")->getValue();
                $rowLength = $columns + 1; // Zahl der Spalten + ein Byte für Predictor
                $stringLength = strlen($string);
                if ($stringLength % $rowLength !== 0)
                    throw new \Exception("FlateDecode Error: uncompressed Stream Length is not multiple of rowLength");
                // Initialisiere erste Reihe als 0-en
                $previousRowData = array_fill(0, $columns, 0);

                // gehe allen Reihen durch
                $uncompressed = "";
                for ($rowStart = 0; $rowStart < $stringLength; $rowStart += $rowLength) {
                    // Kopiere kodierte Reihendaten
                    $rowData = [];
                    for ($column = 0; $column < $columns; ++$column) {
                        array_push($rowData, ord($string[$rowStart + 1 + $column]));
                    }
                    // Berechne Reihendaten anhand Predictor-Funktion
                    $predictor = ord($string[$rowStart]);
                    switch ($predictor) {
                        case 0:
                            // No Predictor Function
                            break;
                        case 1:
                            for ($column = 1; $column < $columns; ++$column)
                                $rowData[$column] = ($rowData[$column] + $rowData[$column - 1]) % 256;
                            break;
                        case 2:
                            for ($column = 0; $column < $columns; ++$column)
                                $rowData[$column] = ($rowData[$column] + $previousRowData[$column]) % 256;
                            break;
                        case 3:
                            $rowData[0] = ($rowData[0] + ($previousRowData[0] >> 1)) % 256;
                            for ($column = 1; $column < $columns; ++$column)
                                $rowData[$column] = ($rowData[$column] + (int)(($previousRowData[$column] + $rowData[$column - 1]) / 2)) % 256;
                            break;
                        case 4:
                            $rowData[0] = ($rowData[0] + $previousRowData[0]) % 256;
                            for ($column = 1; $column < $columns; ++$column)
                                $rowData[$column] = ($rowData[$column] + self::paethPredictor($rowData[$column - 1], $previousRowData[$column], $previousRowData[$column - 1])) % 256;
                            break;
                        default:
                            throw new \Exception("Unsupported PNG Predictor Algorithm for FlateDecode Filter");
                    }
                    // Speichere Reihendaten zu unkomprimierten String
                    for ($column = 0; $column < $columns; ++$column)
                        $uncompressed .= chr($rowData[$column]);

                    // vorbereiten für nächste Reihe
                    $previousRowData = $rowData;
                }
                return $uncompressed;

                break;
            default:
                throw new \Exception("FlateDecode with Predictor {$predictor} not supported yet");
        }

    }

    /**
     * Berechnet die PaethPredictor-Funktion für PNG Prediction
     * @param int $left Byte links des aktuellen Bytes
     * @param int $above Byte oberhalb des aktuellen Bytes
     * @param int $aboveLeft Byte oben links des aktuellen Bytes
     * @return int Einen der übergebenen Werte
     */
    private static function paethPredictor(int $left, int $above, int $aboveLeft): int
    {
        $p = $left + $above - $aboveLeft;
        $pa = abs($p - $left);
        $pb = abs($p - $above);
        $pc = abs($p - $aboveLeft);
        if ($pa <= $pb && $pa <= $pc)
            return $left;
        else if ($pb <= $pc)
            return $above;
        else
            return $aboveLeft;
    }
}