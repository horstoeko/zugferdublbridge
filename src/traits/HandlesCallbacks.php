<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge\traits;

/**
 * Trait for handling and firing callbacks
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
trait HandlesCallbacks
{
    /**
     * Internal helper function to fire a callback function
     *
     * @param  callable $callback
     * @param  array    ...$args
     * @return mixed
     */
    private function fireCallback($callback, ...$args)
    {
        if (!is_callable($callback)) {
            return null;
        }

        return call_user_func($callback, ...$args);
    }
}
