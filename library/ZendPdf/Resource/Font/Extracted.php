<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Pdf
 */

namespace ZendPdf\Resource\Font;

use ZendPdf as Pdf;
use ZendPdf\Exception;

/**
 * Extracted fonts implementation
 *
 * Thes class allows to extract fonts already mentioned within PDF document and use them
 * for text drawing.
 *
 * @package    Zend_PDF
 * @subpackage Zend_PDF_Fonts
 */
class Extracted extends AbstractFont
{
    /**
     * Messages
     */
    const TYPE_NOT_SUPPORTED = 'Unsupported font type.';
    const ENCODING_NOT_SUPPORTED  = 'Font encoding is not supported';
    const OPERATION_NOT_SUPPORTED = 'Operation is not supported for extracted fonts';

    /**
     * Extracted font encoding
     *
     * Only 'Identity-H' and 'WinAnsiEncoding' encodings are supported now
     *
     * @var string
     */
    protected $_encoding = null;

	/**
	 * Unicode Conversion Array
	 *
	 * @var array
	 */
	protected $_toUnicode = null;

    /**
     * Object constructor
     *
     * $fontDictionary is a \ZendPdf\InternalType\IndirectObjectReference or
     * \ZendPdf\InternalType\IndirectObject object
     *
     * @param mixed $fontDictionary
     * @throws \ZendPdf\Exception\ExceptionInterface
     */
    public function __construct($fontDictionary)
    {
        // Extract object factory and resource object from font dirctionary object
        $this->_objectFactory = $fontDictionary->getFactory();
        $this->_resource      = $fontDictionary;

        if ($fontDictionary->Encoding !== null) {
            $this->_encoding = $fontDictionary->Encoding->value;
        }

        switch ($fontDictionary->Subtype->value) {
            case 'Type0':
                // Composite type 0 font
                if (count($fontDictionary->DescendantFonts->items) != 1) {
                    // Multiple descendant fonts are not supported
                    throw new Exception\NotImplementedException(self::TYPE_NOT_SUPPORTED);
                }

                $fontDictionaryIterator = $fontDictionary->DescendantFonts->items->getIterator();
                $fontDictionaryIterator->rewind();
                $descendantFont = $fontDictionaryIterator->current();
                $fontDescriptor = $descendantFont->FontDescriptor;
				if ($fontDictionary->ToUnicode) {
					$this->_toUnicode = $this->_parseUnicodeConversionStream( $fontDictionary->ToUnicode->value );
				}
                break;

            case 'Type1':
                if ($fontDictionary->FontDescriptor === null) {
                    // That's one of the standard fonts
                    $standardFont = Pdf\Font::fontWithName($fontDictionary->BaseFont->value);

                    $this->_fontNames          = $standardFont->getFontNames();
                    $this->_isBold             = $standardFont->isBold();
                    $this->_isItalic           = $standardFont->isItalic();
                    $this->_isMonospace        = $standardFont->isMonospace();
                    $this->_underlinePosition  = $standardFont->getUnderlinePosition();
                    $this->_underlineThickness = $standardFont->getUnderlineThickness();
                    $this->_strikePosition     = $standardFont->getStrikePosition();
                    $this->_strikeThickness    = $standardFont->getStrikeThickness();
                    $this->_unitsPerEm         = $standardFont->getUnitsPerEm();
                    $this->_ascent             = $standardFont->getAscent();
                    $this->_descent            = $standardFont->getDescent();
                    $this->_lineGap            = $standardFont->getLineGap();

                    return;
                }

                $fontDescriptor = $fontDictionary->FontDescriptor;
                break;

            case 'TrueType':
                $fontDescriptor = $fontDictionary->FontDescriptor;
                break;

            default:
                throw new Exception\NotImplementedException(self::TYPE_NOT_SUPPORTED);
        }

        $this->_fontNames[Pdf\Font::NAME_POSTSCRIPT]['en'] = iconv('UTF-8', 'UTF-16BE', $fontDictionary->BaseFont->value);

        $this->_isBold             = false; // this property is actually not used anywhere
        $this->_isItalic           = ( ($fontDescriptor->Flags->value & (1 << 6)) != 0 ); // Bit-7 is set
        $this->_isMonospace        = ( ($fontDescriptor->Flags->value & (1 << 0)) != 0 ); // Bit-1 is set
        $this->_underlinePosition  = null; // Can't be extracted
        $this->_underlineThickness = null; // Can't be extracted
        $this->_strikePosition     = null; // Can't be extracted
        $this->_strikeThickness    = null; // Can't be extracted
        $this->_unitsPerEm         = null; // Can't be extracted
        $this->_ascent             = $fontDescriptor->Ascent->value;
        $this->_descent            = $fontDescriptor->Descent->value;
        $this->_lineGap            = null; // Can't be extracted
    }

