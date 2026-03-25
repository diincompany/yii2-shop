<?php
namespace app\modules\store\widgets;

use Yii;
use yii\helpers\VarDumper;

class WhatsAppRedirect extends QuoteButton
{
    public function run()
    {
        

        // Check is quote was sent to redirect to WhatsApp
        if(Yii::$app->request->cookies->has('quoteSent')) {
            $cookie = Yii::$app->request->cookies->get('quoteSent');

            Yii::$app->view->registerJs(<<<JS
                // wait 1 second before redirecting to a new window for WhatsApp
                console.log('Quote sent, redirecting to WhatsApp link...', '{$cookie->value}');

                setTimeout(() => {
                    const whatsAppLink = '{$cookie->value}';
                    // window.open(whatsAppLink, '_blank') || window.location.href;
                    window.location.href = whatsAppLink;
                }, 1000);
            JS);

            Yii::$app->response->cookies->remove('quoteSent');
        }
    }
}