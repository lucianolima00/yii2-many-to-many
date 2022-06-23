<?php

namespace lucianolima00\ManyToMany\behaviors;

use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\base\Behavior;

/**
 * Class ManyToManyBehavior
 * @package lucianolima00\ManyToMany\behaviors
 */
class ManyToManyBehavior extends Behavior
{
    /**
     * @var string
     */
    public string $relatedModel;
    public string $attribute;
    public string $ownAttribute;
    public string $relatedAttribute;

    /**
     * This attribute ensure that the relation are unique combined with ownAttribute.
     * Ensure that for the same ownAttribute only exist one relatedAttribute with same value.
     * @var boolean
     */
    public bool $unique = true;

    /**
     * {@inheritDoc}
     */
    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'find',
            ActiveRecord::EVENT_AFTER_INSERT => 'insert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'update'
        ];
    }

    public function find()
    {
        if ($model = $this->owner) {
            $relatedModelPk = ArrayHelper::getValue($this->relatedModel::primaryKey(), '0');
            
            return $this->relatedModel::find()->where([$this->ownAttribute => $model->$relatedModelPk])->asArray()->all();
        } else {
            throw new InvalidConfigException('Behavior without owner.');
        }
    }

    /**
     *
     */
    public function insert()
    {
        if ($model = $this->owner) {
            $attribute = $this->attribute;

            $this->createRelation($attribute, $model);
        } else {
            throw new InvalidConfigException('Behavior without owner.');
        }
    }

    /**
     *
     */
    public function update()
    {
        if ($model = $this->owner) {
            $attribute = $this->attribute;
            $relatedModelPk = ArrayHelper::getValue($this->relatedModel::primaryKey(), '0');

            $existentRelations = $this->relatedModel::find()
                ->select([$relatedModelPk, $this->ownAttribute, $this->relatedAttribute])
                ->where([$this->ownAttribute => $model->$relatedModelPk])
                ->asArray()->all();

            if ($model->$attribute != "") {
                foreach ($model->$attribute as $config) {
                    ArrayHelper::setValue($config, $this->ownAttribute, $model->$relatedModelPk);
                    $key = false;
                    $aux = array_intersect_key($config, array_flip(['id', $this->ownAttribute, $this->relatedAttribute]));

                    foreach ($existentRelations as $id => $relation) {
                        if (ArrayHelper::getValue($relation, "id") === ArrayHelper::getValue($aux, "id")) {
                            $key = $id;
                            break;
                        }
                    }

                    if ($key !== false) {
                        $arr = ArrayHelper::getValue($existentRelations, $key);
                        ArrayHelper::setValue($arr, 'exist', true);
                        ArrayHelper::setValue($existentRelations, $key, $arr);
                    }
                }
            }

            foreach ($existentRelations as $existentRelation) {
                if (!ArrayHelper::getValue($existentRelation, 'exist')) {
                    $this->delete($existentRelation);
                }
            }

            $this->createRelation($attribute, $model);
        } else {
            throw new InvalidConfigException('Behavior without owner.');
        }
    }

    /**
     * @param string $attribute
     * @param \yii\base\Component $model
     * @return void
     * @throws \Exception
     */
    private function createRelation(string $attribute, \yii\base\Component $model)
    {
        if ($model->$attribute != "") {
            $relatedModelPk = ArrayHelper::getValue($this->relatedModel::primaryKey(), '0');
            
            foreach ($model->$attribute as $config) {
                ArrayHelper::setValue($config, $this->ownAttribute, $model->$relatedModelPk);
                if (ArrayHelper::getValue($config, 'id')) {
                    $exist = $this->relatedModel::findOne([ArrayHelper::getValue($this->relatedModel::primaryKey(), '0') => ArrayHelper::getValue($config, 'id')]);
                } else if ($this->unique) {
                    $exist = $this->relatedModel::findOne([$this->ownAttribute => $model->$relatedModelPk, $this->relatedAttribute => ArrayHelper::getValue($config, $this->relatedAttribute)]);
                }

                $relatedModel = $exist ?? new $this->relatedModel();

                $relatedModel->setAttributes($config);

                $relatedModel->save();
            }
        }
    }

    /**
     * @return void
     */
    private function delete($array) {
        $relation = $this->relatedModel::findOne(ArrayHelper::getValue($array, 'id'));

        if ($relation) {
            $relation->delete();
        }
    }
}