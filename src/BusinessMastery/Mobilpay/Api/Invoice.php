<?php

namespace Omnipay\MobilPay\Api;

/*
 * Class Invoice
 * @copyright NETOPIA System
 * @author Claudiu Tudose
 * @version 1.0
 */

use DOMNode;
use Exception;
use DOMDocument;

class Invoice
{
    public const ERROR_INVALID_PARAMETER = 0x11110001;

    public const ERROR_INVALID_CURRENCY = 0x11110002;

    public const ERROR_ITEM_INSERT_INVALID_INDEX = 0x11110003;

    public const ERROR_LOAD_FROM_XML_CURRENCY_ATTR_MISSING = 0x31110001;

    public $currency;

    public $amount;

    public $details;

    public $installments;

    public $selectedInstallments;

    protected $billingAddress;

    protected $shippingAddress;

    protected $items = [];

    protected $exchangeRates = [];

    public function __construct(?DOMNode $domNode = null)
    {
        if ($domNode != null) {
            $this->loadFromXml($domNode);
        }
    }

    public function createXmlElement(DOMDocument $domDocument)
    {
        $xmlInvElem = $domDocument->createElement('invoice');

        if ($this->currency == null) {
            throw new Exception('Invalid currency', self::ERROR_INVALID_CURRENCY);
        }

        $xmlAttr = $domDocument->createAttribute('currency');
        $xmlAttr->nodeValue = $this->currency;

        $xmlInvElem->appendChild($xmlAttr);

        if ($this->amount != null) {
            $xmlAttr = $domDocument->createAttribute('amount');
            $xmlAttr->nodeValue = sprintf('%.02f', (float) ($this->amount));
            $xmlInvElem->appendChild($xmlAttr);
        }

        if ($this->installments != null) {
            $xmlAttr = $domDocument->createAttribute('installments');
            $xmlAttr->nodeValue = $this->installments;
            $xmlInvElem->appendChild($xmlAttr);
        }

        if ($this->selectedInstallments != null) {
            $xmlAttr = $domDocument->createAttribute('selected_installments');
            $xmlAttr->nodeValue = $this->selectedInstallments;
            $xmlInvElem->appendChild($xmlAttr);
        }

        if ($this->details != null) {
            $xmlElem = $domDocument->createElement('details');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->details)));
            $xmlInvElem->appendChild($xmlElem);
        }

        if (($this->billingAddress instanceof Address) || ($this->shippingAddress instanceof Address)) {
            $xmlAddr = null;
            if ($this->billingAddress instanceof Address) {
                try {
                    $xmlElem = $this->billingAddress->createXmlElement($domDocument, 'billing');
                    $xmlAddr = $domDocument->createElement('contact_info');

                    $xmlAddr->appendChild($xmlElem);
                } catch (Exception $e) {
                }
            }

            if ($this->shippingAddress instanceof Address) {
                try {
                    $xmlElem = $this->shippingAddress->createXmlElement($domDocument, 'shipping');
                    if ($xmlAddr == null) {
                        $xmlAddr = $domDocument->createElement('contact_info');
                    }

                    $xmlAddr->appendChild($xmlElem);
                } catch (Exception $e) {
                }
            }

            if ($xmlAddr != null) {
                $xmlInvElem->appendChild($xmlAddr);
            }
        }

        return $xmlInvElem;
    }

    public function setBillingAddress(Address $address)
    {
        $this->billingAddress = $address;

        return $this;
    }

    public function setShippingAddress(Address $address)
    {
        $this->shippingAddress = $address;

        return $this;
    }

    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    protected function loadFromXml(DOMNode $domNode): void
    {
        $attr = $domNode->attributes->getNamedItem('currency');
        if ($attr == null) {
            throw new Exception('Invoice::loadFromXml failed; currency attribute missing', self::ERROR_LOAD_FROM_XML_CURRENCY_ATTR_MISSING);
        }

        $this->currency = $attr->nodeValue;

        $attr = $domNode->attributes->getNamedItem('amount');
        if ($attr != null) {
            $this->amount = $attr->nodeValue;
        }

        $attr = $domNode->attributes->getNamedItem('installments');
        if ($attr != null) {
            $this->installments = $attr->nodeValue;
        }

        $attr = $domNode->attributes->getNamedItem('selected_installments');
        if ($attr != null) {
            $this->selectedInstallments = $attr->nodeValue;
        }

        $elems = $domNode->getElementsByTagName('details');
        if ($elems->length == 1) {
            $this->details = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('contact_info');
        if ($elems->length == 1) {
            $addrElem = $elems->item(0);

            $elems = $addrElem->getElementsByTagName('billing');
            if ($elems->length == 1) {
                $this->billingAddress = new Address($elems->item(0));
            }

            $elems = $addrElem->getElementsByTagName('shipping');
            if ($elems->length == 1) {
                $this->shippingAddress = new Address($elems->item(0));
            }
        }
    }
}
