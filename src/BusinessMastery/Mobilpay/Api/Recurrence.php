<?php

namespace Omnipay\MobilPay\Api;

use DOMNode;
use Exception;
use DOMDocument;

class Recurrence
{
    public const ERROR_INVALID_PARAMETER = 0x11110001;

    public const ERROR_INVALID_INTERVAL_DAY = 0x11110002;

    public const ERROR_INVALID_PAYMENTS_NO = 0x11110003;

    public const ERROR_LOAD_FROM_XML_CURRENCY_ATTR_MISSING = 0x31110001;

    public $interval_day;

    public $payments_no;

    public function __construct(?DOMNode $domNode = null)
    {
        if ($domNode != null) {
            $this->loadFromXml($domNode);
        }
    }

    public function createXmlElement(DOMDocument $domDocument)
    {
        $xmlInvElem = $domDocument->createElement('recurrence');

        if (is_null($this->interval_day)) {
            throw new Exception('Invalid interval_day', self::ERROR_INVALID_INTERVAL_DAY);
        }

        if (is_null($this->payments_no)) {
            throw new Exception('Invalid payments_no', self::ERROR_INVALID_PAYMENTS_NO);
        }

        $xmlAttr = $domDocument->createAttribute('payments_no');
        $xmlAttr->nodeValue = $this->payments_no;

        $xmlInvElem->appendChild($xmlAttr);

        $xmlAttr = $domDocument->createAttribute('interval_day');
        $xmlAttr->nodeValue = $this->interval_day;

        $xmlInvElem->appendChild($xmlAttr);

        return $xmlInvElem;
    }

    protected function loadFromXml(DOMNode $domNode): void
    {
        $attr = $domNode->attributes->getNamedItem('interval_day');

        if (is_null($attr)) {
            throw new Exception('Mobilpay_Payment_Recurrence::loadFromXml failed; interval_day attribute missing', self::ERROR_LOAD_FROM_XML_CURRENCY_ATTR_MISSING);
        }

        $this->interval_day = $attr->value;

        $attr = $domNode->attributes->getNamedItem('payments_no');
        if (is_null($attr)) {
            throw new Exception('Mobilpay_Payment_Recurrence::loadFromXml failed; payments_no attribute missing', self::ERROR_LOAD_FROM_XML_CURRENCY_ATTR_MISSING);
        }

        $this->payments_no = $attr->value;
    }
}
