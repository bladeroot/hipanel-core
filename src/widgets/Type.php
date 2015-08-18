<?php
/**
 * @link    http://hiqdev.com/hipanel
 * @license http://hiqdev.com/hipanel/license
 * @copyright Copyright (c) 2015 HiQDev
 */

namespace hipanel\widgets;

use hipanel\base\Model;
use hipanel\base\Re;
use \yii\helpers\ArrayHelper as AH;

class Type extends \hipanel\widgets\Label {

    /** @var Model */
    public $model;

    /** @var $states[] which contains:
      * key - css class name which will be used to highlight label
      * values - states or types, that represent current CSS class
      * Examples
      * ~~~
      * ['info' => ['ok', 'expired']]
      * ~~~
     **/
    public $values = [];

    /** @var defaultValues[] inherits $values */

    public $defaultValues = [];

    /** @var string field  */
    public $field = 'state';

    public function run () {
        $field = $this->model->{$this->field};

        foreach ($this->defaultValues as $key => $values) {
            $possible[$key] = AH::merge($values, $this->values[$key] ? : []);
        }

        $this->values = AH::merge($possible, $this->values);

        foreach ($this->values as $classes => $values) {
            if (in_array($field, $values)) {
                $class = $classes;
                break;
            }
        }

        $this->zclass   = $class ?: 'warning';
        $this->label    = Re::l($this->model->{"{$this->field}_label"} ? : $this->model->{$this->field});
        parent::run();
    }
}