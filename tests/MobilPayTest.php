<?php

namespace Omnipay\MobilPay\Tests;

use PHPUnit\Framework\TestCase;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\MobilPay\Api\Request\AbstractRequest;
use Omnipay\MobilPay\Api\Request\Card;
use Omnipay\MobilPay\Gateway;
use Omnipay\MobilPay\Message\CompletePurchaseResponse;
use Psr\Http\Message\ResponseInterface;

class MobilPayTest extends TestCase
{
    private string $keyPath;

    private string $certPath;

    protected function setUp(): void
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($privateKey);

        $csr = openssl_csr_new(['commonName' => 'omnipay-mobilpay-test'], $privateKey);
        $this->assertNotFalse($csr);

        $certificate = openssl_csr_sign($csr, null, $privateKey, 365);
        $this->assertNotFalse($certificate);

        openssl_x509_export($certificate, $certPem);
        openssl_pkey_export($privateKey, $keyPem);

        $this->certPath = sys_get_temp_dir() . '/omnipay-mobilpay-' . bin2hex(random_bytes(8)) . '.cer';
        $this->keyPath = sys_get_temp_dir() . '/omnipay-mobilpay-' . bin2hex(random_bytes(8)) . '.key';

        file_put_contents($this->certPath, $certPem);
        file_put_contents($this->keyPath, $keyPem);
    }

    protected function tearDown(): void
    {
        @unlink($this->certPath);
        @unlink($this->keyPath);
    }

    private function purchaseParameters(): array
    {
        return [
            'amount' => '10.00',
            'currency' => 'RON',
            'orderId' => 'ORDER1',
            'confirmUrl' => 'https://example.com/ipn',
            'returnUrl' => 'https://example.com/return',
            'details' => 'Test payment',
        ];
    }

    private function gateway(): Gateway
    {
        // Standalone package: no PSR-18 implementation is installed, and these tests never send HTTP.
        $client = new class implements ClientInterface {
            public function request(
                $method,
                $uri,
                array $headers = [],
                $body = null,
                $protocolVersion = '1.1',
            ): ResponseInterface {
                throw new \RuntimeException('No HTTP client in tests');
            }
        };

        $gateway = new Gateway($client);
        $gateway->setMerchantId('1234-5678-9012-3456-7890');
        $gateway->setPublicKey($this->certPath);

        return $gateway;
    }

    public function testPurchaseResponseRedirectsWithPostFormData(): void
    {
        $response = $this->gateway()->purchase($this->purchaseParameters())->send();

        $this->assertTrue($response->isRedirect());
        $this->assertSame('POST', $response->getRedirectMethod());

        $data = $response->getRedirectData();
        $this->assertNotEmpty($data['env_key']);
        $this->assertNotEmpty($data['data']);
        $this->assertNotEmpty($data['cipher']);
        $this->assertNotEmpty($data['iv']);
        $this->assertSame('aes-256-cbc', $data['cipher']);
    }

    public function testCipherAndIvPostFieldsAreMapped(): void
    {
        $request = $this->gateway()->completePurchase([
            'env_key' => 'x',
            'data' => 'y',
            'cipher' => 'aes-256-cbc',
            'iv' => 'AQID',
        ]);

        $this->assertSame('aes-256-cbc', $request->getIpnCipher());
        $this->assertSame('AQID', $request->getIpnIv());
    }

    public function testFactoryFromEncryptedDecryptsAes256PayloadWithIv(): void
    {
        $xml = '<order type="card" id="ORDER1" timestamp="20260101000000">'
            . '<signature>sig</signature>'
            . '<service></service>'
            . '<invoice currency="RON" amount="10.00"><details>x</details></invoice>'
            . '</order>';

        $publicKey = openssl_pkey_get_public('file://' . $this->certPath);
        $this->assertNotFalse($publicKey);

        $sealed = '';
        $envKeys = [];
        $iv = '';
        $result = openssl_seal($xml, $sealed, $envKeys, [$publicKey], 'aes-256-cbc', $iv);
        $this->assertNotFalse($result);

        $request = AbstractRequest::factoryFromEncrypted(
            base64_encode($envKeys[0]),
            base64_encode($sealed),
            $this->keyPath,
            null,
            'aes-256-cbc',
            base64_encode($iv),
        );

        $this->assertInstanceOf(Card::class, $request);
        $this->assertSame('ORDER1', $request->orderId);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function sealXml(string $xml, string $cipher = 'aes-256-cbc'): array
    {
        $publicKey = openssl_pkey_get_public('file://' . $this->certPath);
        $this->assertNotFalse($publicKey);

        $sealed = '';
        $envKeys = [];
        $iv = '';
        $result = openssl_seal($xml, $sealed, $envKeys, [$publicKey], $cipher, $iv);
        $this->assertNotFalse($result);

        return [$envKeys[0], $sealed, $iv];
    }

    private function ipnOrderXml(bool $withNotify, string $action = 'confirmed', string $crc = 'crc-value'): string
    {
        $notify = $withNotify
            ? '<mobilpay timestamp="20260101000000" crc="' . $crc . '">'
                . '<action>' . $action . '</action>'
                . '<purchase>ORDER1</purchase>'
                . '<original_amount>10.00</original_amount>'
                . '<processed_amount>10.00</processed_amount>'
                . '<current_payment_count>1</current_payment_count>'
                . '<error code="0"></error>'
                . '</mobilpay>'
            : '';

        return '<order type="card" id="ORDER1" timestamp="20260101000000">'
            . '<signature>sig</signature>'
            . '<service></service>'
            . '<invoice currency="RON" amount="10.00"><details>x</details></invoice>'
            . $notify
            . '</order>';
    }

    public function testCompletePurchaseDecryptsConfirmedIpn(): void
    {
        [$envKey, $encData, $iv] = $this->sealXml($this->ipnOrderXml(true));

        $response = $this->gateway()->completePurchase([
            'env_key' => base64_encode($envKey),
            'data' => base64_encode($encData),
            'cipher' => 'aes-256-cbc',
            'iv' => base64_encode($iv),
            'privateKey' => $this->keyPath,
        ])->send();

        $this->assertSame('confirmed', $response->getData()['objPmNotify']['action']);
        $this->assertTrue($response->isSuccessful());
        $this->assertStringContainsString('<crc>crc-value</crc>', $response->getCrcResponse());
    }

    public function testCompletePurchaseWithoutNotifyElementReturnsPermanentError(): void
    {
        [$envKey, $encData, $iv] = $this->sealXml($this->ipnOrderXml(false));

        $response = $this->gateway()->completePurchase([
            'env_key' => base64_encode($envKey),
            'data' => base64_encode($encData),
            'cipher' => 'aes-256-cbc',
            'iv' => base64_encode($iv),
            'privateKey' => $this->keyPath,
        ])->send();

        $this->assertSame([], $response->getData());
        $this->assertFalse($response->isSuccessful());
        $this->assertStringContainsString(
            'error_type="' . AbstractRequest::CONFIRM_ERROR_TYPE_PERMANENT . '"',
            $response->getCrcResponse(),
        );
    }

    public function testCompletePurchaseAcceptsJunkIvWithRc4(): void
    {
        if (!in_array('rc4', openssl_get_cipher_methods(), true)) {
            $this->markTestSkipped('rc4 not available on this build');
        }

        [$envKey, $encData] = $this->sealXml($this->ipnOrderXml(true), 'rc4');

        $response = $this->gateway()->completePurchase([
            'env_key' => base64_encode($envKey),
            'data' => base64_encode($encData),
            'cipher' => 'rc4',
            'iv' => '!!not-base64!!',
            'privateKey' => $this->keyPath,
        ])->send();

        $this->assertSame('confirmed', $response->getData()['objPmNotify']['action']);
        $this->assertTrue($response->isSuccessful());
    }

    public function testCrcResponseIsXmlAndEscaped(): void
    {
        $request = $this->gateway()->purchase($this->purchaseParameters());

        $ok = new \stdClass();
        $ok->code = 0;
        $ok->type = AbstractRequest::CONFIRM_ERROR_TYPE_NONE;
        $ok->message = 'crc-value';

        $response = new CompletePurchaseResponse($request, [], $ok);
        $body = $response->getCrcResponse();

        $this->assertStringStartsWith('<?xml version="1.0" encoding="utf-8"?>', $body);
        $this->assertStringContainsString('<crc>crc-value</crc>', $body);

        $error = new \stdClass();
        $error->code = AbstractRequest::ERROR_CONFIRM_INVALID_ACTION;
        $error->type = AbstractRequest::CONFIRM_ERROR_TYPE_PERMANENT;
        $error->message = 'bad <value> & more';

        $errorResponse = new CompletePurchaseResponse($request, [], $error);
        $errorBody = $errorResponse->getCrcResponse();

        $this->assertStringContainsString('bad &lt;value&gt; &amp; more', $errorBody);
        $this->assertStringNotContainsString('bad <value>', $errorBody);
    }
}
