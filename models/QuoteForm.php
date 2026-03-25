<?php
namespace DiinCompany\Yii2Shop\models;

use DiinCompany\Yii2Shop\services\ShopServiceLocator;
use Yii;
use yii\base\Model;
use yii\helpers\VarDumper;

/**
 * QuoteForm is the model behind the contact form.
 */
class QuoteForm extends Model
{
    public $name;
    public $email;
    public $phone;
    public $message;
    public $items;
    public $whatsAppLink;

    public function rules()
    {
        return [
            [['name','email','phone'], 'required'],
            [['name','email','phone','message'], 'string', 'max' => 255],
            ['email', 'email'],
            ['phone', 'match', 'pattern' => '/^\+?[0-9\s\-\(\)]+$/', 'message' => Yii::t('shop','El teléfono debe ser un número válido.')],
            [['name', 'email', 'phone', 'message'], 'trim'],
            [['items'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => Yii::t('shop','Nombre'),
            'email' => Yii::t('shop','Correo Electrónico'),
            'phone' => Yii::t('shop','Teléfono'),
            'message' => Yii::t('shop','Mensaje'),
        ];
    }

    public function save()
    {
        $api = ShopServiceLocator::getApiClient();
        $logger = ShopServiceLocator::getLogger();

        if(!$this->validate()) {
            return $this->errors;
        }

        if(!empty($this->items))
            $this->items = json_decode($this->items);

        $response = $api->postOrder([
            'order' => [
                'session_id' => Yii::$app->session->id,
                'status' => 1,
                'type' => 1,
                'items' => $this->items,
                'comment' => $this->message,
            ],
            'customer' => [
                'name' => $this->name,
                'email' => $this->email,
                'phone_number' => $this->phone,
            ],
        ]);

        if($response['status']!=='success') {
            $logger->error("An error occurred while creating order", [
                'attributes' => $this->attributes,
                'response' => $response,
            ]);

            return false;
        }

        $logger->info("Order #{$response['data']['id']} created", [
            'response' => $response,
        ]);

        if($this->items) {
            foreach($this->items as $id) {
                $product = $api->getProduct([
                    'id' => $id,
                ]);

                break;
            }

            $this->getWhatsAppLink($product['data']);
        } else {
            $this->getWhatsAppLink();
        }

        return true;
    }

    public function getWhatsAppLink($product = null) {
        $message = "Hola, estoy interesado en cotizar:\n";
        $message .= "*Nombre:* {$this->name}\n";
        $message .= "*Correo Electrónico:* {$this->email}\n";
        $message .= "*Teléfono:* {$this->phone}\n";

        if($product) {
            $product = "{$product['code']} | {$product['name']}";
            $message .= "*Producto:* {$product}\n";
        }

        $whatsAppPhone = preg_replace('/[^\d+]/', '', Yii::$app->params['contactPhones'][2]);

        $this->whatsAppLink = "https://wa.me/{$whatsAppPhone}/?text=" . urlencode($message);
    }
}
