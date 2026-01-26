<?php

namespace Omnipay\MobilPay\Api;

/*
 * Class Mobilpay_Payment_Address
 * @copyright NETOPIA System
 * @author Claudiu Tudose
 * @version 1.0
 */

use DOMNode;
use Exception;
use DOMDocument;

class Address
{
    public const TYPE_COMPANY = 'company';

    public const TYPE_PERSON = 'person';

    public const ERROR_INVALID_PARAMETER = 0x11100001;

    public const ERROR_INVALID_ADDRESS_TYPE = 0x11100002;

    public const ERROR_INVALID_ADDRESS_TYPE_VALUE = 0x11100003;

    public $type;

    public $firstName;

    public $lastName;

    public $fiscalNumber;

    public $identityNumber;

    public $country;

    public $county;

    public $city;

    public $zipCode;

    public $address;

    public $email;

    public $mobilePhone;

    public $bank;

    public $iban;

    public function __construct(?DOMNode $domNode = null)
    {
        if ($domNode != null) {
            $this->loadFromXml($domNode);
        }
    }

    public function createXmlElement(DOMDocument $domDocument, $nodeName)
    {
        $addrElem = $domDocument->createElement($nodeName);

        if ($this->type == null) {
            throw new Exception('Invalid address type', self::ERROR_INVALID_ADDRESS_TYPE);
        }

        if ($this->type != self::TYPE_COMPANY && $this->type != self::TYPE_PERSON) {
            throw new Exception('Invalid address type', self::ERROR_INVALID_ADDRESS_TYPE_VALUE);
        }

        $xmlAttr = $domDocument->createAttribute('type');
        $xmlAttr->nodeValue = $this->type;

        $addrElem->appendChild($xmlAttr);

        if ($this->firstName != null) {
            $xmlElem = $domDocument->createElement('first_name');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->firstName)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->lastName != null) {
            $xmlElem = $domDocument->createElement('last_name');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->lastName)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->fiscalNumber != null) {
            $xmlElem = $domDocument->createElement('fiscal_number');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->fiscalNumber)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->identityNumber != null) {
            $xmlElem = $domDocument->createElement('identity_number');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->identityNumber)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->country != null) {
            $xmlElem = $domDocument->createElement('country');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->country)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->county != null) {
            $xmlElem = $domDocument->createElement('county');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->county)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->city != null) {
            $xmlElem = $domDocument->createElement('city');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->city)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->zipCode != null) {
            $xmlElem = $domDocument->createElement('zip_code');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->zipCode)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->address != null) {
            $xmlElem = $domDocument->createElement('address');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->address)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->email != null) {
            $xmlElem = $domDocument->createElement('email');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->email)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->mobilePhone != null) {
            $xmlElem = $domDocument->createElement('mobile_phone');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->mobilePhone)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->bank != null) {
            $xmlElem = $domDocument->createElement('bank');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->bank)));
            $addrElem->appendChild($xmlElem);
        }

        if ($this->iban != null) {
            $xmlElem = $domDocument->createElement('iban');
            $xmlElem->appendChild($domDocument->createCDATASection(urlencode($this->iban)));
            $addrElem->appendChild($xmlElem);
        }

        return $addrElem;
    }

    public function toArray()
    {
        return [
            'ppiFirstName' => $this->firstName,
            'ppiLastName' => $this->lastName,
            'ppiCountry' => $this->country,
            'ppiCounty' => $this->county,
            'ppiCity' => $this->city,
            'ppiPostalCode' => $this->zipCode,
            'ppiAddress' => $this->address,
            'ppiEmail' => $this->email,
            'ppiPhone' => $this->mobilePhone,
            'ppiBank' => $this->bank,
            'ppiIban' => $this->iban,
            'ppiFiscalNumber' => $this->fiscalNumber,
            'ppiIdentityNumber' => $this->identityNumber
        ];
    }

    protected function loadFromXml(DOMNode $domNode): void
    {
        $attr = $domNode->attributes->getNamedItem('type');
        $this->type = $attr != null ? $attr->nodeValue : self::TYPE_PERSON;

        $elems = $domNode->getElementsByTagName('first_name');
        if ($elems->length == 1) {
            $this->firstName = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('last_name');
        if ($elems->length == 1) {
            $this->lastName = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('fiscal_number');
        if ($elems->length == 1) {
            $this->fiscalNumber = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('identity_number');
        if ($elems->length == 1) {
            $this->identityNumber = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('country');
        if ($elems->length == 1) {
            $this->country = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('county');
        if ($elems->length == 1) {
            $this->county = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('city');
        if ($elems->length == 1) {
            $this->city = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('zip_code');
        if ($elems->length == 1) {
            $this->zipCode = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('address');
        if ($elems->length == 1) {
            $this->address = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('email');
        if ($elems->length == 1) {
            $this->email = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('mobile_phone');
        if ($elems->length == 1) {
            $this->mobilePhone = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('bank');
        if ($elems->length == 1) {
            $this->bank = urldecode($elems->item(0)->nodeValue);
        }

        $elems = $domNode->getElementsByTagName('iban');
        if ($elems->length == 1) {
            $this->iban = urldecode($elems->item(0)->nodeValue);
        }
    }
}
