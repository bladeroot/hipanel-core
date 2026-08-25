<?php

declare(strict_types=1);

namespace hipanel\components;

use hipanel\helpers\Url;
use Yii;
use yii\base\Application;
use yii\base\Component;
use yii\web\View;

/**
 * A stale/mismatched session (multiple tabs, a page served from cache, a request that
 * raced an earlier request's session write, ...) makes the CSRF token embedded in the
 * page's meta tags fail validation server-side, even though nothing legitimately wrong
 * happened - see hipanel\actions\TimeZoneAction's automatic POST for the most visible
 * case of this. Rather than surface that as a dead-end 400 to every visitor who hits it,
 * fetch a fresh token from CsrfTokenAction and retry the failed request once.
 */
class CsrfRetry extends Component
{
    public function init()
    {
        parent::init();

        Yii::$app->on(Application::EVENT_BEFORE_REQUEST, function ($event) {
            /** @var View $view */
            $view = $event->sender->view;
            $csrfTokenUrl = Url::to('/site/csrf-token');
            $js = /** @lang JavaScript */ "
              ;(() => {
                var refreshing = false;
                $(document).ajaxError(function (event, jqXHR, settings) {
                  if (
                    jqXHR.status !== 400 ||
                    settings._csrfRetried ||
                    refreshing ||
                    !settings.type ||
                    settings.type.toUpperCase() !== 'POST' ||
                    settings.url.indexOf('$csrfTokenUrl') !== -1
                  ) {
                    return;
                  }
                  refreshing = true;
                  $.get('$csrfTokenUrl', function (data) {
                    yii.setCsrfToken(data.csrfParam, data.csrfToken);
                    refreshing = false;
                    $.ajax($.extend({}, settings, { _csrfRetried: true }));
                  }).fail(function () {
                    refreshing = false;
                  });
                });
              })();
            ";
            $view->registerJs($js);
        });
    }
}
