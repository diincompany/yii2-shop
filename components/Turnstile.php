<?php

namespace diincompany\shop\components;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Yii;
use yii\base\Component;
use yii\helpers\Html;
use yii\web\View;

/**
 * Cloudflare Turnstile component.
 *
 * Provides captcha rendering and server-side verification.
 * Register as an application component:
 *
 * ```php
 * 'turnstile' => [
 *     'class'     => 'diincompany\shop\components\Turnstile',
 *     'siteKey'   => 'your-site-key',
 *     'secretKey' => 'your-secret-key',
 * ],
 * ```
 *
 * Usage in views:
 *   <?= Yii::$app->turnstile->getCaptcha() ?>
 *
 * Usage in controllers:
 *   if (!Yii::$app->turnstile->disable) {
 *       $result = Yii::$app->turnstile->getCaptchaResponse();
 *       if (!$result->success) { ... }
 *   }
 */
class Turnstile extends Component
{
    /** @var string Cloudflare Turnstile site key (public) */
    public string $siteKey = '';

    /** @var string Cloudflare Turnstile secret key (private) */
    public string $secretKey = '';

    /** @var bool Set to true to skip rendering and verification (e.g. in dev/testing) */
    public bool $disable = false;

    private const VERIFY_URL  = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    private const SCRIPT_URL  = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    public  const INPUT_NAME  = 'cf-turnstile-response';

    public function init(): void
    {
        parent::init();

        if ($this->disable) {
            return;
        }

        if (trim($this->siteKey) === '' || trim($this->secretKey) === '') {
            $this->disable = true;
            Yii::warning('Turnstile disabled because siteKey/secretKey are missing.', __METHOD__);
        }
    }

    /**
     * Renders the Turnstile widget HTML and registers the required JS file.
     *
     * @param array $options Additional HTML attributes merged onto the wrapper div.
     * @return string
     */
    public function getCaptcha(array $options = []): string
    {
        if ($this->disable) {
            return '';
        }

        Yii::$app->view->registerJsFile(
            self::SCRIPT_URL,
            ['async' => true, 'defer' => true, 'position' => View::POS_END]
        );

        $attrs = array_merge(
            ['class' => 'cf-turnstile', 'data-sitekey' => $this->siteKey],
            $options
        );

        return Html::tag('div', '', $attrs);
    }

    /**
     * Reads the token submitted by the browser and verifies it with Cloudflare.
     *
     * @return object An object with at least a `success` (bool) property.
     */
    public function getCaptchaResponse(): object
    {
        $token = trim((string) Yii::$app->request->post(self::INPUT_NAME, ''));

        if ($token === '') {
            return (object) ['success' => false, 'error-codes' => ['missing-input-response']];
        }

        try {
            $client = new Client(['timeout' => 5.0]);
            $response = $client->post(self::VERIFY_URL, [
                'form_params' => [
                    'secret'   => $this->secretKey,
                    'response' => $token,
                    'remoteip' => Yii::$app->request->userIP,
                ],
            ]);

            $data = json_decode((string) $response->getBody(), false);

            return $data ?? (object) ['success' => false, 'error-codes' => ['invalid-json']];
        } catch (GuzzleException $e) {
            Yii::warning('Turnstile verify error: ' . $e->getMessage(), __METHOD__);

            return (object) ['success' => false, 'error-codes' => ['connection-error']];
        }
    }
}
