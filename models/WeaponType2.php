<?php
/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\models;

use Yii;
use app\components\helpers\Translator;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "weapon_type2".
 *
 * @property integer $id
 * @property string $key
 * @property integer $category_id
 * @property string $name
 * @property integer $rank
 *
 * @property Weapon2[] $weapons
 * @property WeaponCategory2 $category
 */
class WeaponType2 extends ActiveRecord
{
    public static function tableName()
    {
        return 'weapon_type2';
    }

    public function rules()
    {
        return [
            [['key', 'category_id', 'name', 'rank'], 'required'],
            [['category_id', 'rank'], 'default', 'value' => null],
            [['category_id', 'rank'], 'integer'],
            [['key'], 'string', 'max' => 16],
            [['name'], 'string', 'max' => 32],
            [['category_id', 'rank'], 'unique', 'targetAttribute' => ['category_id', 'rank']],
            [['key'], 'unique'],
            [['name'], 'unique'],
            [['category_id'], 'exist',
                'skipOnError' => true,
                'targetClass' => WeaponCategory2::class,
                'targetAttribute' => ['category_id' => 'id'],
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'key' => 'Key',
            'category_id' => 'Category ID',
            'name' => 'Name',
            'rank' => 'Rank',
        ];
    }

    public function getWeapons(): ActiveQuery
    {
        return $this->hasMany(Weapon2::class, ['type_id' => 'id']);
    }

    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(WeaponCategory2::class, ['id' => 'category_id']);
    }

    public function toJsonArray(): array
    {
        return [
            'key' => $this->key,
            'name' => Translator::translateToAll('app-weapon2', $this->name),
            'category' => $this->category->toJsonArray(),
        ];
    }
}
