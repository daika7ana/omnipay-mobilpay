<?php

namespace Omnipay\MobilPay\Api\Invoice;

/**
 * Class Mobilpay_Payment_Invoice_Item.
 * @copyright NETOPIA System
 * @author Claudiu Tudose
 * @version 1.0
 */
class Item
{
    public const ERROR_INVALID_PARAMETER = 0x11111001;

    public const ERROR_INVALID_PROPERTY = 0x11110002;

    public const ERROR_LOAD_FROM_XML_CODE_ELEM_MISSING = 0x40000001;

    public const ERROR_LOAD_FROM_XML_NAME_ELEM_MISSING = 0x40000002;

    public const ERROR_LOAD_FROM_XML_QUANTITY_ELEM_MISSING = 0x40000003;

    public const ERROR_LOAD_FROM_XML_QUANTITY_ELEM_EMPTY = 0x40000004;

    public const ERROR_LOAD_FROM_XML_PRICE_ELEM_MISSING = 0x40000005;

    public const ERROR_LOAD_FROM_XML_PRICE_ELEM_EMPTY = 0x40000006;

    public const ERROR_LOAD_FROM_XML_VAT_ELEM_MISSING = 0x40000007;

    public $code = null;

    public $name = null;

    public $measurment = null;

    public $quantity = null;

    public $price = null;

    public $vat = null;

    public function __construct(DOMNode $elem = null)
    {
        if ($elem != null) {
            $this->loadFromXml($elem);
        }
    }

    public function createXmlElement(DOMDocument $xmlDoc)
    {
        if (!($xmlDoc instanceof DOMDocument)) {
            throw new Exception('', self::ERROR_INVALID_PARAMETER);
        }

        $xmlItemElem = $xmlDoc->createElement('item');

        if ($this->code == null || $this->name == null || $this->measurment == null || $this->quantity == null || $this->price == null || $this->vat == null) {
            throw new Exception('Invalid property', self::ERROR_INVALID_PROPERTY);
        }

        $xmlElem = $xmlDoc->createElement('code');
        $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->code)));
        $xmlItemElem->appendChild($xmlElem);

        $xmlElem = $xmlDoc->createElement('name');
        $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->name)));
        $xmlItemElem->appendChild($xmlElem);

        $xmlElem = $xmlDoc->createElement('measurment');
        $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->measurment)));
        $xmlItemElem->appendChild($xmlElem);

        $xmlElem = $xmlDoc->createElement('quantity');
        $xmlElem->nodeValue = $this->quantity;
        $xmlItemElem->appendChild($xmlElem);

        $xmlElem = $xmlDoc->createElement('price');
        $xmlElem->nodeValue = $this->price;
        $xmlItemElem->appendChild($xmlElem);

        $xmlElem = $xmlDoc->createElement('vat');
        $xmlElem->nodeValue = $this->vat;
        $xmlItemElem->appendChild($xmlElem);

        return $xmlItemElem;
    }

    public function getTotalAmmount()
    {
        $value = round($this->price * $this->quantity, 2);
        $vat = round($value * $this->vat, 2);

        return $value + $vat;
    }

    protected function loadFromXml(DOMNode $elem)
    {
        $elems = $elem->getElementsByTagName('code');
        if ($elems->length != 1) {
            throw new Exception('Mobilpay_Payment_Invoice_Item::loadFromXml failed! Invalid code element.', self::ERROR_LOAD_FROM_XML_CODE_ELEM_MISSING);
        }
        $this->code = urldecode($elems->item(0)->nodeValue);

        $elems = $elem->getElementsByTagName('name');
        if ($elems->length != 1) {
            throw new Exception('Mobilpay_Payment_Invoice_Item::loadFromXml failed! Invalid name element.', self::ERROR_LOAD_FROM_XML_NAME_ELEM_MISSING);
        }
        $this->name = urldecode($elems->item(0)->nodeValue);

        $elems = $elem->getElementsByTagName('measurment');
        if ($elems->length == 1) {
            $this->measurment = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $elem->getElementsByTagName('quantity');
        if ($elems->length != 1) {
            throw new Exception('Mobilpay_Payment_Invoice_Item::loadFromXml failed! Invalid quantity element.', self::ERROR_LOAD_FROM_XML_QUANTITY_ELEM_MISSING);
        }
        $this->quantity = (float) (urldecode($elems->item(0)->nodeValue));
        if ($this->quantity == 0) {
            throw new Exception('Mobilpay_Payment_Invoice_Item::loadFromXml failed! Invalid quantity value=' . $this->quantity, self::ERROR_LOAD_FROM_XML_QUANTITY_ELEM_EMPTY);
        }

        $elems = $elem->getElementsByTagName('price');
        if ($elems->length != 1) {
            throw new Exception('Mobilpay_Payment_Invoice_Item::loadFromXml failed! Invalid price element.', self::ERROR_LOAD_FROM_XML_PRICE_ELEM_MISSING);
        }
        $this->price = (float) (urldecode($elems->item(0)->nodeValue));
        if ($this->price == 0) {
            throw new Exception('Mobilpay_Payment_Invoice_Item::loadFromXml failed! Invalid price value=' . $this->price, self::ERROR_LOAD_FROM_XML_PRICE_ELEM_EMPTY);
        }

        $elems = $elem->getElementsByTagName('vat');
        if ($elems->length != 1) {
            throw new Exception('Mobilpay_Payment_Invoice_Item::loadFromXml failed! Invalid vat element.', self::ERROR_LOAD_FROM_XML_VAT_ELEM_MISSING);
        }
        $this->vat = (float) (urldecode($elems->item(0)->nodeValue));

        return $this;
    }
}
