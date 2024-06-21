<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge\xml;

use DOMNodeList;

/**
 * Class representing the converter from CII syntax to UBL syntax
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
class XmlNodeList
{
    /**
     * Nodelist
     *
     * @var DOMNodeList|null
     */
    private $domNodeList = null;

    /**
     * Factory
     *
     * @param  DOMNodeList|null $domNodeList
     * @return XmlNodeList
     */
    public static function createFromDomNodelist(?DOMNodeList $domNodeList = null): XmlNodeList
    {
        return new static($domNodeList);
    }

    /**
     * Constructor
     *
     * @param DOMNodeList|null $domNodeList
     */
    public function __construct(?DOMNodeList $domNodeList = null)
    {
        $this->domNodeList = $domNodeList;
    }

    /**
     * Foreach node in internal nodelist
     *
     * @param  callable      $callback
     * @param  callable|null $callBackBefore
     * @param  callable|null $callbackAfter
     * @param  callable|null $callbackBeforeEach
     * @param  callable|null $callbackAfterEach
     * @return void
     */
    public function forEach($callback, $callBackBefore = null, $callbackAfter = null, $callbackBeforeEach = null, $callbackAfterEach = null)
    {
        if (is_null($this->domNodeList)) {
            return;
        }
        if (!is_callable($callback)) {
            return;
        }

        if (is_callable($callBackBefore)) {
            call_user_func($callBackBefore);
        }

        foreach ($this->domNodeList as $node) {
            if (is_callable($callbackBeforeEach)) {
                call_user_func($callbackBeforeEach);
            }
            call_user_func($callback, $node);
            if (is_callable($callbackAfterEach)) {
                call_user_func($callbackAfterEach);
            }
        }

        if (is_callable($callbackAfter)) {
            call_user_func($callbackAfter);
        }
    }
}
