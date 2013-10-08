<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Pdf
 */

namespace ZendPdf\InternalType\StreamFilter\Compression;

use ZendPdf as Pdf;
use ZendPdf\Exception;
use Imagick;
use ImagickPixel;

/**
 * DCT stream filter
 *
 * @package    Zend_PDF
 * @subpackage Zend_PDF_Internal
 */
class Dct extends AbstractCompression
{
	/**
	 * Get EarlyChange decode param value
	 *
	 * @param array $params
	 *
	 * @return integer
	 * @throws \ZendPdf\Exception\ExceptionInterface
	 */
	private static function _getColorTransformValue( $params )
	{
		if (isset($params[ 'ColorTransform' ])) {
			$colorTransform = $params[ 'ColorTransform' ];

			if ($colorTransform != 0 && $colorTransform != 1) {
				throw new Exception\CorruptedPdfException('Invalid value of \'ColorTransform\' decode param - ' .
				                                          $colorTransform . '.');
			}

			return $colorTransform;
		} else {
			return 0;
		}
	}

	/**
	 * Encode data
	 *
	 * @param string $data
	 * @param array  $params
	 *
	 * @return string
	 * @throws \ZendPdf\Exception\ExceptionInterface
	 */
	public static function encode( $data, $params = NULL )
	{
		throw new Exception\NotImplementedException('Not implemented yet');
	}

	/**
	 * Decode data
	 *
	 * @param string $data
	 * @param array  $params
	 *
	 * @return string
	 * @throws \ZendPdf\Exception\ExceptionInterface
	 */
	public static function decode( $data, $params = NULL )
	{
		$imagickProcessor = new Imagick();
		$imagickProcessor->readimageblob( $data );

		if ($params !== NULL && self::_getColorTransformValue( $params )) {
			$imagickProcessor->setimagecolorspace(Imagick::COLORSPACE_RGB);
		}
		$pixelIterator = $imagickProcessor->getpixeliterator();

		$output = "";

		foreach ($pixelIterator as $row) {
			foreach ($row as $pixel) {
				if ($pixel instanceof ImagickPixel) {
					$color = $pixel->getColor();
					$output .= chr( $color[ 'r' ] );
					$output .= chr( $color[ 'g' ] );
					$output .= chr( $color[ 'b' ] );
				}
			}
		}

		return $output;
	}
}
