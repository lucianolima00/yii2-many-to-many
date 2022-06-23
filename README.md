ManyToMany Relation for Yii PHP Framework
=========================================================================

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require lucianolima00/yii2-many-to-many "*"
```

or add

```
"lucianolima00/yii2-many-to-many": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by:

```php
//app\models\User.php
<?php

use lucianolima00\ManyToMany\behaviors\ManyToManyBehavior;

class User extends \yii\db\ActiveRecord
{

    /**
     * {@inheritDoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => ManyToManyBehavior::class(),
                'ownAttribute' => 'user_id', // Name of the column in junction table that represents current model
                'relatedModel' => UserTest::class, // Junction model class
                'attribute' => 'tests', // Represent the attribute of current model 
                'relatedAttribute' => 'test_id', // Name of the column in junction table that represents related model
                'unique' => true // Ensure that for the same ownAttribute only exist one relatedAttribute with same value. Default is true
            ],
        ];
    }
```

For display related models (requires additional model for junction table):

```php
/**
 * Gets query for [[UserTests]].
 * 
 * @return \yii\db\ActiveQuery
 */
public function getUserTests()
{
    return $this->hasMany(UserTest::class(), ['user_id' => 'id']);
}
```
