<?php

namespace diincompany\shop\controllers;

use diincompany\shop\contracts\ShopApiClientInterface;
use diincompany\shop\contracts\ShopLoggerInterface;
use diincompany\shop\Module as ShopModule;
use diincompany\shop\models\QuoteForm;
use Yii;
use yii\helpers\VarDumper;
use yii\web\Controller;

/**
 * Quote Controller for the `store` module
 */
class QuoteController extends Controller
{
    private function shopModule(): ShopModule
    {
        $module = $this->module;

        if (!$module instanceof ShopModule) {
            throw new \yii\base\InvalidConfigException('QuoteController must run inside DiinCompany\\Yii2Shop\\Module.');
        }

        return $module;
    }

    private function apiClient(): ShopApiClientInterface
    {
        return $this->shopModule()->getApiClient();
    }

    private function logger(): ShopLoggerInterface
    {
        return $this->shopModule()->getLogger();
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex(int|null $pid = null)
    {
        $data = null;
        $model = new QuoteForm();
        $post = Yii::$app->request->post();

        if($post) {
            // Validate Turnstile captcha
            if(!Yii::$app->turnstile->disable) {
                $captchaResponse = Yii::$app->turnstile->getCaptchaResponse();

                if(!$captchaResponse || !$captchaResponse->success) {
                    Yii::$app->session->setFlash('error', Yii::t('shop', 'Por favor completa el captcha correctamente.'));
                    
                    return $this->redirect(Yii::$app->request->referrer);
                }
            }

            $model->load($post);

            if(!$model->save()) {
                Yii::$app->session->setFlash('error',Yii::t('shop','Algo falló al momento de enviar la cotización'));

                $this->logger()->error('Error sending quote', [
                    'post' => $post,
                    'errors' => $model->errors,
                ]);

                return $this->redirect(Yii::$app->request->referrer);
            }

            Yii::$app->session->setFlash('success',Yii::t('shop','Cotización enviada'));

            $cookie = new \yii\web\Cookie([
                'name' => 'quoteSent',
                'value' => $model->whatsAppLink,
                'expire' => time() + 60, // 1 minute
                'httpOnly' => true,
            ]);
            Yii::$app->response->cookies->add($cookie);

            return $this->redirect(Yii::$app->request->referrer);
        }

        if(!empty($pid)) {
            $api = $this->apiClient();

            $response = $api->getProduct(['id' => $pid]);

            $data = $response['data'];

            $model->items = json_encode([$data['id']]);
        }

        if(Yii::$app->request->isAjax) {
            return $this->renderAjax('minimal', [
                'data' => $data,
                'model' => $model,
            ]);
        }

        return $this->render('index', [
            'data' => $data,
            'model' => $model,
        ]);
    }
}
