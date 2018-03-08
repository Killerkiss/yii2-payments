<?php
namespace macklus\payments\models;

use Yii;
use yii\helpers\Json;
use macklus\payments\interfaces\ConstantsProviderInterface;
use macklus\payments\interfaces\ConstantsStatusInterface;

/**
 * This is the model class for table "payment_response".
 *
 * @property int $id
 * @property int $payment_id
 * @property string $item
 * @property string $status
 * @property double $amount
 * @property string $provider
 * @property string $data
 * @property string $error_code
 * @property string $date_received
 * @property string $date_processed
 *
 * @property Payment $payment
 */
class PaymentResponse extends \yii\db\ActiveRecord implements ConstantsProviderInterface, ConstantsStatusInterface
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return Yii::$app->getModule('payments')->tables['response'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['payment_id'], 'integer'],
            [['status', 'item', 'provider', 'data'], 'string'],
            [['amount', 'provider', 'data'], 'required'],
            [['amount'], 'number'],
            [['date_received', 'date_processed'], 'safe'],
            [['error_code', 'item'], 'string', 'max' => 255],
            [['payment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Payment::className(), 'targetAttribute' => ['payment_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('payments', 'ID'),
            'payment_id' => Yii::t('payments', 'Payment ID'),
            'item' => Yii::t('payments', 'Item'),
            'status' => Yii::t('payments', 'Status'),
            'amount' => Yii::t('payments', 'Amount'),
            'provider' => Yii::t('payments', 'Provider'),
            'data' => Yii::t('payments', 'Data'),
            'error_code' => Yii::t('payments', 'Error Code'),
            'date_received' => Yii::t('payments', 'Date Received'),
            'date_processed' => Yii::t('payments', 'Date Processed'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayment()
    {
        return $this->hasOne(Payment::className(), ['id' => 'payment_id']);
    }

    /**
     * @inheritdoc
     * @return PaymentResponseQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PaymentResponseQuery(get_called_class());
    }

    public function recordRequest()
    {
        $data = [
            'get' => Yii::$app->request->get(),
            'post' => Yii::$app->request->post()
        ];
        /*
         * Try to encode response if not utf8
         */
        if (\Yii::$app->charset !== "utf-8") {
            $encoding = Yii::$app->request->post('charset', 'windows-1252');
            array_walk_recursive($data, function (&$value) use ($encoding) {
                $value = mb_convert_encoding($value, 'UTF-8', $encoding);
            });
        }
        $this->data = Json::encode($data);
    }
}
