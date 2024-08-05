<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge\traits;

/**
 * Trait for handling supported profiles
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
trait HandlesAmountFormatting
{
    /**
     * Internal flag to disable amount formattting
     *
     * @var boolean
     */
    private $amountFormatDisabled = true;

    /**
     * Disable amount formatting
     *
     * @return static
     */
    public function disableAmountFormatDisabled()
    {
        $this->amountFormatDisabled = true;

        return $this;
    }

    /**
     * Enable amount formatting
     *
     * @return static
     */
    public function enableAmountFormatDisabled()
    {
        $this->amountFormatDisabled = false;

        return $this;
    }

    /**
     * Returns true if the amount formatting is disabled
     *
     * @return boolean
     */
    public function getAmountFormatDisabled(): bool
    {
        return $this->amountFormatDisabled;
    }

    /**
     * Returns true if the amount formatting is enabled
     *
     * @return boolean
     */
    public function getAmountFormatEnabled(): bool
    {
        return $this->getAmountFormatDisabled() === false;
    }

    /**
     * Format amount value
     *
     * @param  string|null $amount
     * @return string|null
     */
    private function formatAmount(?string $amount): ?string
    {
        if ($this->getAmountFormatDisabled() === true) {
            return $amount;
        }

        if (is_null($amount)) {
            return $amount;
        }

        if (!is_numeric($amount)) {
            return $amount;
        }

        return (string)((float)$amount);
    }
}
