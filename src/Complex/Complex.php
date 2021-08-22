<?php

/**
 *
 * Class for the management of Complex numbers
 *
 * @copyright  Copyright (c) 2013-2018 Mark Baker (https://github.com/MarkBaker/PHPComplex)
 * @license    https://www.gnu.org/licenses/lgpl-3.0.html    LGPL 3.0
 */
namespace Complex;

/**
 * Complex Number object.
 *
 * @package Complex
 */
class Complex
{
    /**
     * @constant    Euler's Number.
     */
    const EULER = 2.7182818284590452353602874713526624977572;

    /**
     * @var    float    $realPart    The value of of this complex number on the real plane.
     */
    protected $realPart = 0.0;

    /**
     * @var    float    $imaginaryPart    The value of of this complex number on the imaginary plane.
     */
    protected $imaginaryPart = 0.0;

    /**
     * @var    string    $suffix    The suffix for this complex number (i or j).
     */
    protected $suffix;


    /**
     * Validates whether the argument is a valid complex number, converting scalar or array values if possible
     *
     * @param     mixed    $complexNumber   The value to parse
     * @return    array
     * @throws    Exception    If the argument isn't a Complex number or cannot be converted to one
     */
    private static function parseComplex($complexNumber)
    {
        // Test for real number, with no imaginary part
        if (is_numeric($complexNumber)) {
            return [$complexNumber, 0, null];
        }

        // Fix silly human errors
        $complexNumber = str_replace(
            ['+-', '-+', '++', '--'],
            ['-', '-', '+', '+'],
            $complexNumber
        );

        // Basic validation of string, to parse out real and imaginary parts, and any suffix
        $validComplex = preg_match(
            '/^([\-\+]?(\d+\.?\d*|\d*\.?\d+)([Ee][\-\+]?[0-2]?\d{1,3})?)([\-\+]?(\d+\.?\d*|\d*\.?\d+)([Ee][\-\+]?[0-2]?\d{1,3})?)?(([\-\+]?)([ij]?))$/ui',
            $complexNumber,
            $complexParts
        );

        if (!$validComplex) {
            // Neither real nor imaginary part, so test to see if we actually have a suffix
            $validComplex = preg_match('/^([\-\+]?)([ij])$/ui', $complexNumber, $complexParts);
            if (!$validComplex) {
                throw new Exception('Invalid complex number');
            }
            // We have a suffix, so set the real to 0, the imaginary to either 1 or -1 (as defined by the sign)
            $imaginary = 1;
            if ($complexParts[1] === '-') {
                $imaginary = 0 - $imaginary;
            }
            return [0, $imaginary, $complexParts[2]];
        }

        // If we don't have an imaginary part, identify whether it should be +1 or -1...
        if (($complexParts[4] === '') && ($complexParts[9] !== '')) {
            if ($complexParts[7] !== $complexParts[9]) {
                $complexParts[4] = 1;
                if ($complexParts[8] === '-') {
                    $complexParts[4] = -1;
                }
            } else {
                // ... or if we have only the real and no imaginary part
                //  (in which case our real should be the imaginary)
                $complexParts[4] = $complexParts[1];
                $complexParts[1] = 0;
            }
        }

        // Return real and imaginary parts and suffix as an array, and set a default suffix if user input lazily
        return [
            $complexParts[1],
            $complexParts[4],
            !empty($complexParts[9]) ? $complexParts[9] : 'i'
        ];
    }


    public function __construct($realPart = 0.0, $imaginaryPart = null, $suffix = 'i')
    {
        if ($imaginaryPart === null) {
            if (is_array($realPart)) {
                // We have an array of (potentially) real and imaginary parts, and any suffix
                list ($realPart, $imaginaryPart, $suffix) = array_values($realPart) + [0.0, 0.0, 'i'];
                if ($suffix === null) {
                    $suffix = 'i';
                }
            } elseif ((is_string($realPart)) || (is_numeric($realPart))) {
                // We've been given a string to parse to extract the real and imaginary parts, and any suffix
                list($realPart, $imaginaryPart, $suffix) = self::parseComplex($realPart);
            }
        }

        // Set parsed values in our properties
        $this->realPart = (float) $realPart;
        $this->imaginaryPart = (float) $imaginaryPart;
        $this->suffix = strtolower($suffix);
    }

