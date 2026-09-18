<?php

declare(strict_types=1);

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge\xml;

use DOMNode;
use DOMNodeList;
use horstoeko\zugferdublbridge\traits\HandlesCallbacks;

/**
 * Class representing a XML node list
 *
 * @category Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @see      https://github.com/horstoeko/zugferdublbridge
 */
class XmlNodeList
{
    use HandlesCallbacks;

    /**
     * Nodelist
     *
     * @var null|DOMNodeList<DOMNode>
     */
    private $domNodeList;

    /**
     * Constructor
     *
     * @param null|DOMNodeList<DOMNode> $domNodeList
     */
    public function __construct(?DOMNodeList $domNodeList = null)
    {
        $this->domNodeList = $domNodeList;
    }

    /**
     * Factory
     *
     * @param  null|DOMNodeList<DOMNode> $domNodeList
     * @return XmlNodeList
     */
    public static function createFromDomNodelist(?DOMNodeList $domNodeList = null): self
    {
        return new self($domNodeList);
    }

    /**
     * Foreach node in internal nodelist
     *
     * @param  callable      $callback
     * @param  null|callable $callBackBefore
     * @param  null|callable $callbackAfter
     * @param  null|callable $callbackBeforeEach
     * @param  null|callable $callbackAfterEach
     * @return void
     */
    public function forEach($callback, $callBackBefore = null, $callbackAfter = null, $callbackBeforeEach = null, $callbackAfterEach = null)
    {
        $this->forEachMax(0, $callback, $callBackBefore, $callbackAfter, $callbackBeforeEach, $callbackAfterEach);
    }

    /**
     * Foreach for only $max nodes in internal nodelist.
     *
     * @param  int           $max
     * @param  callable      $callback
     * @param  null|callable $callBackBefore
     * @param  null|callable $callbackAfter
     * @param  null|callable $callbackBeforeEach
     * @param  null|callable $callbackAfterEach
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
            ++$count;

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
