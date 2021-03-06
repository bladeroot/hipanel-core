<?php

namespace hipanel\grid;

use hiqdev\higrid\representations\RepresentationCollection;
use hiqdev\higrid\representations\RepresentationCollectionInterface;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Inflector;

/**
 * Class RepresentationCollectionFinder helps to find a representation collection class
 * depending on [[module]] and [[controller]] name.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class RepresentationCollectionFinder
{
    private $module;
    private $controller;
    /**
     * @var string
     * // TODO: declare format. example: '\hipanel\modules\%s\grid\%sRepresentations'
     */
    private $representationsLocation;

    public function __construct($module, $controller, string $representationsLocation)
    {
        $this->module = $module;
        $this->controller = $controller;
        $this->representationsLocation = $representationsLocation;
    }

    protected function buildClassName()
    {
        return sprintf($this->representationsLocation, $this->module, $this->controller);
    }

    /**
     * @return RepresentationCollectionInterface|RepresentationCollection
     */
    public function find()
    {
        $representationsClass = $this->buildClassName();

        if (!class_exists($representationsClass)) {
            return null;
        }

        return Yii::createObject(['class' => $representationsClass]);
    }

    /**
     * @return RepresentationCollectionInterface|RepresentationCollection
     */
    public function findOrFallback()
    {
        $collection = $this->find();

        if ($collection === null) {
            $collection = new RepresentationCollection();
        }

        return $collection;
    }

    /**
     * @return RepresentationCollection|RepresentationCollectionInterface
     * @throws InvalidConfigException When collection does not exist for the route
     */
    public function findOrFail()
    {
        $collection = $this->find();
        if ($collection === null) {
            throw new InvalidConfigException('Representation class "' . $this->buildClassName() . '" does not exist');
        }

        return $collection;
    }

    static function forCurrentRoute(string $representationsLocation)
    {
        $controller = Yii::$app->controller;

        $module = $controller->module->id;
        $controller = Inflector::id2camel($controller->id);

        return new static($module, $controller, $representationsLocation);
    }
}