    /**
     * Returns an array of glyph numbers corresponding to the Unicode characters.
     *
     * If a particular character doesn't exist in this font, the special 'missing
     * character glyph' will be substituted.
     *
     * See also {@link glyphNumberForCharacter()}.
     *
     * @param array $characterCodes Array of Unicode character codes (code points).
     * @return array Array of glyph numbers.
     */
    public function glyphNumbersForCharacters($characterCodes)
    {
        throw new Exception\NotImplementedException(self::OPERATION_NOT_SUPPORTED);
    }

    /**
     * Returns the glyph number corresponding to the Unicode character.
     *
     * If a particular character doesn't exist in this font, the special 'missing
     * character glyph' will be substituted.
     *
     * See also {@link glyphNumbersForCharacters()} which is optimized for bulk
     * operations.
     *
     * @param integer $characterCode Unicode character code (code point).
     * @return integer Glyph number.
     */
    public function glyphNumberForCharacter($characterCode)
    {
        throw new Exception\NotImplementedException(self::OPERATION_NOT_SUPPORTED);
    }

    /**
     * Returns a number between 0 and 1 inclusive that indicates the percentage
     * of characters in the string which are covered by glyphs in this font.
     *
     * Since no one font will contain glyphs for the entire Unicode character
     * range, this method can be used to help locate a suitable font when the
     * actual contents of the string are not known.
     *
     * Note that some fonts lie about the characters they support. Additionally,
     * fonts don't usually contain glyphs for control characters such as tabs
     * and line breaks, so it is rare that you will get back a full 1.0 score.
     * The resulting value should be considered informational only.
     *
     * @param string $string
     * @param string $charEncoding (optional) Character encoding of source text.
     *   If omitted, uses 'current locale'.
     * @return float
     */
    public function getCoveredPercentage($string, $charEncoding = '')
    {
        throw new Exception\NotImplementedException(self::OPERATION_NOT_SUPPORTED);
    }

    /**
     * Returns the widths of the glyphs.
     *
     * The widths are expressed in the font's glyph space. You are responsible
     * for converting to user space as necessary. See {@link unitsPerEm()}.
     *
     * See also {@link widthForGlyph()}.
     *
     * @param array $glyphNumbers Array of glyph numbers.
     * @return array Array of glyph widths (integers).
     * @throws \ZendPdf\Exception\ExceptionInterface
     */
    public function widthsForGlyphs($glyphNumbers)
    {
        throw new Exception\NotImplementedException(self::OPERATION_NOT_SUPPORTED);
    }

    /**
     * Returns the width of the glyph.
     *
     * Like {@link widthsForGlyphs()} but used for one glyph at a time.
     *
     * @param integer $glyphNumber
     * @return integer
     * @throws \ZendPdf\Exception\ExceptionInterface
     */
    public function widthForGlyph($glyphNumber)
    {
        throw new Exception\NotImplementedException(self::OPERATION_NOT_SUPPORTED);
    }

    /**
     * Convert string to the font encoding.
     *
     * The method is used to prepare string for text drawing operators
     *
     * @param string $string
     * @param string $charEncoding Character encoding of source text.
     * @return string
     */
    public function encodeString($string, $charEncoding)
    {
        if ($this->_encoding == 'Identity-H') {
            return iconv($charEncoding, 'UTF-16BE', $string);
        }

        if ($this->_encoding == 'WinAnsiEncoding') {
            return iconv($charEncoding, 'CP1252//IGNORE', $string);
        }

        throw new Exception\CorruptedPdfException(self::ENCODING_NOT_SUPPORTED);
    }

    /**
     * Convert string from the font encoding.
     *
     * The method is used to convert strings retrieved from existing content streams
     *
     * @param string $string
     * @param string $charEncoding Character encoding of resulting text.
     * @return string
     */
    public function decodeString($string, $charEncoding)
    {
        if ($this->_encoding == 'Identity-H') {
            return iconv('UTF-16BE', $charEncoding, $string);
        }

        if ($this->_encoding == 'WinAnsiEncoding') {
            return iconv('CP1252', $charEncoding, $string);
        }

        throw new Exception\CorruptedPdfException(self::ENCODING_NOT_SUPPORTED);
    }

	/**
	 * Test font for unicode conversion support.
	 *
	 * @return bool True if extracted font included a unicode conversion stream.
	 */
	public function unicodeConversionSupported()
	{
		return ($this->_toUnicode !== null);
	}

	/**
	 * Get unicode character represented by a given glyph.
	 *
	 * @param string $glyph Hexadecimal representation of a glyph.
	 *
	 * @return string|null String containing the represented character or null if not found.
	 */
	public function unicodeForGlyph( $glyph )
	{
		$glyph = strtolower( $glyph );

		if ($this->_toUnicode !== null && isset($this->_toUnicode[ $glyph ])) {
			return $this->_toUnicode[ $glyph ];
		}

		return null;
	}

