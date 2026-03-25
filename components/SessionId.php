<?php
namespace DiinCompany\Yii2Shop\components;

use Ramsey\Uuid\Uuid;
use Yii;

class SessionId
{
    public static function getAnonymousSessionId($regenerate = false)
    {
        $cookieName = '_anonymous_session_id';
        $cookies = Yii::$app->request->cookies;
        
        // Check if cookie exists
        if ($cookies->has($cookieName) && !$regenerate) {
            $cookie = $cookies->get($cookieName);

            return $cookie->value;
        }
        
        // Generate new UUID
        $sessionId = Uuid::uuid4()->toString();
        
        // Set cookie (persist for 1 year)
        Yii::$app->response->cookies->add(new \yii\web\Cookie([
            'name' => $cookieName,
            'value' => $sessionId,
            'expire' => time() + (365 * 24 * 60 * 60), // 1 year
            'httpOnly' => true,
        ]));
        
        return $sessionId;
    }
}