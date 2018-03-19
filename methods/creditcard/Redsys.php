<?php
namespace macklus\payments\methods\creditcard;

use yii\base\Exception;
use macklus\payments\lib\Redsys as RedSysObj;
use macklus\payments\methods\BaseMethod;

Class Redsys extends BaseMethod
{

    protected $_obj;
    public $signature;
    public $view = __DIR__ . '/../../views/redsys.php';
    protected $_merchantParameters;
    protected $_signature;
    protected $order;
    protected $_accepted_vars = [
        'merchant', 'terminal', 'currency', 'transactionType', 'merchantURL',
        'urlOK', 'urlKO', 'urlPago', 'key', 'version', 'live',
        'debug', 'submit', 'form_target'
    ];
    protected $_required_vars = [
        'merchant', 'terminal', 'currency', 'transactionType', 'merchantURL',
        'urlOK', 'urlKO', 'urlPago', 'key'
    ];

    public function __construct()
    {
        $this->_config['version'] = 'HMAC_SHA256_V1';
        return parent::__construct();
    }

    public function process()
    {
        $this->_obj = new RedSysObj();

        $this->_obj->setParameter("DS_MERCHANT_AMOUNT", $this->getSaniticeAmount());
        $this->_obj->setParameter("DS_MERCHANT_ORDER", strval($this->item));
        $this->_obj->setParameter("DS_MERCHANT_MERCHANTCODE", $this->getParameter('merchant'));
        $this->_obj->setParameter("DS_MERCHANT_CURRENCY", $this->getCurrency());
        $this->_obj->setParameter("DS_MERCHANT_TRANSACTIONTYPE", $this->getParameter('transactionType'));
        $this->_obj->setParameter("DS_MERCHANT_TERMINAL", $this->getParameter('terminal'));
        $this->_obj->setParameter("DS_MERCHANT_MERCHANTURL", $this->getParameter('merchantURL'));
        $this->_obj->setParameter("DS_MERCHANT_URLOK", $this->getParameter('urlOK'));
        $this->_obj->setParameter("DS_MERCHANT_URLKO", $this->getParameter('urlKO'));

        $this->_merchantParameters = $this->_obj->createMerchantParameters();
        $this->_signature = $this->_obj->createMerchantSignature($this->getParameter('key'));
    }

    public function getSaniticeAmount()
    {
        $amount = $this->amount;
        $amount = round($amount, 2);
        $amount = $amount * 100;
        return intval($amount);
    }

    public function getMerchantParameters()
    {
        return $this->_merchantParameters;
    }

    public function getSignature()
    {
        return $this->_signature;
    }

    public function getCurrency()
    {
        switch ($this->currency) {
            case 'EUR':
                return 978;
                break;
            default:
                return 978;
                break;
        }
    }

    public function setItem($item)
    {
        /*
         * Redsys requires that item has:
         * - between 4 and 12 positions
         * - firts 4 positions as numeric
         * 
         * also, we expect our user set item as ID, so construct it
         */
        if (strlen($item) > 7) {
            throw new Exception('too much characters on setItem');
        }

        /*
         * Redsys dont allow duplicate order numbers
         * to avoid that, we generate a random string
         */
        $this->item = 'i' . $item;

        $end = 12 - strlen($this->item);
        for ($r = 1; $r <= $end; $r++) {
            $this->item = rand(0, 9) . $this->item;
        }
    }

    public function setName($name)
    {
        
    }

    public function setUrlOK($url)
    {
        $this->setParameter('urlOK', $url);
    }

    public function setUrlError($url)
    {
        $this->setParameter('urlKO', $url);
    }
}