	/**
	 * Helper method for parsing extracted unicode conversion stream.
	 *
	 * @param string $StreamData
	 *
	 * @return array Associated array of glyph codes mapped to unicode characters.
	 */
	private static function _parseUnicodeConversionStream( $streamData )
	{
		$conversionMap = array();
		$matches       = array();

		$codeSpaceRange = self::_findStringBetweenMarkers( $streamData, 'begincodespacerange', 'endcodespacerange' );

		if (preg_match_all( '%\s*<(?P<start>[0-9a-fA-F]{4})>\s+<(?P<end>[0-9a-fA-F]{4})>\s*%m',
		                    $codeSpaceRange,
		                    $matches,
		                    PREG_SET_ORDER ) !== false
		) {
			foreach ($matches as $match) {
				$startCharacter = hexdec( $match[ 'start' ] );
				$endCharacter   = hexdec( $match[ 'end' ] );
				for ($character = $startCharacter; $character <= $endCharacter; $character++) {
					$characterCode                   = str_pad( dechex( $character ), 4, "0", STR_PAD_LEFT );
					$conversionMap[ $characterCode ] = null;
				}
			}
		}

		$BFRange = self::_findStringBetweenMarkers( $streamData, 'beginbfrange', 'endbfrange' );

		if (preg_match_all( '%^\s*<(?P<start>[0-9a-fA-F]{4})>\s+<(?P<end>[0-9a-fA-F]{4})>\s+<(?P<destination>[0-9a-fA-F]{4})>\s*$%m',
		                    $BFRange,
		                    $matches,
		                    PREG_SET_ORDER ) !== false
		) {
			foreach ($matches as $match) {
				$startCharacter = hexdec( $match[ 'start' ] );
				$endCharacter   = hexdec( $match[ 'end' ] );
				$destination    = hexdec( $match[ 'destination' ] );
				for ($character = $startCharacter; $character <= $endCharacter; $character++) {
					$characterCode                   = str_pad( dechex( $character ), 4, "0", STR_PAD_LEFT );
					$conversionMap[ $characterCode ] = self::_unicodeForCode( $destination );
					$destination++;
				}
			}
		}

		$BFChar = self::_findStringBetweenMarkers( $streamData, 'beginbfchar', 'endbfchar' );

		if (preg_match_all( '%^\s*<(?P<source>[0-9a-fA-F]{4})>\s+<(?P<destination>[0-9a-fA-F]{4})>\s*$%m',
		                    $BFChar,
		                    $matches, PREG_SET_ORDER ) !== false
		) {
			foreach ($matches as $match) {
				$source      = hexdec( $match[ 'source' ] );
				$destination = hexdec( $match[ 'destination' ] );

				$characterCode                   = str_pad( dechex( $source ), 4, "0", STR_PAD_LEFT );
				$conversionMap[ $characterCode ] = self::_unicodeForCode( $destination );
			}
		}

		return $conversionMap;
	}

	/**
	 * Helper method for extracting a sub-string from a $string.
	 *
	 * The search range can either start with a given $startMarker
	 * or, if the default value of an empty string is supplied,
	 * start at the beginning of the source $string.
	 *
	 * The search range can either end with a given $endMarker or,
	 * if the default value of an empty string is supplied, end
	 * at the end of the source $string.
	 *
	 * @param string $string      String to search for sub-string
	 * @param string $startMarker Marker at beginning of sub-string
	 * @param string $endMarker   Market at end of sub-string.
	 *
	 * @return string Text encapsulated by given Markers. Empty string
	 *                returned if either marker is not found.
	 */
	private static function _findStringBetweenMarkers( $string, $startMarker = "", $endMarker = "" )
	{
		if ($startMarker != "") {
			if (strpos( $string, $startMarker ) !== false) {
				$startPosition = strpos( $string, $startMarker ) + strlen( $startMarker );
			} else {
				return "";
			}
		} else {
			$startPosition = 0;
		}
		if ($endMarker !== "") {
			if (strpos( $string, $endMarker, $startPosition ) !== false) {
				$endPosition = strpos( $string, $endMarker, $startPosition );
			} else {
				return "";
			}
		} else {
			$endPosition = strlen( $string );
		}

		return substr( $string, $startPosition, ($endPosition - $startPosition) );
	}

	/**
	 * @param string $unicodeCharacterCode Hexadecimal character code.
	 *
	 * @return string Unicode character.
	 */
	private static function _unicodeForCode( $unicodeCharacterCode )
	{
		return self::_hex2unicode( '\u' . str_pad( dechex( $unicodeCharacterCode ), 4, "0", STR_PAD_LEFT ) );
	}

	/**
	 * @param string $hexValue
	 *
	 * @return string Unicode
	 */
	private static function _hex2unicode( $hexValue )
	{
		return preg_replace_callback( '/(?:\\\\u[0-9a-fA-Z]{4})+/', function ( $value ){
			$value = strtr( $value[ 0 ], array('\\u' => '') );

			return mb_convert_encoding( pack( 'H*', $value ), 'UTF-8', 'UTF-16BE' );
		}, $hexValue );
	}
}
