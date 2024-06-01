<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge;

use horstoeko\zugferdublbridge\XmlDocumentReader;
use horstoeko\zugferdublbridge\XmlDocumentWriter;

/**
 * Class representing the base class of a converter
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
class XmlConverterBase
{
    /**
     * The input document
     *
     * @var XmlDocumentReader
     */
    protected $in = null;

    /**
     * The output document
     *
     * @var XmlDocumentWriter
     */
    protected $out = null;
}
