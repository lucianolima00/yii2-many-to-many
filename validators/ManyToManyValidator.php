<?php

namespace lucianolima00\ManyToMany\validators;

use lucianolima00\ManyToMany\behaviors\ManyToManyBehavior;
use yii\helpers\ArrayHelper;
use Yii;
use yii\base\InvalidConfigException;
use yii\i18n\PhpMessageSource;
use yii\validators\Validator;

class ManyToManyValidator extends Validator
{
    /**
     * {@inheritDoc}
     */
    public function init()
    {
        parent::init();

        Yii::setAlias('@many-to-many', dirname(__DIR__));
        Yii::$app->i18n->translations['many-to-many'] = [
            'class' => PhpMessageSource::class,
            'basePath' => '@many-to-many/messages',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validateAttribute($model, $attribute)
    {
        $label = $model->getAttributeLabel($attribute);

        if (!is_array($model->$attribute)) {
            $model->addError($attribute, Yii::t('many-to-many', '{attribute} must be an array.', [
                'attribute' => $label,
            ]));

            return;
        }

        $behavior = null;

        foreach ($model->behaviors as $key => $attachedBehavior) {
            if ($attachedBehavior::className() == ManyToManyBehavior::class) {
                $behavior = $attachedBehavior;

                break;
            }
        }

        if (!$behavior) {
            throw new InvalidConfigException("Behavior not detected.");
        }

        $primaryKeys = $model->$attribute;

        if (!$primaryKeys) {
            return;
        }

        $relatedModel = $behavior->relatedModel;
        $relatedModelPk = ArrayHelper::getValue($relatedModel::primaryKey(), '0');
        $relatedModelsCount = $relatedModel::find()->where([$relatedModelPk => $primaryKeys])->count();

        if (count($primaryKeys) != $relatedModelsCount) {
            $error = 'There are nonexistent elements in {attribute}.';
            $model->addError($attribute, Yii::t('many-to-many', $error, ['attribute' => $label]));
        }
    }
}