    /**
     * Gets the real part of this complex number
     *
     * @return Float
     */
    public function getReal()
    {
        return $this->realPart;
    }

    /**
     * Gets the imaginary part of this complex number
     *
     * @return Float
     */
    public function getImaginary()
    {
        return $this->imaginaryPart;
    }

    /**
     * Gets the suffix of this complex number
     *
     * @return String
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    public function format()
    {
        $str = "";
        if ($this->imaginaryPart != 0.0) {
            if (\abs($this->imaginaryPart) != 1.0) {
                $str .= $this->imaginaryPart . $this->suffix;
            } else {
                $str .= (($this->imaginaryPart < 0.0) ? '-' : '') . $this->suffix;
            }
        }
        if ($this->realPart != 0.0) {
            if (($str) && ($this->imaginaryPart > 0.0)) {
                $str = "+" . $str;
            }
            $str = $this->realPart . $str;
        }
        if (!$str) {
            $str = "0.0";
        }

        return $str;
    }

    public function __toString()
    {
        return $this->format();
    }

    /**
     * Validates whether the argument is a valid complex number, converting scalar or array values if possible
     *
     * @param     mixed    $complex   The value to validate
     * @return    Complex
     * @throws    Exception    If the argument isn't a Complex number or cannot be converted to one
     */
    public static function validateComplexArgument($complex)
    {
        if (is_scalar($complex) || is_array($complex)) {
            $complex = new Complex($complex);
        } elseif (!is_object($complex) || !($complex instanceof Complex)) {
            throw new Exception('Value is not a valid complex number');
        }

        return $complex;
    }

    /**
     * Returns the reverse of this complex number
     *
     * @return    Complex
     */
    public function reverse()
    {
        return new Complex(
            $this->imaginaryPart,
            $this->realPart,
            ($this->realPart == 0.0) ? null : $this->suffix
        );
    }

    public function invertImaginary()
    {
        return new Complex(
            $this->realPart,
            $this->imaginaryPart * -1,
            ($this->imaginaryPart == 0.0) ? null : $this->suffix
        );
    }

    public function invertReal()
    {
        return new Complex(
            $this->realPart * -1,
            $this->imaginaryPart,
            ($this->imaginaryPart == 0.0) ? null : $this->suffix
        );
    }

    protected static $functions = [
        'abs',
        'acos',
        'acosh',
        'acot',
        'acoth',
        'acsc',
        'acsch',
        'argument',
        'asec',
        'asech',
        'asin',
        'asinh',
        'atan',
        'atanh',
        'conjugate',
        'cos',
        'cosh',
        'cot',
        'coth',
        'csc',
        'csch',
        'exp',
        'inverse',
        'ln',
        'log2',
        'log10',
        'negative',
        'rho',
        'sec',
        'sech',
        'sin',
        'sinh',
        'sqrt',
        'tan',
        'tanh',
        'theta',
    ];

    protected static $operations = [
        'add',
        'subtract',
        'multiply',
        'divideby',
        'divideinto',
    ];

    /**
     * Returns the result of the function call or operation
     *
     * @return    Complex|float
     * @throws    Exception|\InvalidArgumentException
     */
    public function __call($functionName, $arguments)
    {
        $functionName = strtolower(str_replace('_', '', $functionName));

        // Test for function calls
        if (in_array($functionName, self::$functions)) {
            $functionName = "\\" . __NAMESPACE__ . "\\{$functionName}";
            return $functionName($this);
        }
        // Test for operation calls
        if (in_array($functionName, self::$operations)) {
            $functionName = "\\" . __NAMESPACE__ . "\\{$functionName}";
            return $functionName($this, ...$arguments);
        }
    }
}