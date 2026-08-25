<?php

declare(strict_types=1);

namespace hipanel\actions;

use Yii;
use yii\base\Action;
use yii\web\Response;

/**
 * Forces a fresh CSRF token (discarding whatever is currently stored - see
 * Request::getCsrfToken()'s $regenerate param) and returns it as JSON, so a
 * client that just got a "Unable to verify your data submission" 400 can
 * fetch a valid token and retry instead of failing outright.
 */
class CsrfTokenAction extends Action
{
    public function run(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'csrfParam' => Yii::$app->request->csrfParam,
            'csrfToken' => Yii::$app->request->getCsrfToken(true),
        ];
    }
}
