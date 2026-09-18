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
trait HandlesDocumentTypes
{
    /**
     * Internal flag to disable the automatic detection of Invoice or CreditNote
     *
     * @var bool
     */
    private $automaticDocumentTypeModeDisabled = true;

    /**
     * Returns a list of docuemnt type codes which mean that the document type
     * is an invoice
     *
     * @return string[]
     */
    public function getInvoiceTypeCodes(): array
    {
        return [
            '80', '82', '84', '130', '202', '203', '204', '211', '295', '325', '326', '380', '383',
            '384', '385', '386', '387', '388', '389', '390', '393', '394', '395', '456', '457', '527',
            '575', '623', '633', '751', '780', '935',
        ];
    }

    /**
     * Returns a list of docuemnt type codes which mean that the document type
     * is a credit memo
     *
     * @return string[]
     */
    public function getCreditNoteTypeCodes(): array
    {
        return [
            '81', '83', '261', '262', '296', '308', '381', '396', '420', '458', '532',
        ];
    }

    /**
     * Returns true if $documentTypeCode means "Invoice"
     *
     * @param  string $documentTypeCode
     * @return bool
     */
    public function isInvoiceDocumentType(string $documentTypeCode): bool
    {
        return in_array($documentTypeCode, $this->getInvoiceTypeCodes(), true);
    }

    /**
     * Returns true if $documentTypeCode means "Credit Memo"
     *
     * @param  string $documentTypeCode
     * @return bool
     */
    public function isCreditMemoDocumentType(string $documentTypeCode): bool
    {
        return in_array($documentTypeCode, $this->getCreditNoteTypeCodes(), true);
    }

    /**
     * Disable automatic detection of Invoice/CreditNote
     *
     * @return static
     */
    public function disableAutomaticMode()
    {
        $this->automaticDocumentTypeModeDisabled = true;

        return $this;
    }

    /**
     * Enable automatic detection of Invoice/CreditNote
     *
     * @return static
     */
    public function enableAutomaticMode()
    {
        $this->automaticDocumentTypeModeDisabled = false;

        return $this;
    }

    /**
     * Returns true if the automatic document type detection is disabled
     *
     * @return bool
     */
    public function getAutomaticDocumentTypeModeDisabled(): bool
    {
        return $this->automaticDocumentTypeModeDisabled;
    }

    /**
     * Returns true if the automatic document type detection is enabled
     *
     * @return bool
     */
    public function getAutomaticDocumentTypeModeEnabled(): bool
    {
        return false === $this->getAutomaticDocumentTypeModeDisabled();
    }
}
