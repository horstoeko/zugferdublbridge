<?php

declare(strict_types=1);

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge;

use DateTime;
use DOMException;
use Exception;
use horstoeko\zugferdublbridge\traits\HandlesAmountFormatting;
use horstoeko\zugferdublbridge\traits\HandlesDocumentTypes;
use horstoeko\zugferdublbridge\traits\HandlesProfiles;
use RuntimeException;
use ValueError;

/**
 * Class representing the converter from CII syntax to UBL syntax
 *
 * @category Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @see      https://github.com/horstoeko/zugferdublbridge
 */
class XmlConverterCiiToUbl extends XmlConverterBase
{
    use HandlesAmountFormatting;
    use HandlesDocumentTypes;
    use HandlesProfiles;

    /**
     * {@inheritDoc}
     */
    protected function getDestinationRoot(): string
    {
        return 'ubl:Invoice';
    }

    /**
     * {@inheritDoc}
     */
    protected function getSourceNamespaces(): array
    {
        return [
            'rsm' => 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100',
            'ram' => 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100',
            'qdt' => 'urn:un:unece:uncefact:data:Standard:QualifiedDataType:100',
            'udt' => 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100',
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getDestinationNamespaces(): array
    {
        return [
            'ubl' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException
     */
    protected function checkValidSource()
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);

        if (is_null($invoiceElement)) {
            throw new RuntimeException('The document is not a valid CII document');
        }

        $invoiceExchangeDocumentContext = $this->source->query('./rsm:ExchangedDocumentContext', $invoiceElement)->item(0);

        if (is_null($invoiceExchangeDocumentContext)) {
            throw new RuntimeException('The document is not a valid CII document');
        }

        $submittedProfile = $this->source->queryValue('./ram:GuidelineSpecifiedDocumentContextParameter/ram:ID', $invoiceExchangeDocumentContext);

        if (!$this->isSupportedProfile($submittedProfile)) {
            throw new RuntimeException(sprintf('The submitted profile %s is not supported', $submittedProfile));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws DOMException
     * @throws Exception
     * @throws ValueError
     */
    protected function doConvert()
    {
        $this->checkForCreditNote();
        $this->convertGeneral();
        $this->convertSellerTradeParty();
        $this->convertBuyerTradeParty();
        $this->convertPayeeTradeParty();
        $this->convertTaxRepresentativeParty();
        $this->convertShipToTradeParty();
        $this->convertPaymentMeans();
        $this->convertPaymentTerms();
        $this->convertDocumentLevelAllowanceCharge();
        $this->convertDocumentLevelTax();
        $this->convertDocumentSummation();
        $this->convertLines();

        return $this;
    }

    /**
     * Returns true if source is a credit note, otherwise false
     *
     * @return bool
     */
    private function getIsCreditNote(): bool
    {
        if ($this->getAutomaticDocumentTypeModeDisabled()) {
            return false;
        }

        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceExchangeDocument = $this->source->query('./rsm:ExchangedDocument', $invoiceElement)->item(0);

        return $this->isCreditMemoDocumentType($this->source->queryValue('./ram:TypeCode', $invoiceExchangeDocument));
    }

    /**
     * Check if the docukment is a credit note.
     *
     * @return void
     *
     * @throws DOMException
     */
    private function checkForCreditNote(): void
    {
        if (!$this->getIsCreditNote()) {
            return;
        }

        $this->destination->addNamespace('ubl', 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2');
        $this->destination->changeRoot('ubl:CreditNote');
    }

    /**
     * Convert general information
     *
     * @return void
     *
     * @throws DOMException
     * @throws Exception
     * @throws ValueError
     */
    private function convertGeneral(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceExchangeDocumentContext = $this->source->query('./rsm:ExchangedDocumentContext', $invoiceElement)->item(0);
        $invoiceExchangeDocument = $this->source->query('./rsm:ExchangedDocument', $invoiceElement)->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);
        $invoiceHeaderAgreement = $this->source->query('./ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);
        $invoiceHeaderDelivery = $this->source->query('./ram:ApplicableHeaderTradeDelivery', $invoiceSuppyChainTradeTransaction)->item(0);

        $customizationId = $this->source->queryValue('./ram:GuidelineSpecifiedDocumentContextParameter/ram:ID', $invoiceExchangeDocumentContext);
        $customizationId = $this->getForceDestinationProfileWithDefault($customizationId);

        $this->destination->element('cbc:CustomizationID', $customizationId);
        $this->destination->element('cbc:ProfileID', 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0');

        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:ID', $invoiceExchangeDocument));

        $this->destination->element(
            'cbc:IssueDate',
            $this->convertDateTime(
                $this->source->queryValue('./ram:IssueDateTime/udt:DateTimeString', $invoiceExchangeDocument),
                $this->source->queryValue('./ram:IssueDateTime/udt:DateTimeString/@format', $invoiceExchangeDocument)
            )
        );

        $this->destination->elementIf(
            !$this->getIsCreditNote(),
            'cbc:DueDate',
            $this->convertDateTime(
                $this->source->queryValue('./ram:SpecifiedTradePaymentTerms/ram:DueDateDateTime/udt:DateTimeString', $invoiceHeaderSettlement),
                $this->source->queryValue('./ram:SpecifiedTradePaymentTerms/ram:DueDateDateTime/udt:DateTimeString/@format', $invoiceHeaderSettlement)
            )
        );

        $this->destination->elementIf($this->getIsCreditNote(), 'cbc:CreditNoteTypeCode', $this->source->queryValue('./ram:TypeCode', $invoiceExchangeDocument));

        $this->destination->elementIf(!$this->getIsCreditNote(), 'cbc:InvoiceTypeCode', $this->source->queryValue('./ram:TypeCode', $invoiceExchangeDocument));

        $this->source->queryAll('./ram:IncludedNote', $invoiceExchangeDocument)->forEach(
            function ($includedNoteNode): void {
                $note = $this->source->queryValue('./ram:Content', $includedNoteNode);

                $subjectCode = $this->source->queryValue('./ram:SubjectCode', $includedNoteNode);

                if (null !== $subjectCode && '' !== $subjectCode) {
                    $note = sprintf('#%s#%s', $subjectCode, $note);
                }

                $this->destination->element('cbc:Note', $note);
            }
        );

        $this->destination->elementIf(
            !$this->getIsCreditNote(),
            'cbc:TaxPointDate',
            $this->convertDateTime(
                $this->source->queryValue('./ram:ApplicableTradeTax/ram:TaxPointDate/udt:DateString', $invoiceHeaderSettlement),
                $this->source->queryValue('./ram:ApplicableTradeTax/ram:TaxPointDate/udt:DateString/@format', $invoiceHeaderSettlement)
            )
        );

        $this->destination->element('cbc:DocumentCurrencyCode', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));

        $this->destination->element('cbc:TaxCurrencyCode', $this->source->queryValue('./ram:TaxCurrencyCode', $invoiceHeaderSettlement));

        $this->destination->element('cbc:AccountingCost', $this->source->queryValue('./ram:ReceivableSpecifiedTradeAccountingAccount/ram:ID', $invoiceHeaderSettlement));

        $this->destination->element('cbc:BuyerReference', $this->source->queryValue('./ram:BuyerReference', $invoiceHeaderAgreement));

        $this->source->whenExists(
            './ram:BillingSpecifiedPeriod',
            $invoiceHeaderSettlement,
            function ($nodeFound): void {
                $this->destination->startElement('cac:InvoicePeriod');
                $this->destination->element(
                    'cbc:StartDate',
                    $this->convertDateTime(
                        $this->source->queryValue('./ram:StartDateTime/udt:DateTimeString', $nodeFound),
                        $this->source->queryValue('./ram:StartDateTime/udt:DateTimeString/@format', $nodeFound)
                    )
                );
                $this->destination->element(
                    'cbc:EndDate',
                    $this->convertDateTime(
                        $this->source->queryValue('./ram:EndDateTime/udt:DateTimeString', $nodeFound),
                        $this->source->queryValue('./ram:EndDateTime/udt:DateTimeString/@format', $nodeFound)
                    )
                );
                $this->destination->endElement();
            }
        );

        $this->source->whenExists(
            './ram:BuyerOrderReferencedDocument/ram:IssuerAssignedID',
            $invoiceHeaderAgreement,
            function ($nodeFound) use ($invoiceHeaderAgreement): void {
                $this->destination->startElement('cac:OrderReference');
                $this->destination->element('cbc:ID', $nodeFound->nodeValue);
                $this->destination->element('cbc:SalesOrderID', $this->source->queryValue('./ram:SellerOrderReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderAgreement));
                $this->destination->endElement();
            },
            function () use ($invoiceHeaderAgreement): void {
                $this->source->whenExists(
                    './ram:SellerOrderReferencedDocument/ram:IssuerAssignedID',
                    $invoiceHeaderAgreement,
                    function ($sellerOrderReferencedDocumentNode): void {
                        $this->destination->startElement('cac:OrderReference');
                        $this->destination->element('cbc:ID', 'Dummy');
                        $this->destination->element('cbc:SalesOrderID', $sellerOrderReferencedDocumentNode->nodeValue);
                        $this->destination->endElement();
                    }
                );
            }
        );

        $this->source->whenExists(
            './ram:InvoiceReferencedDocument',
            $invoiceHeaderSettlement,
            function ($nodeFound): void {
                $this->destination->startElement('cac:BillingReference');
                $this->destination->startElement('cac:InvoiceDocumentReference');
                $this->destination->element('cbc:ID', $this->source->queryValue('./ram:IssuerAssignedID', $nodeFound));
                $this->destination->element(
                    'cbc:IssueDate',
                    $this->convertDateTime(
                        $this->source->queryValue('./ram:FormattedIssueDateTime/qdt:DateTimeString', $nodeFound),
                        $this->source->queryValue('./ram:FormattedIssueDateTime/qdt:DateTimeString/@format', $nodeFound)
                    )
                );
                $this->destination->endElement();
                $this->destination->endElement();
            }
        );

        $this->source->queryAll('./ram:DespatchAdviceReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderDelivery)->forEach(
            function ($nodeFound): void {
                $this->destination->startElement('cac:DespatchDocumentReference');
                $this->destination->element('cbc:ID', $nodeFound->nodeValue);
                $this->destination->endElement();
            }
        );

        $this->source->queryAll('./ram:ReceivingAdviceReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderDelivery)->forEach(
            function ($nodeFound): void {
                $this->destination->startElement('cac:ReceiptDocumentReference');
                $this->destination->element('cbc:ID', $nodeFound->nodeValue);
                $this->destination->endElement();
            }
        );

        $addDocuments = $this->getIsCreditNote() ? ['CON', 'ADD', 'ORI'] : ['ORI', 'CON', 'ADD', 'PRJ'];

        foreach ($addDocuments as $addDocument) {
            if ('CON' === $addDocument) {
                $this->source->queryAll('./ram:ContractReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderAgreement)->forEach(
                    function ($nodeFound): void {
                        $this->destination->startElement('cac:ContractDocumentReference');
                        $this->destination->element('cbc:ID', $nodeFound->nodeValue);
                        $this->destination->endElement();
                    }
                );
            }

            if ('ADD' === $addDocument) {
                $this->source->queryAll('./ram:AdditionalReferencedDocument', $invoiceHeaderAgreement)->forEach(
                    function ($additionalReferencedDocumentNode): void {
                        $this->source->whenNotEquals(
                            './ram:TypeCode',
                            $additionalReferencedDocumentNode,
                            '50',
                            function () use ($additionalReferencedDocumentNode): void {
                                $this->destination->startElement('cac:AdditionalDocumentReference');
                                $this->destination->element('cbc:ID', $this->source->queryValue('./ram:IssuerAssignedID', $additionalReferencedDocumentNode));

                                if ('130' === $this->source->queryValue('./ram:TypeCode', $additionalReferencedDocumentNode)) {
                                    $this->destination->element('cbc:DocumentTypeCode', $this->source->queryValue('./ram:TypeCode', $additionalReferencedDocumentNode));
                                }

                                $this->destination->element('cbc:DocumentDescription', $this->source->queryValue('./ram:Name', $additionalReferencedDocumentNode));
                                $this->source->whenExists(
                                    './ram:AttachmentBinaryObject',
                                    $additionalReferencedDocumentNode,
                                    function ($attachmentBinaryObjectNode, $additionalReferencedDocumentNode): void {
                                        $this->destination->startElement('cac:Attachment');
                                        $this->destination->elementWithMultipleAttributes(
                                            'cbc:EmbeddedDocumentBinaryObject',
                                            $attachmentBinaryObjectNode->nodeValue,
                                            [
                                                'mimeCode' => $attachmentBinaryObjectNode->getAttribute('mimeCode'),
                                                'filename' => $attachmentBinaryObjectNode->getAttribute('filename'),
                                            ]
                                        );
                                        $this->source->whenExists(
                                            './ram:URIID',
                                            $additionalReferencedDocumentNode,
                                            function ($uriIdNode): void {
                                                $this->destination->startElement('cac:ExternalReference');
                                                $this->destination->element('cbc:URI', $uriIdNode->nodeValue);
                                                $this->destination->endElement();
                                            }
                                        );
                                        $this->destination->endElement();
                                    }
                                );
                                $this->destination->endElement();
                            }
                        );
                    }
                );
            }

            if ('ORI' === $addDocument) {
                $this->source->queryAll('./ram:AdditionalReferencedDocument', $invoiceHeaderAgreement)->forEach(
                    function ($nodeFound): void {
                        $this->source->whenEquals(
                            './ram:TypeCode',
                            $nodeFound,
                            '50',
                            function () use ($nodeFound): void {
                                $this->destination->startElement('cac:OriginatorDocumentReference');
                                $this->destination->element('cbc:ID', $this->source->queryValue('./ram:IssuerAssignedID', $nodeFound));
                                $this->destination->endElement();
                            }
                        );
                    }
                );
            }

            if ('PRJ' === $addDocument) {
                $this->source->queryAll('./ram:SpecifiedProcuringProject/ram:ID', $invoiceHeaderAgreement)->forEach(
                    function ($nodeFound): void {
                        $this->destination->startElement('cac:ProjectReference');
                        $this->destination->element('cbc:ID', $nodeFound->nodeValue);
                        $this->destination->endElement();
                    }
                );
            }
        }
    }

    /**
     * Converts the seller trade party of an CII document
     *
     * @return void
     */
    private function convertSellerTradeParty(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);
        $invoiceHeaderAgreement = $this->source->query('./ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:SellerTradeParty',
            $invoiceHeaderAgreement,
            function ($sellerTradePartyNode) use ($invoiceHeaderSettlement): void {
                $this->destination->startElement('cac:AccountingSupplierParty');
                $this->destination->startElement('cac:Party');

                $this->source->whenExists(
                    './ram:URIUniversalCommunication/ram:URIID',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyUniversalCommNode): void {
                        $this->destination->startElement('cbc:EndpointID', $sellerTradePartyUniversalCommNode->nodeValue);
                        $this->destination->attribute('schemeID', $this->source->queryValue('./@schemeID', $sellerTradePartyUniversalCommNode));
                        $this->destination->endElement();
                    }
                );

                // Set the IDs of the seller
                // ID's cardinallity
                // CII - ID is 0..unbounded, GlobalID is 0..unbounded
                // UBL - PartyIdentification is 0..unbounded

                $this->source->queryAll('./ram:ID', $sellerTradePartyNode)->forEach(
                    function ($sellerTradePartyIdNode): void {
                        $this->destination->startElement('cac:PartyIdentification');
                        $this->destination->elementWithAttribute('cbc:ID', $sellerTradePartyIdNode->nodeValue, 'schemeID', $sellerTradePartyIdNode->getAttribute('schemeID'));
                        $this->destination->endElement();
                    }
                );

                $this->source->queryAll('./ram:GlobalID', $sellerTradePartyNode)->forEach(
                    function ($sellerTradePartyGlobalIdNode): void {
                        $this->destination->startElement('cac:PartyIdentification');
                        $this->destination->elementWithAttribute('cbc:ID', $sellerTradePartyGlobalIdNode->nodeValue, 'schemeID', $sellerTradePartyGlobalIdNode->getAttribute('schemeID'));
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    './ram:CreditorReferenceID',
                    $invoiceHeaderSettlement,
                    function ($DirectDebitMandateNode): void {
                        $this->destination->startElement('cac:PartyIdentification');
                        $this->destination->elementWithAttribute('cbc:ID', $DirectDebitMandateNode->nodeValue, 'schemeID', 'SEPA');
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    './ram:SpecifiedLegalOrganization/ram:TradingBusinessName',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyLegalOrgNameNode): void {
                        $this->destination->startElement('cac:PartyName');
                        $this->destination->element('cbc:Name', $sellerTradePartyLegalOrgNameNode->nodeValue);
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    './ram:PostalTradeAddress',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyPostalAddressNode): void {
                        $this->destination->startElement('cac:PostalAddress');
                        $this->destination->element('cbc:StreetName', $this->source->queryValue('./ram:LineOne', $sellerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:AdditionalStreetName', $this->source->queryValue('./ram:LineTwo', $sellerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CityName', $this->source->queryValue('./ram:CityName', $sellerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:PostalZone', $this->source->queryValue('./ram:PostcodeCode', $sellerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CountrySubentity', $this->source->queryValue('./ram:CountrySubDivisionName', $sellerTradePartyPostalAddressNode));

                        $this->source->whenExists(
                            './ram:LineThree',
                            $sellerTradePartyPostalAddressNode,
                            function ($sellerTradePartyPostalAddressNode): void {
                                $this->destination->startElement('cac:AddressLine');
                                $this->destination->element('cbc:Line', $sellerTradePartyPostalAddressNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:CountryID',
                            $sellerTradePartyPostalAddressNode,
                            function ($sellerTradePartyPostalAddressCountryNode): void {
                                $this->destination->startElement('cac:Country');
                                $this->destination->element('cbc:IdentificationCode', $sellerTradePartyPostalAddressCountryNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    "./ram:SpecifiedTaxRegistration/ram:ID[@schemeID='VA']",
                    $sellerTradePartyNode,
                    function ($sellerTradePartyTaxRegNode): void {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'VAT');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    "./ram:SpecifiedTaxRegistration/ram:ID[@schemeID='FC']",
                    $sellerTradePartyNode,
                    function ($sellerTradePartyTaxRegNode): void {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'FC');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );

                $this->destination->group(
                    'cac:PartyLegalEntity',
                    function () use ($sellerTradePartyNode): void {
                        $this->destination->element('cbc:RegistrationName', $this->source->queryValue('./ram:Name', $sellerTradePartyNode));
                        $this->destination->elementWithAttribute('cbc:CompanyID', $this->source->queryValue('./ram:SpecifiedLegalOrganization/ram:ID', $sellerTradePartyNode), 'schemeID', $this->source->queryValue('./ram:SpecifiedLegalOrganization/ram:ID/@schemeID', $sellerTradePartyNode));
                        $this->destination->element('cbc:CompanyLegalForm', $this->source->queryValue('./ram:Description', $sellerTradePartyNode));
                    }
                );

                $this->source->whenExists(
                    './ram:DefinedTradeContact',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyContactNode): void {
                        $this->destination->startElement('cac:Contact');
                        $this->destination->element('cbc:Name', $this->source->queryValue('./ram:PersonName', $sellerTradePartyContactNode));
                        $this->destination->element('cbc:Telephone', $this->source->queryValue('./ram:TelephoneUniversalCommunication/ram:CompleteNumber', $sellerTradePartyContactNode));
                        $this->destination->element('cbc:ElectronicMail', $this->source->queryValue('./ram:EmailURIUniversalCommunication/ram:URIID', $sellerTradePartyContactNode));
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
                $this->destination->endElement();
            }
        );
    }

    /**
     * Converts the seller trade party of an CII document
     *
     * @return void
     */
    private function convertBuyerTradeParty(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderAgreement = $this->source->query('./ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:BuyerTradeParty',
            $invoiceHeaderAgreement,
            function ($buyerTradePartyNode): void {
                $this->destination->startElement('cac:AccountingCustomerParty');
                $this->destination->startElement('cac:Party');

                $this->source->whenExists(
                    './ram:URIUniversalCommunication/ram:URIID',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyUniversalCommNode): void {
                        $this->destination->startElement('cbc:EndpointID', $buyerTradePartyUniversalCommNode->nodeValue);
                        $this->destination->attribute('schemeID', $this->source->queryValue('./@schemeID', $buyerTradePartyUniversalCommNode));
                        $this->destination->endElement();
                    }
                );

                // Set the IDs of the buyer
                // ID's cardinallity
                // CII - ID is 0..1, GlobalID is 0..1
                // UBL - PartyIdentification is 0..1

                $this->source->whenExists(
                    './ram:GlobalID',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyGlobalIdNode): void {
                        $this->destination->startElement('cac:PartyIdentification');
                        $this->destination->elementWithAttribute('cbc:ID', $buyerTradePartyGlobalIdNode->nodeValue, 'schemeID', $buyerTradePartyGlobalIdNode->getAttribute('schemeID'));
                        $this->destination->endElement();
                    },
                    function () use ($buyerTradePartyNode): void {
                        $this->source->whenExists(
                            './ram:ID',
                            $buyerTradePartyNode,
                            function ($buyerTradePartyIdNode): void {
                                $this->destination->startElement('cac:PartyIdentification');
                                $this->destination->elementWithAttribute('cbc:ID', $buyerTradePartyIdNode->nodeValue, 'schemeID', $buyerTradePartyIdNode->getAttribute('schemeID'));
                                $this->destination->endElement();
                            }
                        );
                    }
                );

                $this->source->whenExists(
                    './ram:SpecifiedLegalOrganization/ram:TradingBusinessName',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyLegalOrgNameNode): void {
                        $this->destination->startElement('cac:PartyName');
                        $this->destination->element('cbc:Name', $buyerTradePartyLegalOrgNameNode->nodeValue);
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    './ram:PostalTradeAddress',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyPostalAddressNode): void {
                        $this->destination->startElement('cac:PostalAddress');
                        $this->destination->element('cbc:StreetName', $this->source->queryValue('./ram:LineOne', $buyerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:AdditionalStreetName', $this->source->queryValue('./ram:LineTwo', $buyerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CityName', $this->source->queryValue('./ram:CityName', $buyerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:PostalZone', $this->source->queryValue('./ram:PostcodeCode', $buyerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CountrySubentity', $this->source->queryValue('./ram:CountrySubDivisionName', $buyerTradePartyPostalAddressNode));

                        $this->source->whenExists(
                            './ram:LineThree',
                            $buyerTradePartyPostalAddressNode,
                            function ($buyerTradePartyPostalAddressNode): void {
                                $this->destination->startElement('cac:AddressLine');
                                $this->destination->element('cbc:Line', $buyerTradePartyPostalAddressNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:CountryID',
                            $buyerTradePartyPostalAddressNode,
                            function ($buyerTradePartyPostalAddressCountryNode): void {
                                $this->destination->startElement('cac:Country');
                                $this->destination->element('cbc:IdentificationCode', $buyerTradePartyPostalAddressCountryNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    "./ram:SpecifiedTaxRegistration/ram:ID[@schemeID='VA']",
                    $buyerTradePartyNode,
                    function ($buyerTradePartyTaxRegNode): void {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $buyerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'VAT');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    "./ram:SpecifiedTaxRegistration/ram:ID[@schemeID='FC']",
                    $buyerTradePartyNode,
                    function ($sellerTradePartyTaxRegNode): void {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'FC');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );

                $this->destination->group(
                    'cac:PartyLegalEntity',
                    function () use ($buyerTradePartyNode): void {
                        $this->destination->element('cbc:RegistrationName', $this->source->queryValue('./ram:Name', $buyerTradePartyNode));
                        $this->destination->elementWithAttribute('cbc:CompanyID', $this->source->queryValue('./ram:SpecifiedLegalOrganization/ram:ID', $buyerTradePartyNode), 'schemeID', $this->source->queryValue('./ram:SpecifiedLegalOrganization/ram:ID/@schemeID', $buyerTradePartyNode));
                        $this->destination->element('cbc:CompanyLegalForm', $this->source->queryValue('./ram:Description', $buyerTradePartyNode));
                    }
                );

                $this->source->whenExists(
                    './ram:DefinedTradeContact',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyContactNode): void {
                        $this->destination->startElement('cac:Contact');
                        $this->destination->element('cbc:Name', $this->source->queryValue('./ram:PersonName', $buyerTradePartyContactNode));
                        $this->destination->element('cbc:Telephone', $this->source->queryValue('./ram:TelephoneUniversalCommunication/ram:CompleteNumber', $buyerTradePartyContactNode));
                        $this->destination->element('cbc:ElectronicMail', $this->source->queryValue('./ram:EmailURIUniversalCommunication/ram:URIID', $buyerTradePartyContactNode));
                        $this->destination->endElement();
                    }
                );

                $this->destination->endElement();
                $this->destination->endElement();
            }
        );
    }

    /**
     * Converts the payee trade party of an CII document
     *
     * @return void
     */
    private function convertPayeeTradeParty(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:PayeeTradeParty',
            $invoiceHeaderSettlement,
            function ($payeeTradePartyNode): void {
                $this->destination->startElement('cac:PayeeParty');

                $this->source->whenExists(
                    './ram:URIUniversalCommunication/ram:URIID',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyUniversalCommNode): void {
                        $this->destination->startElement('cbc:EndpointID', $payeeTradePartyUniversalCommNode->nodeValue);
                        $this->destination->attribute('schemeID', $this->source->queryValue('./@schemeID', $payeeTradePartyUniversalCommNode));
                        $this->destination->endElement();
                    }
                );

                // Set the IDs of the payee
                // ID's cardinallity
                // CII - ID is 0..1, GlobalID is 0..1
                // UBL - PartyIdentification is 0..1

                $this->source->whenExists(
                    './ram:GlobalID',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyGlobalIdNode): void {
                        $this->destination->startElement('cac:PartyIdentification');
                        $this->destination->elementWithAttribute('cbc:ID', $payeeTradePartyGlobalIdNode->nodeValue, 'schemeID', $payeeTradePartyGlobalIdNode->getAttribute('schemeID'));
                        $this->destination->endElement();
                    },
                    function () use ($payeeTradePartyNode): void {
                        $this->source->whenExists(
                            './ram:ID',
                            $payeeTradePartyNode,
                            function ($payeeTradePartyIdNode): void {
                                $this->destination->startElement('cac:PartyIdentification');
                                $this->destination->elementWithAttribute('cbc:ID', $payeeTradePartyIdNode->nodeValue, 'schemeID', $payeeTradePartyIdNode->getAttribute('schemeID'));
                                $this->destination->endElement();
                            }
                        );
                    }
                );

                $this->source->whenExists(
                    './ram:Name',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyNameNode): void {
                        $this->destination->startElement('cac:PartyName');
                        $this->destination->element('cbc:Name', $payeeTradePartyNameNode->nodeValue);
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    './ram:PostalTradeAddress',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyPostalAddressNode): void {
                        $this->destination->startElement('cac:PostalAddress');
                        $this->destination->element('cbc:StreetName', $this->source->queryValue('./ram:LineOne', $payeeTradePartyPostalAddressNode));
                        $this->destination->element('cbc:AdditionalStreetName', $this->source->queryValue('./ram:LineTwo', $payeeTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CityName', $this->source->queryValue('./ram:CityName', $payeeTradePartyPostalAddressNode));
                        $this->destination->element('cbc:PostalZone', $this->source->queryValue('./ram:PostcodeCode', $payeeTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CountrySubentity', $this->source->queryValue('./ram:CountrySubDivisionName', $payeeTradePartyPostalAddressNode));

                        $this->source->whenExists(
                            './ram:LineThree',
                            $payeeTradePartyPostalAddressNode,
                            function ($payeeTradePartyPostalAddressNode): void {
                                $this->destination->startElement('cac:AddressLine');
                                $this->destination->element('cbc:Line', $payeeTradePartyPostalAddressNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:CountryID',
                            $payeeTradePartyPostalAddressNode,
                            function ($payeeTradePartyPostalAddressCountryNode): void {
                                $this->destination->startElement('cac:Country');
                                $this->destination->element('cbc:IdentificationCode', $payeeTradePartyPostalAddressCountryNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    "./ram:SpecifiedTaxRegistration/ram:ID[@schemeID='VA']",
                    $payeeTradePartyNode,
                    function ($payeeTradePartyTaxRegNode): void {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $payeeTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'VAT');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    "./ram:SpecifiedTaxRegistration/ram:ID[@schemeID='FC']",
                    $payeeTradePartyNode,
                    function ($sellerTradePartyTaxRegNode): void {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'FC');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    './ram:SpecifiedLegalOrganization/ram:ID',
                    $payeeTradePartyNode,
                    function () use ($payeeTradePartyNode): void {
                        $this->destination->startElement('cac:PartyLegalEntity');
                        $this->destination->elementWithAttribute('cbc:CompanyID', $this->source->queryValue('./ram:SpecifiedLegalOrganization/ram:ID', $payeeTradePartyNode), 'schemeID', $this->source->queryValue('./ram:SpecifiedLegalOrganization/ram:ID/@schemeID', $payeeTradePartyNode));
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    './ram:DefinedTradeContact',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyContactNode): void {
                        $this->destination->startElement('cac:Contact');
                        $this->destination->element('cbc:Name', $this->source->queryValue('./ram:PersonName', $payeeTradePartyContactNode));
                        $this->destination->element('cbc:Telephone', $this->source->queryValue('./ram:TelephoneUniversalCommunication/ram:CompleteNumber', $payeeTradePartyContactNode));
                        $this->destination->element('cbc:ElectronicMail', $this->source->queryValue('./ram:EmailURIUniversalCommunication/ram:URIID', $payeeTradePartyContactNode));
                        $this->destination->endElement();
                    }
                );

                $this->destination->endElement();
            }
        );
    }

    /**
     * Convert Seller Tax Representative Party
     *
     * @return void
     */
    private function convertTaxRepresentativeParty(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderAgreement = $this->source->query('./ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:SellerTaxRepresentativeTradeParty',
            $invoiceHeaderAgreement,
            function ($sellerTaxRepresentativePartyNode): void {
                $this->destination->startElement('cac:TaxRepresentativeParty');

                $this->source->whenExists(
                    './ram:Name',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyNameNode): void {
                        $this->destination->startElement('cac:PartyName');
                        $this->destination->element('cbc:Name', $sellerTaxRepresentativePartyNameNode->nodeValue);
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    './ram:PostalTradeAddress',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyPostalAddressNode): void {
                        $this->destination->startElement('cac:PostalAddress');
                        $this->destination->element('cbc:StreetName', $this->source->queryValue('./ram:LineOne', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('cbc:AdditionalStreetName', $this->source->queryValue('./ram:LineTwo', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('cbc:CityName', $this->source->queryValue('./ram:CityName', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('cbc:PostalZone', $this->source->queryValue('./ram:PostcodeCode', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('cbc:CountrySubentity', $this->source->queryValue('./ram:CountrySubDivisionName', $sellerTaxRepresentativePartyPostalAddressNode));

                        $this->source->whenExists(
                            './ram:LineThree',
                            $sellerTaxRepresentativePartyPostalAddressNode,
                            function ($sellerTaxRepresentativePartyPostalAddressNode): void {
                                $this->destination->startElement('cac:AddressLine');
                                $this->destination->element('cbc:Line', $sellerTaxRepresentativePartyPostalAddressNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:CountryID',
                            $sellerTaxRepresentativePartyPostalAddressNode,
                            function ($sellerTaxRepresentativePartyPostalAddressCountryNode): void {
                                $this->destination->startElement('cac:Country');
                                $this->destination->element('cbc:IdentificationCode', $sellerTaxRepresentativePartyPostalAddressCountryNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    "./ram:SpecifiedTaxRegistration/ram:ID[@schemeID='VA']",
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyTaxRegNode): void {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTaxRepresentativePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'VAT');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    "./ram:SpecifiedTaxRegistration/ram:ID[@schemeID='FC']",
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTradePartyTaxRegNode): void {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'FC');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );

                $this->destination->endElement();
            }
        );
    }

    /**
     * Converts the ship-to trade party of an CII document
     *
     * @return void
     */
    private function convertShipToTradeParty(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderDelivery = $this->source->query('./ram:ApplicableHeaderTradeDelivery', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:ShipToTradeParty',
            $invoiceHeaderDelivery,
            function ($shipToTradePartyNode) use ($invoiceHeaderDelivery): void {
                $this->destination->startElement('cac:Delivery');

                $this->destination->element(
                    'cbc:ActualDeliveryDate',
                    $this->convertDateTime(
                        $this->source->queryValue('./ram:ActualDeliverySupplyChainEvent/ram:OccurrenceDateTime/udt:DateTimeString', $invoiceHeaderDelivery),
                        $this->source->queryValue('./ram:ActualDeliverySupplyChainEvent/ram:OccurrenceDateTime/udt:DateTimeString/@format', $invoiceHeaderDelivery)
                    )
                );

                $this->destination->startElement('cac:DeliveryLocation');

                // Set the IDs of the ship to party
                // ID's cardinallity
                // CII - ID is 0..1, GlobalID is 0..1
                // UBL - PartyIdentification is 0..1

                $this->source->whenExists(
                    './ram:GlobalID',
                    $shipToTradePartyNode,
                    function ($shipToTradePartyGlobalIdNode): void {
                        $this->destination->elementWithAttribute('cbc:ID', $shipToTradePartyGlobalIdNode->nodeValue, 'schemeID', $shipToTradePartyGlobalIdNode->getAttribute('schemeID'));
                    },
                    function () use ($shipToTradePartyNode): void {
                        $this->source->whenExists(
                            './ram:ID',
                            $shipToTradePartyNode,
                            function ($shipToTradePartyIdNode): void {
                                $this->destination->elementWithAttribute('cbc:ID', $shipToTradePartyIdNode->nodeValue, 'schemeID', $shipToTradePartyIdNode->getAttribute('schemeID'));
                            }
                        );
                    }
                );

                $this->source->whenExists(
                    './ram:PostalTradeAddress',
                    $shipToTradePartyNode,
                    function ($shipToTradePartyPostalAddressNode): void {
                        $this->destination->startElement('cac:Address');

                        $this->destination->element('cbc:StreetName', $this->source->queryValue('./ram:LineOne', $shipToTradePartyPostalAddressNode));
                        $this->destination->element('cbc:AdditionalStreetName', $this->source->queryValue('./ram:LineTwo', $shipToTradePartyPostalAddressNode));

                        $this->source->whenExists(
                            './ram:LineThree',
                            $shipToTradePartyPostalAddressNode,
                            function ($shipToTradePartyPostalAddressNode): void {
                                $this->destination->startElement('cac:AddressLine');
                                $this->destination->element('cbc:Line', $shipToTradePartyPostalAddressNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );

                        $this->destination->element('cbc:CityName', $this->source->queryValue('./ram:CityName', $shipToTradePartyPostalAddressNode));
                        $this->destination->element('cbc:PostalZone', $this->source->queryValue('./ram:PostcodeCode', $shipToTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CountrySubentity', $this->source->queryValue('./ram:CountrySubDivisionName', $shipToTradePartyPostalAddressNode));

                        $this->source->whenExists(
                            './ram:CountryID',
                            $shipToTradePartyPostalAddressNode,
                            function ($shipToTradePartyPostalAddressCountryNode): void {
                                $this->destination->startElement('cac:Country');
                                $this->destination->element('cbc:IdentificationCode', $shipToTradePartyPostalAddressCountryNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );

                $this->destination->endElement();

                $this->source->whenExists(
                    './ram:Name',
                    $shipToTradePartyNode,
                    function ($shipToTradePartyNameNode): void {
                        $this->destination->startElement('cac:DeliveryParty');
                        $this->destination->startElement('cac:PartyName');
                        $this->destination->element('cbc:Name', $shipToTradePartyNameNode->nodeValue);
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );

                $this->destination->endElement();
            },
            function () use ($invoiceHeaderDelivery): void {
                $this->source->whenExists(
                    './ram:ActualDeliverySupplyChainEvent/ram:OccurrenceDateTime/udt:DateTimeString',
                    $invoiceHeaderDelivery,
                    function ($actualDeliverySupplyChainEventNode): void {
                        $this->destination->startElement('cac:Delivery');
                        $this->destination->element(
                            'cbc:ActualDeliveryDate',
                            $this->convertDateTime(
                                $actualDeliverySupplyChainEventNode->nodeValue,
                                $actualDeliverySupplyChainEventNode->getAttribute('format')
                            )
                        );
                        $this->destination->endElement();
                    }
                );
            }
        );
    }

    /**
     * Converts the payment means of an CII document
     *
     * @return void
     */
    private function convertPaymentMeans(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->queryAll('./ram:SpecifiedTradeSettlementPaymentMeans', $invoiceHeaderSettlement)->forEach(
            function ($paymentMeansNode) use ($invoiceHeaderSettlement): void {
                $this->destination->startElement('cac:PaymentMeans');

                $this->source->whenExists(
                    './ram:TypeCode',
                    $paymentMeansNode,
                    function ($paymentMeansTypeCodeNode) use ($paymentMeansNode): void {
                        $this->destination->startElement('cbc:PaymentMeansCode', $paymentMeansTypeCodeNode->nodeValue);
                        $this->destination->attribute('name', $this->source->queryValue('./ram:Information', $paymentMeansNode));
                        $this->destination->endElement();
                    }
                );

                $this->destination->element('cbc:PaymentID', $this->source->queryValue('./ram:PaymentReference', $invoiceHeaderSettlement));

                $this->source->whenExists(
                    './ram:ApplicableTradeSettlementFinancialCard',
                    $paymentMeansNode,
                    function ($paymentMeansFinancialCardNode): void {
                        $this->destination->startElement('cac:CardAccount');
                        $this->destination->element('cbc:PrimaryAccountNumberID', $this->source->queryValue('./ram:ID', $paymentMeansFinancialCardNode));
                        $this->destination->element('cbc:NetworkID', 'mapped-from-cii');
                        $this->destination->element('cbc:HolderName', $this->source->queryValue('./ram:CardholderName', $paymentMeansFinancialCardNode));
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    './ram:PayeePartyCreditorFinancialAccount',
                    $paymentMeansNode,
                    function ($paymentMeansCreditorFinancialAccountNode) use ($paymentMeansNode): void {
                        $this->destination->startElement('cac:PayeeFinancialAccount');
                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:IBANID', $paymentMeansCreditorFinancialAccountNode));
                        $this->destination->element('cbc:Name', $this->source->queryValue('./ram:AccountName', $paymentMeansCreditorFinancialAccountNode));

                        $this->source->whenExists(
                            './ram:PayeeSpecifiedCreditorFinancialInstitution',
                            $paymentMeansNode,
                            function ($paymentMeansCreditorFinancialInstNode): void {
                                $this->destination->startElement('cac:FinancialInstitutionBranch');
                                $this->destination->element('cbc:ID', $this->source->queryValue('./ram:BICID', $paymentMeansCreditorFinancialInstNode));
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );

                $this->source->whenExists(
                    './ram:SpecifiedTradePaymentTerms/ram:DirectDebitMandateID',
                    $invoiceHeaderSettlement,
                    function ($DirectDebitMandateNode) use ($paymentMeansNode): void {
                        $this->destination->startElement('cac:PaymentMandate');
                        $this->destination->element('cbc:ID', $DirectDebitMandateNode->nodeValue);

                        $this->source->whenExists(
                            './ram:PayerPartyDebtorFinancialAccount',
                            $paymentMeansNode,
                            function ($paymentMeansDebtorFinancialAccountNode): void {
                                $this->destination->startElement('cac:PayerFinancialAccount');
                                $this->destination->element('cbc:ID', $this->source->queryValue('./ram:IBANID', $paymentMeansDebtorFinancialAccountNode));
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );

                $this->destination->endElement();
            }
        );
    }

    /**
     * Converts the payment terms of an CII document
     *
     * @return void
     */
    private function convertPaymentTerms(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:SpecifiedTradePaymentTerms/ram:Description[string-length(text()) > 0]',
            $invoiceHeaderSettlement,
            function ($peymentTermsDescriptionNode): void {
                $this->destination->startElement('cac:PaymentTerms');
                $this->destination->element('cbc:Note', $peymentTermsDescriptionNode->nodeValue);
                $this->destination->endElement();
            }
        );
    }

    /**
     * Converts the allowances/charges on document level of a CII document
     *
     * @return void
     */
    private function convertDocumentLevelAllowanceCharge(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->queryAll('./ram:SpecifiedTradeAllowanceCharge', $invoiceHeaderSettlement)->forEach(
            function ($tradeAllowanceChargeNode) use ($invoiceHeaderSettlement): void {
                $this->destination->startElement('cac:AllowanceCharge');

                $this->destination->element('cbc:ChargeIndicator', $this->source->queryValue('./ram:ChargeIndicator/udt:Indicator', $tradeAllowanceChargeNode));
                $this->destination->element('cbc:AllowanceChargeReasonCode', $this->source->queryValue('./ram:ReasonCode', $tradeAllowanceChargeNode));
                $this->destination->element('cbc:AllowanceChargeReason', $this->source->queryValue('./ram:Reason', $tradeAllowanceChargeNode));
                $this->destination->element('cbc:MultiplierFactorNumeric', $this->source->queryValue('./ram:CalculationPercent', $tradeAllowanceChargeNode));
                $this->destination->elementWithAttribute('cbc:Amount', $this->formatAmount($this->source->queryValue('./ram:ActualAmount', $tradeAllowanceChargeNode)), 'currencyID', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                $this->destination->elementWithAttribute('cbc:BaseAmount', $this->formatAmount($this->source->queryValue('./ram:BasisAmount', $tradeAllowanceChargeNode)), 'currencyID', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));

                $this->source->whenExists(
                    './ram:CategoryTradeTax',
                    $tradeAllowanceChargeNode,
                    function ($tradeAllowanceChargeTaxNode): void {
                        $this->destination->startElement('cac:TaxCategory');
                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:CategoryCode', $tradeAllowanceChargeTaxNode));
                        $this->destination->element('cbc:Percent', $this->source->queryValue('./ram:RateApplicablePercent', $tradeAllowanceChargeTaxNode));
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:TypeCode', $tradeAllowanceChargeTaxNode));
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );

                $this->destination->endElement();
            }
        );
    }

    /**
     * Converts the taxes on document level of a CII document
     *
     * @return void
     */
    private function convertDocumentLevelTax(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $invoiceCurrencyCode = $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement);
        $taxCurrencyCode = $this->source->queryValue('./ram:TaxCurrencyCode', $invoiceHeaderSettlement);

        $this->source->whenExists(
            './ram:ApplicableTradeTax',
            $invoiceHeaderSettlement,
            function () use ($invoiceHeaderSettlement, $invoiceCurrencyCode, $taxCurrencyCode): void {
                $this->destination->startElement('cac:TaxTotal');

                $this->source->whenExists(
                    sprintf("./ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount[@currencyID='%s']", $invoiceCurrencyCode),
                    $invoiceHeaderSettlement,
                    function ($taxTotalAmountNode): void {
                        $this->destination->elementWithAttribute(
                            'cbc:TaxAmount',
                            $this->formatAmount($taxTotalAmountNode->nodeValue),
                            'currencyID',
                            $taxTotalAmountNode->getAttribute('currencyID')
                        );
                    },
                    function () use ($invoiceCurrencyCode): void {
                        $this->destination->elementWithAttribute(
                            'cbc:TaxAmount',
                            '0.0',
                            'currencyID',
                            $invoiceCurrencyCode
                        );
                    }
                );

                $this->source->queryAll('./ram:ApplicableTradeTax', $invoiceHeaderSettlement)->forEach(
                    function ($tradeTaxNode) use ($invoiceHeaderSettlement, $invoiceCurrencyCode): void {
                        $this->destination->startElement('cac:TaxSubtotal');

                        $this->destination->elementWithAttribute(
                            'cbc:TaxableAmount',
                            $this->formatAmount($this->source->queryValue('./ram:BasisAmount', $tradeTaxNode)),
                            'currencyID',
                            $this->source->queryValue('./ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount/@currencyID', $invoiceHeaderSettlement) ?? $invoiceCurrencyCode
                        );
                        $this->destination->elementWithAttribute(
                            'cbc:TaxAmount',
                            $this->formatAmount($this->source->queryValue('./ram:CalculatedAmount', $tradeTaxNode)),
                            'currencyID',
                            $this->source->queryValue('./ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount/@currencyID', $invoiceHeaderSettlement) ?? $invoiceCurrencyCode
                        );

                        $this->destination->startElement('cac:TaxCategory');
                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:CategoryCode', $tradeTaxNode));
                        $this->destination->element('cbc:Percent', $this->source->queryValue('./ram:RateApplicablePercent', $tradeTaxNode));
                        $this->destination->element('cbc:TaxExemptionReasonCode', $this->source->queryValue('./ram:ExemptionReasonCode', $tradeTaxNode));
                        $this->destination->element('cbc:TaxExemptionReason', $this->source->queryValue('./ram:ExemptionReason', $tradeTaxNode));
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:TypeCode', $tradeTaxNode));
                        $this->destination->endElement();
                        $this->destination->endElement();

                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();

                if (null !== $invoiceCurrencyCode && '' !== $invoiceCurrencyCode
                    && null !== $taxCurrencyCode && '' !== $taxCurrencyCode
                    && $invoiceCurrencyCode !== $taxCurrencyCode
                ) {
                    $this->source->whenExists(
                        sprintf("./ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount[@currencyID='%s']", $taxCurrencyCode),
                        $invoiceHeaderSettlement,
                        function ($taxTotalAmountNode): void {
                            $this->destination->startElement('cac:TaxTotal');

                            $this->destination->elementWithAttribute(
                                'cbc:TaxAmount',
                                $this->formatAmount($taxTotalAmountNode->nodeValue),
                                'currencyID',
                                $taxTotalAmountNode->getAttribute('currencyID')
                            );

                            $this->destination->endElement();
                        }
                    );
                }
            }
        );
    }

    /**
     * Converts the document summation of a CII document
     *
     * @return void
     */
    private function convertDocumentSummation(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:SpecifiedTradeSettlementHeaderMonetarySummation',
            $invoiceHeaderSettlement,
            function ($monetarySummationNode) use ($invoiceHeaderSettlement): void {
                $this->destination->startElement('cac:LegalMonetaryTotal');

                $this->destination->elementWithAttribute(
                    'cbc:LineExtensionAmount',
                    $this->formatAmount($this->source->queryValue('./ram:LineTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );

                $this->destination->elementWithAttribute(
                    'cbc:TaxExclusiveAmount',
                    $this->formatAmount($this->source->queryValue('./ram:TaxBasisTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );

                $this->destination->elementWithAttribute(
                    'cbc:TaxInclusiveAmount',
                    $this->formatAmount($this->source->queryValue('./ram:GrandTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );

                $this->destination->elementWithAttribute(
                    'cbc:AllowanceTotalAmount',
                    $this->formatAmount($this->source->queryValue('./ram:AllowanceTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );

                $this->destination->elementWithAttribute(
                    'cbc:ChargeTotalAmount',
                    $this->formatAmount($this->source->queryValue('./ram:ChargeTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );

                $this->destination->elementWithAttribute(
                    'cbc:PrepaidAmount',
                    $this->formatAmount($this->source->queryValue('./ram:TotalPrepaidAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );

                $this->destination->elementWithAttribute(
                    'cbc:PayableRoundingAmount',
                    $this->formatAmount($this->source->queryValue('./ram:RoundingAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );

                $this->destination->elementWithAttribute(
                    'cbc:PayableAmount',
                    $this->formatAmount($this->source->queryValue('./ram:DuePayableAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );

                $this->destination->endElement();
            }
        );
    }

    /**
     * Converts the document lines of a CII document
     *
     * @return void
     */
    private function convertLines(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:IncludedSupplyChainTradeLineItem',
            $invoiceSuppyChainTradeTransaction,
            function () use ($invoiceSuppyChainTradeTransaction, $invoiceHeaderSettlement): void {
                $this->source->queryAll(
                    './ram:IncludedSupplyChainTradeLineItem',
                    $invoiceSuppyChainTradeTransaction
                )->forEach(
                    function ($tradeLineItemNode) use ($invoiceHeaderSettlement): void {
                        $this->destination->startElement($this->getIsCreditNote() ? 'cac:CreditNoteLine' : 'cac:InvoiceLine');

                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:AssociatedDocumentLineDocument/ram:LineID', $tradeLineItemNode));
                        $this->destination->element('cbc:Note', $this->source->queryValue('./ram:AssociatedDocumentLineDocument/ram:IncludedNote/ram:Content', $tradeLineItemNode));

                        $this->destination->elementWithAttribute(
                            $this->getIsCreditNote() ? 'cbc:CreditedQuantity' : 'cbc:InvoicedQuantity',
                            $this->source->queryValue('./ram:SpecifiedLineTradeDelivery/ram:BilledQuantity', $tradeLineItemNode),
                            'unitCode',
                            $this->source->queryValue('./ram:SpecifiedLineTradeDelivery/ram:BilledQuantity/@unitCode', $tradeLineItemNode)
                        );

                        $this->destination->elementWithAttribute(
                            'cbc:LineExtensionAmount',
                            $this->formatAmount($this->source->queryValue('./ram:SpecifiedLineTradeSettlement/ram:SpecifiedTradeSettlementLineMonetarySummation/ram:LineTotalAmount', $tradeLineItemNode)),
                            'currencyID',
                            $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                        );

                        $this->source->whenExists(
                            './ram:SpecifiedLineTradeSettlement/ram:ReceivableSpecifiedTradeAccountingAccount/ram:ID',
                            $tradeLineItemNode,
                            function ($accountingCostNode): void {
                                $this->destination->element('cbc:AccountingCost', $accountingCostNode->nodeValue);
                            }
                        );

                        $this->source->whenExists(
                            './ram:SpecifiedLineTradeSettlement/ram:BillingSpecifiedPeriod',
                            $tradeLineItemNode,
                            function ($billingSpecifiedPeriodNode): void {
                                $this->destination->startElement('cac:InvoicePeriod');
                                $this->destination->element(
                                    'cbc:StartDate',
                                    $this->convertDateTime(
                                        $this->source->queryValue('./ram:StartDateTime/udt:DateTimeString', $billingSpecifiedPeriodNode),
                                        $this->source->queryValue('./ram:StartDateTime/udt:DateTimeString/@format', $billingSpecifiedPeriodNode)
                                    )
                                );
                                $this->destination->element(
                                    'cbc:EndDate',
                                    $this->convertDateTime(
                                        $this->source->queryValue('./ram:EndDateTime/udt:DateTimeString', $billingSpecifiedPeriodNode),
                                        $this->source->queryValue('./ram:EndDateTime/udt:DateTimeString/@format', $billingSpecifiedPeriodNode)
                                    )
                                );
                                $this->destination->endElement();
                            }
                        );

                        $this->source->whenExists(
                            './ram:SpecifiedLineTradeAgreement/ram:BuyerOrderReferencedDocument/ram:LineID',
                            $tradeLineItemNode,
                            function ($buyerOrderReferencedDocumentNode): void {
                                $this->destination->startElement('cac:OrderLineReference');
                                $this->destination->element('cbc:LineID', $buyerOrderReferencedDocumentNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );

                        $this->source->whenExists(
                            './ram:SpecifiedLineTradeSettlement/ram:AdditionalReferencedDocument/ram:IssuerAssignedID',
                            $tradeLineItemNode,
                            function ($additionalReferencedDocumentNode): void {
                                $this->destination->startElement('cac:DocumentReference');
                                $this->destination->element('cbc:ID', $additionalReferencedDocumentNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );

                        $this->source->queryAll('./ram:SpecifiedLineTradeSettlement/ram:SpecifiedTradeAllowanceCharge', $tradeLineItemNode)->forEach(
                            function ($tradeLineItemAllowanceChargeNode) use ($invoiceHeaderSettlement): void {
                                $this->destination->startElement('cac:AllowanceCharge');

                                $this->destination->element('cbc:ChargeIndicator', $this->source->queryValue('./ram:ChargeIndicator/udt:Indicator', $tradeLineItemAllowanceChargeNode));
                                $this->destination->element('cbc:AllowanceChargeReasonCode', $this->source->queryValue('./ram:ReasonCode', $tradeLineItemAllowanceChargeNode));
                                $this->destination->element('cbc:AllowanceChargeReason', $this->source->queryValue('./ram:Reason', $tradeLineItemAllowanceChargeNode));
                                $this->destination->element('cbc:MultiplierFactorNumeric', $this->source->queryValue('./ram:CalculationPercent', $tradeLineItemAllowanceChargeNode));
                                $this->destination->elementWithAttribute('cbc:Amount', $this->formatAmount($this->source->queryValue('./ram:ActualAmount', $tradeLineItemAllowanceChargeNode)), 'currencyID', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                                $this->destination->elementWithAttribute('cbc:BaseAmount', $this->formatAmount($this->source->queryValue('./ram:BasisAmount', $tradeLineItemAllowanceChargeNode)), 'currencyID', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));

                                $this->source->whenExists(
                                    './ram:CategoryTradeTax',
                                    $tradeLineItemAllowanceChargeNode,
                                    function ($tradeLineItemAllowanceChargeTaxNode): void {
                                        $this->destination->startElement('cac:TaxCategory');
                                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:CategoryCode', $tradeLineItemAllowanceChargeTaxNode));
                                        $this->destination->element('cbc:Percent', $this->source->queryValue('./ram:RateApplicablePercent', $tradeLineItemAllowanceChargeTaxNode));
                                        $this->destination->startElement('cac:TaxScheme');
                                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:TypeCode', $tradeLineItemAllowanceChargeTaxNode));
                                        $this->destination->endElement();
                                        $this->destination->endElement();
                                    }
                                );

                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:SpecifiedTradeProduct',
                            $tradeLineItemNode,
                            function ($tradeLineItemProductNode) use ($tradeLineItemNode): void {
                                $this->destination->startElement('cac:Item');

                                $this->destination->element('cbc:Description', $this->source->queryValue('./ram:Description', $tradeLineItemProductNode));
                                $this->destination->element('cbc:Name', $this->source->queryValue('./ram:Name', $tradeLineItemProductNode));

                                $this->source->whenExists(
                                    './ram:BuyerAssignedID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductIdNode): void {
                                        $this->destination->startElement('cac:BuyersItemIdentification');
                                        $this->destination->element('cbc:ID', $tradeLineItemProductIdNode->nodeValue);
                                        $this->destination->endElement();
                                    }
                                );

                                $this->source->whenExists(
                                    './ram:SellerAssignedID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductIdNode): void {
                                        $this->destination->startElement('cac:SellersItemIdentification');
                                        $this->destination->element('cbc:ID', $tradeLineItemProductIdNode->nodeValue);
                                        $this->destination->endElement();
                                    }
                                );

                                $this->source->whenExists(
                                    './ram:GlobalID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductGlobalIdNode): void {
                                        $this->destination->startElement('cac:StandardItemIdentification');
                                        $this->destination->elementWithAttribute('cbc:ID', $tradeLineItemProductGlobalIdNode->nodeValue, 'schemeID', $tradeLineItemProductGlobalIdNode->getAttribute('schemeID'));
                                        $this->destination->endElement();
                                    }
                                );

                                $this->source->whenExists(
                                    './ram:OriginTradeCountry/ram:ID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductOriginTradeCountryNode): void {
                                        $this->destination->startElement('cac:OriginCountry');
                                        $this->destination->element('cbc:IdentificationCode', $tradeLineItemProductOriginTradeCountryNode->nodeValue);
                                        $this->destination->endElement();
                                    }
                                );

                                $this->source->whenExists(
                                    './ram:DesignatedProductClassification/ram:ClassCode',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductClassificationNode): void {
                                        $this->destination->startElement('cac:CommodityClassification');
                                        $this->destination->elementWithMultipleAttributes('cbc:ItemClassificationCode', $tradeLineItemProductClassificationNode->nodeValue, ['listID' => $tradeLineItemProductClassificationNode->getAttribute('listID'), 'listVersionID' => $tradeLineItemProductClassificationNode->getAttribute('listVersionID')]);
                                        $this->destination->endElement();
                                    }
                                );

                                $this->source->whenExists(
                                    './ram:SpecifiedLineTradeSettlement',
                                    $tradeLineItemNode,
                                    function ($tradeLineItemSettlementNode): void {
                                        $this->source->whenExists(
                                            './ram:ApplicableTradeTax',
                                            $tradeLineItemSettlementNode,
                                            function ($tradeLineItemSettlementTaxNode): void {
                                                $this->destination->startElement('cac:ClassifiedTaxCategory');
                                                $this->destination->element('cbc:ID', $this->source->queryValue('ram:CategoryCode', $tradeLineItemSettlementTaxNode));
                                                $this->destination->element('cbc:Percent', $this->source->queryValue('ram:RateApplicablePercent', $tradeLineItemSettlementTaxNode));
                                                $this->destination->startElement('cac:TaxScheme');
                                                $this->destination->element('cbc:ID', $this->source->queryValue('ram:TypeCode', $tradeLineItemSettlementTaxNode));
                                                $this->destination->endElement();
                                                $this->destination->endElement();
                                            }
                                        );
                                    }
                                );

                                $this->source->whenExists(
                                    './ram:SpecifiedTradeProduct',
                                    $tradeLineItemNode,
                                    function ($tradeLineItemProductNode): void {
                                        $this->source->whenExists(
                                            './ram:ApplicableProductCharacteristic',
                                            $tradeLineItemProductNode,
                                            function ($tradeLineProductCharacteristicNode): void {
                                                $this->destination->startElement('cac:AdditionalItemProperty');
                                                $this->destination->element('cbc:Name', $this->source->queryValue('./ram:Description', $tradeLineProductCharacteristicNode));
                                                $this->destination->element('cbc:Value', $this->source->queryValue('./ram:Value', $tradeLineProductCharacteristicNode));
                                                $this->destination->endElement();
                                            }
                                        );
                                    }
                                );

                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:SpecifiedLineTradeAgreement',
                            $tradeLineItemNode,
                            function ($tradeLineItemAgreementNode) use ($invoiceHeaderSettlement): void {
                                $this->destination->startElement('cac:Price');
                                $this->destination->elementWithAttribute('cbc:PriceAmount', $this->formatAmount($this->source->queryValue('./ram:NetPriceProductTradePrice/ram:ChargeAmount', $tradeLineItemAgreementNode)), 'currencyID', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                                $this->destination->elementWithAttribute('cbc:BaseQuantity', $this->source->queryValue('./ram:NetPriceProductTradePrice/ram:BasisQuantity', $tradeLineItemAgreementNode), 'unitCode', $this->source->queryValue('./ram:NetPriceProductTradePrice/ram:BasisQuantity/@unitCode', $tradeLineItemAgreementNode));
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
            }
        );
    }

    /**
     * Converts to UBL date format
     *
     * @param  null|string $dateTimeString
     * @param  null|string $format
     * @return null|string
     *
     * @throws Exception
     * @throws ValueError
     */
    private function convertDateTime(?string $dateTimeString, ?string $format): ?string
    {
        $dateTime = $this->toDateTime($dateTimeString, $format);

        if (false === $dateTime) {
            return null;
        }

        return $dateTime->format('Y-m-d');
    }

    /**
     * Convert to datetime
     *
     * @param  null|string    $dateTimeString
     * @param  null|string    $format
     * @return DateTime|false
     *
     * @throws Exception
     * @throws ValueError
     */
    private function toDateTime(?string $dateTimeString, ?string $format)
    {
        if (is_null($dateTimeString) || is_null($format)) {
            return false;
        }

        if ('102' === $format) {
            return DateTime::createFromFormat('Ymd', $dateTimeString);
        }

        if ('101' === $format) {
            return DateTime::createFromFormat('ymd', $dateTimeString);
        }

        if ('201' === $format) {
            return DateTime::createFromFormat('ymdHi', $dateTimeString);
        }

        if ('202' === $format) {
            return DateTime::createFromFormat('ymdHis', $dateTimeString);
        }

        if ('203' === $format) {
            return DateTime::createFromFormat('YmdHi', $dateTimeString);
        }

        if ('204' === $format) {
            return DateTime::createFromFormat('YmdHis', $dateTimeString);
        }

        throw new Exception($format);
    }
}
