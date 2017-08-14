<?php

/*
 * This file is part of the Macklus Yii2-Payments project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace macklus\payments;

use Yii;
use yii\base\Object;
use yii\base\Exception;
use macklus\payments\models\Payment as PaymentModel;
use macklus\payments\methods\Paypal;
use macklus\payments\methods\creditcard\Redsys;
use macklus\payments\methods\Transfer;

/**
 * 
 */
class Payment extends Object {

    const PROVIDER_PAYPAL = 'paypal';
    const PROVIDER_REDSYS = 'redsys';
    const PROVIDER_TRANSFER = 'transfer';

    private $_payment;
    private $_provider;
    public $viewPath;
    public $isDebug = false;
    public $mode;

    public function init() {
        $this->viewPath = Yii::$app->getModule('payments')->viewPath;
        return parent::init();
    }

    public function start($provider) {
        $current_payment = Yii::$app->session->get('current_payment_code', false);
        if ($current_payment) {
            $this->_payment = PaymentModel::find()->code($current_payment)->one();
        } else {
            $this->_payment = new PaymentModel();
            $this->_payment->save();
            Yii::$app->session->set('current_payment_code', $this->_payment->code);
        }

        // Warn about debug mode
        if (YII_ENV == 'dev' || YII_DEBUG) {
            $this->isDebug = true;
        }
        $this->mode = YII_ENV;

        $this->_payment->provider = $provider;
        $this->_provider = $this->_getInstanceOf($provider);
        $this->_provider->configure($provider);
    }

    public function end() {
        $this->_payment->save();
        Yii::$app->session->remove('current_payment_code');
    }

    private function _getInstanceOf($provider) {
        switch ($provider) {
            case self::PROVIDER_PAYPAL:
                return new Paypal();
            case self::PROVIDER_REDSYS;
                return new Redsys();
            case self::PROVIDER_TRANSFER:
                return new Transfer();
            default:
                throw new Exception("Unknow provider $provider");
        }
    }

    public function setAmount($amount) {
        if (is_numeric($amount)) {
            $this->_payment->amount = $amount;
            $this->_provider->setAmount($amount);
        }
    }

    public function getAmount() {
        return $this->_provider->getAmount();
    }

    public function setName($name) {
        $this->_provider->setName($name);
    }

    public function setItem($item) {
        do {
            $this->_provider->setItem($item);
            $exists = PaymentModel::find()->item($this->_provider->getItem())->one();
        } while ($exists);
        $this->_payment->item = $this->_provider->getItem();
    }

    public function getItem() {
        return $this->_provider->getItem();
    }

    public function setUrlOK($url) {
        $this->_provider->setUrlOK($url);
    }

    public function setUrlError($url) {
        $this->_provider->setUrlError($url);
    }

    public function setParameter($key, $value) {
        $this->_provider->setParameter($key, $value);
    }

    public function getParameter($key) {
        return $this->_provider->getParameter($key);
    }

    public function getCurrency() {
        return $this->_provider->getCurrency();
    }

    public function process() {
        if ($this->_payment->save() !== true) {
            //print_R($this->_payment->getErrors());
        }
        $this->_provider->process();
    }

    public function renderForm($file, $extra_params = []) {
        $params = [
            'payment' => $this,
            'provider' => $this->_provider,
            'database' => $this->_payment
        ];
        $params = array_merge($params, $extra_params);

        return Yii::$app->view->renderFile($this->viewPath . '/' . $file . '.php', $params);
    }

}