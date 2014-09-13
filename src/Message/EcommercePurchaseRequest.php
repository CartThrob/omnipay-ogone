<?php

namespace Omnipay\Ogone\Message;

use Omnipay\Common\Message\AbstractRequest;

/**
 * OGone Authorize Request
 */
class EcommercePurchaseRequest extends AbstractRequest
{
    protected $liveEndpoint = 'https://secure.ogone.com/ncol/prod/orderstandard_utf8.asp';
    protected $testEndpoint = 'https://secure.ogone.com/ncol/test/orderstandard_utf8.asp';

    public function getPspId()
    {
        return $this->getParameter('pspId');
    }

    public function setPspId($value)
    {
        return $this->setParameter('pspId', $value);
    }

    public function getTestMode()
    {
        return $this->getParameter('testMode');
    }

    public function setTestMode($value)
    {
        return $this->setParameter('testMode', $value);
    }

    public function getSecretCode()
    {
        return $this->getParameter('secret_code');
    }

    public function setSecretCode($value)
    {
        return $this->setParameter('secret_code', $value);
    }

    public function getData()
    {
        $this->validate('amount', 'returnUrl');

        $data = array();
        $data['PSPID'] = $this->getPspId();
        $data['ORDERID'] = $this->getTransactionId();
        $data['AMOUNT'] = $this->getAmount() * 100;
        $data['CURRENCY'] = $this->getCurrency();

        //----------------------------------------
        // Return URLs
        //----------------------------------------
        $data['ACCEPTURL'] = $this->getReturnUrl();
        $data['DECLINEURL'] = $this->getCancelUrl();
        //$data['EXCEPTIONURL'] = $this->getCancelUrl();
        //$data['CANCELURL'] = $this->getCancelUrl();

        //----------------------------------------
        // Optional
        //----------------------------------------
        if ($this->getCard()) {
            $data['CN'] = $this->getCard()->getName();
            $data['EMAIL'] = $this->getCard()->getEmail();
            $data['OWNERADDRESS'] = $this->getCard()->getAddress1();
            $data['OWNERZIP'] = $this->getCard()->getPostcode();
            $data['OWNERTOWN'] = $this->getCard()->getCity();
            $data['OWNERCTY'] = $this->getCard()->getCountry();
            $data['OWNERTELNO'] = $this->getCard()->getPhone();
        }

        //----------------------------------------
        // SHAIN Secret Code (Required)
        //----------------------------------------
        $shaIn = $this->getShaIn();

        if (!$shaIn) {
            throw new InvalidRequestException('Missing required sha_in');
        }

        // All parameters have to be arranged alphabetically
        ksort($data);
        array_map('trim', $data);

        //----------------------------------------
        // Generate Security Hash
        // http://payment-services.ingenico.com/ogone/support/guides/integration%20guides/e-commerce/security-pre-payment-check
        //----------------------------------------
        $shaString = '';

        foreach ($data as $key => $val) {
            // Parameters that do not have a value should NOT be included in the string to hash
            if (!$val) continue;

            $shaString .= "{$key}=$val{$shaIn}";
        }

        // All three SHA algo are supported
        switch ($this->getShaAlgo()) {
            case 'sha256':
                $shaSign = hash('sha256', $shaString);
                break;
            case 'sha512':
                $shaSign = hash('sha512', $shaString);
                break;
            default:
                $shaSign = sha1($shaString);
                break;
        }

        $data['SHASIGN'] = $shaSign;

        return $data;
    }

    public function sendData($data)
    {
        return $this->response = new EcommercePurchaseResponse($this, $data);
    }

    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }
}
