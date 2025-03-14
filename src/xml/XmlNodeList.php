<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge\xml;

use DOMNodeList;
use horstoeko\zugferdublbridge\traits\HandlesCallbacks;

/**
 * Class representing a XML node list
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
class XmlNodeList
{
    use HandlesCallbacks;

    /**
     * Nodelist
     *
     * @var DOMNodeList|null
     */
    private $domNodeList;

    /**
     * Factory
     *
     * @param  DOMNodeList|null $domNodeList
     * @return XmlNodeList
     */
    public static function createFromDomNodelist(?DOMNodeList $domNodeList = null): XmlNodeList
    {
        return new XmlNodeList($domNodeList);
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
        $this->forEachMax(0, $callback, $callBackBefore, $callbackAfter, $callbackBeforeEach, $callbackAfterEach);
    }

    /**
     * Foreach for only $max nodes in internal nodelist.
     *
     * @param  integer       $max
     * @param  callable      $callback
     * @param  callable|null $callBackBefore
     * @param  callable|null $callbackAfter
     * @param  callable|null $callbackBeforeEach
     * @param  callable|null $callbackAfterEach
     * @return void
     */
    public function forEachMax(int $max, $callback, $callBackBefore = null, $callbackAfter = null, $callbackBeforeEach = null, $callbackAfterEach = null)
    {
        if (is_null($this->domNodeList)) {
            return;
        }

        $this->fireCallback($callBackBefore);

        $count = 0;

        foreach ($this->domNodeList as $node) {
            $count++;

            if ($count > $max && $max > 0) {
                break;
            }

            $this->fireCallback($callbackBeforeEach, $node);
            $this->fireCallback($callback, $node);
            $this->fireCallback($callbackAfterEach, $node);
        }

        $this->fireCallback($callbackAfter);
    }
}
