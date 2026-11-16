<?php

namespace Omnipay\MobilPay\Message;

use stdClass;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * MobilPay Complete Purchase Response.
 */
class CompletePurchaseResponse extends AbstractResponse
{
    /**
     * @var string
     */
    protected $action;

    /**
     * @var stdClass
     */
    protected $responseError;

    /**
     * @var string
     */
    protected $status;

    /**
     * @param Omnipay\Common\Message\RequestInterface $request
     * @param array $data
     * @param stdClass $responseError
     */
    public function __construct(RequestInterface $request, $data, $responseError)
    {
        parent::__construct($request, $data);

        $this->request = $request;
        $this->responseError = $responseError;

        if (isset($data['objPmNotify']['action'])) {
            $this->action = $data['objPmNotify']['action'];
        }
    }

    /**
     * Returns whether the transaction was successful.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->action == 'confirmed';
    }

    /**
     * Returns whether the transaction is pending.
     *
     * @return bool
     */
    public function isPending()
    {
        return in_array($this->action, ['confirmed_pending', 'paid_pending', 'paid']);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->action;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Build the IPN acknowledgement body.
     *
     * @return string
     */
    public function getCrcResponse(): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $message = htmlspecialchars((string) $this->responseError->message, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        if ($this->responseError->code == 0) {
            $xml .= "<crc>{$message}</crc>";
        } else {
            $xml .= "<crc error_type=\"{$this->responseError->type}\" error_code=\"{$this->responseError->code}\">{$message}</crc>";
        }

        return $xml;
    }

    /**
     * Send IPN response.
     *
     * @deprecated Use getCrcResponse() and return it as an HTTP response; header()/echo bypasses the framework response lifecycle.
     *
     * @return void
     */
    public function sendResponse(): void
    {
        header('Content-type: application/xml');
        echo $this->getCrcResponse();
    }
}
