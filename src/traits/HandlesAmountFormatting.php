<?php

declare(strict_types=1);

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
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @see      https://github.com/horstoeko/zugferdublbridge
 */
trait HandlesAmountFormatting
{
    /**
     * Internal flag to disable amount formattting
     *
     * @var bool
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
     * @return bool
     */
    public function getAmountFormatDisabled(): bool
    {
        return $this->amountFormatDisabled;
    }

    /**
     * Returns true if the amount formatting is enabled
     *
     * @return bool
     */
    public function getAmountFormatEnabled(): bool
    {
        return false === $this->getAmountFormatDisabled();
    }

    /**
     * Format amount value
     *
     * @param  null|string $amount
     * @return null|string
     */
    private function formatAmount(?string $amount): ?string
    {
        if (true === $this->getAmountFormatDisabled()) {
            return $amount;
        }

        if (is_null($amount)) {
            return $amount;
        }

        if (!is_numeric($amount)) {
            return $amount;
        }

        return (string) ((float) $amount);
    }
}
