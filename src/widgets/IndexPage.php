<?php

/*
 * HiPanel core package
 *
 * @link      https://hipanel.com/
 * @package   hipanel-core
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2014-2016, HiQDev (http://hiqdev.com/)
 */

namespace hipanel\widgets;

use hipanel\base\OrientationStorage;
use Yii;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\base\Object;
use yii\base\Widget;
use yii\bootstrap\ButtonDropdown;
use yii\data\DataProviderInterface;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\helpers\Url;

class IndexPage extends Widget
{
    /**
     * @var Model the search model
     */
    public $model;

    /**
     * @var Object original view context.
     * It is used to render sub-views with the same context, as IndexPage
     */
    public $originalContext;

    /**
     * @var DataProviderInterface
     */
    public $dataProvider;

    /**
     * @var array Hash of document blocks, that can be rendered later in the widget's views
     * Blocks can be set explicitly on widget initialisation, or by calling [[beginContent]] and
     * [[endContent]]
     *
     * @see beginContent
     * @see endContent
     */
    public $contents = [];

    /**
     * @var string the name of current content block, that is under the render
     * @see beginContent
     * @see endContent
     */
    protected $_current = null;

    /**
     * @var array
     */
    public $searchFormData = [];

    /** @inheritdoc */
    public function init()
    {
        parent::init();
        $searchFormId = Json::htmlEncode("#{$this->getBulkFormId()}");
        $this->originalContext = Yii::$app->view->context;
        $view = $this->getView();
        $view->registerJs(<<<JS
        // Checkbox
        var checkboxes = $('table input[type="checkbox"]');
        var bulkcontainer = $('.box-bulk-actions fieldset');
        checkboxes.on('ifChecked ifUnchecked', function(event) {
            if (event.type == 'ifChecked' && $('input.icheck').filter(':checked').length > 0) {
                bulkcontainer.prop('disabled', false);
            } else if ($('input.icheck').filter(':checked').length == 0) {
                bulkcontainer.prop('disabled', true);
            }
        });
        // On/Off Actions TODO: reduce scope
        $(document).on('click', '.box-bulk-actions a', function (event) {
            var link = $(this);
            var action = link.data('action');
            var form = $($searchFormId);
            if (action) {
                form.attr({'action': action, method: 'POST'}).submit();
            }
        });
JS
);
    }

    /**
     * Begins output buffer capture to save data in [[contents]] with the $name key.
     * Must not be called nested. See [[endContent]] for capture terminating.
     * @param string $name
     */
    public function beginContent($name)
    {
        if ($this->_current) {
            throw new InvalidParamException('Output buffer capture is already running for ' . $this->_current);
        }
        $this->_current = $name;
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Terminates output buffer capture started by [[beginContent()]]
     * @see beginContent
     */
    public function endContent()
    {
        if (!$this->_current) {
            throw new InvalidParamException('Outout buffer capture is not running. Call beginContent() first');
        }
        $this->contents[$this->_current] = ob_get_contents();
        ob_end_clean();
        $this->_current = null;
    }

    /**
     * Returns content saved in [[content]] by $name
     * @param string $name
     * @return string
     */
    public function renderContent($name)
    {
        return $this->contents[$name];
    }

    public function run()
    {
        return $this->render($this->getOrientationStorage());
    }

    public function getOrientationStorage()
    {
        $os = Yii::$app->get('orientationStorage');
        $n = $os->get(Yii::$app->controller->getRoute());
        return $n;
    }

    public function setSearchFormData($data)
    {
        $this->searchFormData = $data;
    }

    public function renderSearchForm($advancedSearchOptions = [])
    {
        ob_start();
        ob_implicit_flush(false);
        try {
            $search = $this->beginSearchForm($advancedSearchOptions);
            foreach (['per_page', 'representation'] as $key) {
                echo Html::hiddenInput($key, Yii::$app->request->get($key));
            }
            echo Yii::$app->view->render('_search', array_merge(compact('search'), $this->searchFormData), $this->originalContext);
            $search->end();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    public function beginSearchForm($options = [])
    {
        return AdvancedSearch::begin(array_merge(['model' => $this->model], $options));
    }

    public function renderSearchButton()
    {
        return AdvancedSearch::renderButton() . "\n";
    }

    public function renderPerPage()
    {
        return ButtonDropdown::widget([
            'label' => Yii::t('app', 'Per page') . ': ' . (Yii::$app->request->get('per_page') ?: 25),
            'options' => ['class' => 'btn-default btn-sm'],
            'dropdown' => [
                'items' => [
                    ['label' => '25',  'url' => Url::current(['per_page' => null])],
                    ['label' => '50',  'url' => Url::current(['per_page' => 50])],
                    ['label' => '100', 'url' => Url::current(['per_page' => 100])],
                    ['label' => '200', 'url' => Url::current(['per_page' => 200])],
                    ['label' => '500', 'url' => Url::current(['per_page' => 500])],
                ],
            ],
        ]);
    }

    public function renderRepresentation()
    {
        $representation = Yii::$app->request->get('representation') ?: 'common';

        return ButtonDropdown::widget([
            'label' => Yii::t('synt', 'View') . ': ' . Yii::t('app', $representation),
            'options' => ['class' => 'btn-default btn-sm'],
            'dropdown' => [
                'items' => [
                    ['label' => Yii::t('app', 'common'), 'url' => Url::current(['representation' => null])],
                    ['label' => Yii::t('app', 'report'), 'url' => Url::current(['representation' => 'report'])],
                ],
            ],
        ]);
    }

    public function renderSorter(array $options)
    {
        return LinkSorter::widget(array_merge([
            'show'  => true,
            'sort'  => $this->dataProvider->getSort(),
            'buttonClass' => 'btn btn-default dropdown-toggle btn-sm',
        ], $options));
    }

    public function getViewPath()
    {
        return parent::getViewPath() . DIRECTORY_SEPARATOR . (new \ReflectionClass($this))->getShortName();
    }

    public function getBulkFormId()
    {
        return 'bulk-' . Inflector::camel2id($this->model->formName());
    }

    public function beginBulkForm($action = '')
    {
        echo Html::beginForm($action, 'POST', ['id' => $this->getBulkFormId()]);
    }

    public function endBulkForm()
    {
        echo Html::endForm();
    }
}