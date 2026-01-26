<?php

namespace Omnipay\MobilPay\Api\Request;

/*
 * Class Mobilpay_Payment_Request_Notify
 * This class can be used for accessing payment info sent by mobilpay.ro in confirmation process
 * @copyright NETOPIA System
 * @author Claudiu Tudose
 * @version 1.0
 */

use DOMDocument;
use DOMElement;
use Exception;
use Omnipay\MobilPay\Api\Address;

class Notify
{
    public const ERROR_LOAD_FROM_XML_CRC_ATTR_MISSING = 0x60000001;

    public const ERROR_LOAD_FROM_XML_ACTION_ELEM_MISSING = 0x60000002;

    public $purchaseId;

    public $action;

    public $errorCode;

    public $errorMessage;

    public $timestamp;

    public $originalAmount;

    public $processedAmount;

    public $current_payment_count = 1;

    public $customer;

    public $issuer;

    private $_crc;

    public function loadFromXml(DOMElement $domElement): void
    {
        $attr = $domElement->attributes->getNamedItem('timestamp');
        if ($attr != null) {
            $this->timestamp = $attr->nodeValue;
        }

        $attr = $domElement->attributes->getNamedItem('crc');
        if ($attr == null) {
            throw new Exception('Mobilpay_Payment_Request_Notify::loadFromXml failed; mandatory crc attribute missing', self::ERROR_LOAD_FROM_XML_CRC_ATTR_MISSING);
        }

        $this->_crc = $attr->nodeValue;

        $elems = $domElement->getElementsByTagName('action');
        if ($elems->length != 1) {
            throw new Exception('Mobilpay_Payment_Request_Notify::loadFromXml failed; mandatory action attribute missing', self::ERROR_LOAD_FROM_XML_ACTION_ELEM_MISSING);
        }

        $this->action = $elems->item(0)->nodeValue;

        $elems = $domElement->getElementsByTagName('customer');
        if ($elems->length == 1) {
            $this->customer = new Address($elems->item(0));
        }

        $elems = $domElement->getElementsByTagName('issuer');
        if ($elems->length == 1) {
            $this->issuer = $elems->item(0)->nodeValue;
        }

        $elems = $domElement->getElementsByTagName('purchase');
        if ($elems->length == 1) {
            $this->purchaseId = $elems->item(0)->nodeValue;
        }

        $elems = $domElement->getElementsByTagName('original_amount');
        if ($elems->length == 1) {
            $this->originalAmount = $elems->item(0)->nodeValue;
        }

        $elems = $domElement->getElementsByTagName('processed_amount');
        if ($elems->length == 1) {
            $this->processedAmount = $elems->item(0)->nodeValue;
        }

        $elems = $domElement->getElementsByTagName('current_payment_count');
        if ($elems->length == 1) {
            $this->current_payment_count = $elems->item(0)->nodeValue;
        }

        $elems = $domElement->getElementsByTagName('error');
        if ($elems->length == 1) {
            $xmlErrorElem = $elems->item(0);

            $attr = $xmlErrorElem->attributes->getNamedItem('code');
            if ($attr != null) {
                $this->errorCode = $attr->nodeValue;
            }

            $this->errorMessage = $xmlErrorElem->nodeValue;
        }
    }

    public function _loadFromQueryString($queryString): void
    {
        $parameters = explode('&', $queryString);
        $reqParams = [];
        foreach ($parameters as $parameter) {
            [$key, $value] = explode('=', $parameter);
            $reqParams[$key] = urldecode($value);
        }

        $this->purchaseId = $reqParams['mobilpay_refference_id'] ?? null;
        $this->action = $reqParams['mobilpay_refference_action'] ?? null;
        $this->originalAmount = $reqParams['mobilpay_refference_original_amount'] ?? null;
        $this->processedAmount = $reqParams['mobilpay_refference_processed_amount'] ?? null;
        $this->current_payment_count = $reqParams['mobilpay_refference_current_payment_count'] ?? null;
        $this->errorCode = $reqParams['mobilpay_refference_error_code'] ?? null;
        $this->errorMessage = $reqParams['mobilpay_refference_error_message'] ?? null;
        $this->timestamp = $reqParams['mobilpay_refference_timestamp'] ?? null;
    }

    public function createXmlElement(DOMDocument $domDocument)
    {
        $xmlNotifyElem = $domDocument->createElement('mobilpay');

        $attr = $domDocument->createAttribute('timestamp');
        $attr->nodeValue = date('YmdHis');

        $xmlNotifyElem->appendChild($attr);

        $this->_crc = md5(random_int(0, mt_getrandmax()) . time());
        $attr = $domDocument->createAttribute('crc');
        $attr->nodeValue = $this->_crc;

        $xmlNotifyElem->appendChild($attr);

        $elem = $domDocument->createElement('action');
        $elem->nodeValue = $this->action;

        $xmlNotifyElem->appendChild($elem);

        if ($this->customer instanceof Address) {
            $xmlNotifyElem->appendChild($this->customer->createXmlElement($domDocument, 'customer'));
        }

        $elem = $domDocument->createElement('purchase');
        $elem->nodeValue = $this->purchaseId;

        $xmlNotifyElem->appendChild($elem);

        if ($this->originalAmount != null) {
            $elem = $domDocument->createElement('original_amount');
            $elem->nodeValue = $this->originalAmount;
            $xmlNotifyElem->appendChild($elem);
        }

        if ($this->processedAmount != null) {
            $elem = $domDocument->createElement('processed_amount');
            $elem->nodeValue = $this->processedAmount;
            $xmlNotifyElem->appendChild($elem);
        }

        if ($this->current_payment_count != null) {
            $elem = $domDocument->createElement('current_payment_count');
            $elem->nodeValue = $this->current_payment_count;
            $xmlNotifyElem->appendChild($elem);
        }

        if ($this->issuer != null) {
            $elem = $domDocument->createElement('issuer');
            $elem->nodeValue = $this->issuer;
            $xmlNotifyElem->appendChild($elem);
        }

        $elem = $domDocument->createElement('error');
        $attr = $domDocument->createAttribute('code');
        $attr->nodeValue = $this->errorCode;

        $elem->appendChild($attr);
        $elem->appendChild($domDocument->createCDATASection($this->errorMessage));

        $xmlNotifyElem->appendChild($elem);

        return $xmlNotifyElem;
    }

    public function getCrc()
    {
        return $this->_crc;
    }
}
