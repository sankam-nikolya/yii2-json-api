<?php
/**
 * @link http://www.stombox.com/
 * @copyright Copyright (c) 2015 Stombox LLC
 * @license http://www.stombox.com/license/
 */

namespace tuyakhov\jsonapi;


use yii\base\Arrayable;
use yii\db\ActiveRecordInterface;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

trait ResourceTrait
{
    /**
     * @return null|string
     */
    public function getId()
    {
        if ($this instanceof ActiveRecordInterface) {
            return (string) $this->getPrimaryKey();
        }
        return null;
    }

    /**
     * @return string
     */
    public function getType()
    {
        $reflect = new \ReflectionClass($this);
        $className = $reflect->getShortName();
        return Inflector::pluralize(Inflector::camel2id($className));
    }

    /**
     * @param array $fields
     * @return array
     */
    public function getAttributes(array $fields = [])
    {
        $attributes = [];

        foreach (self::resolveFields($this->fields(), $fields) as $name => $definition) {
            $attributes[$name] = is_string($definition) ? $this->$definition : call_user_func($definition, $this, $name);
        }
        return $attributes;
    }

    /**
     * @return array
     */
    public function getRelationships()
    {
        $relationships = [];

        foreach (self::resolveFields($this->extraFields()) as $name => $definition) {
            if (is_string($definition)) {
                $relation = $this->$definition;
                if (!is_array($relation)) {
                    $relation = [$relation];
                }
                foreach($relation as $item) {
                    if ($item instanceof ResourceIdentifierInterface) {
                        $relationships[$name]['data'] = ['id' => $item->getId(), 'type' => $item->getType()];
                    }
                }
                // TODO add links and meta. Should create interface
            }
        }
        return $relationships;
    }

    /**
     * @param array $fields
     * @param array $expand
     * @param bool $recursive
     * @return array
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = [
            'id' => $this->getId(),
            'type' => $this->getType(),
        ];
        $attributes = $this->getAttributes($fields);
        $relationships = $this->getRelationships();
        if (!empty($attributes)) {
            $data['attributes'] = $attributes;
        }
        if (!empty($relationships)) {
            $data['relationships'] = $relationships;
        }

        // TODO add links and meta. Should create interface

        return $recursive ? ArrayHelper::toArray($data) : $data;
    }

    /**
     * @return array
     */
    public function fields()
    {
        $fields = array_keys(\Yii::getObjectVars($this));
        return array_combine($fields, $fields);
    }

    /**
     * @return array
     */
    public function extraFields()
    {
        return [];
    }

    /**
     * @param array $fields
     * @param array $fieldSet
     * @return array
     */
    protected static function resolveFields(array $fields, array $fieldSet = [])
    {
        $result = [];

        foreach ($fields as $field => $definition) {
            if (is_int($field)) {
                $field = $definition;
            }
            if (empty($fieldSet) || in_array($field, $fieldSet, true)) {
                $result[$field] = $definition;
            }
        }

        return $result;
    }
}