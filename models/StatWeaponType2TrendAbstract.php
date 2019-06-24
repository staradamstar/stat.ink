<?php
/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "stat_weapon_type2_trend_abstract".
 *
 * @property string $start_date
 * @property integer $version_group_id
 * @property integer $weapon_type_id
 * @property integer $count
 * @property string $updated_at
 *
 * @property SplatoonVersionGroup2 $versionGroup
 * @property WeaponType2 $weaponType
 */
class StatWeaponType2TrendAbstract extends ActiveRecord
{
    public static function tableName()
    {
        return 'stat_weapon_type2_trend_abstract';
    }

    public function rules()
    {
        return [
            [['start_date', 'version_group_id', 'weapon_type_id', 'count'], 'required'],
            [['updated_at'], 'required'],
            [['start_date', 'updated_at'], 'safe'],
            [['version_group_id', 'weapon_type_id', 'count'], 'default', 'value' => null],
            [['version_group_id', 'weapon_type_id', 'count'], 'integer'],
            [['start_date', 'version_group_id', 'weapon_type_id'], 'unique',
                'targetAttribute' => ['start_date', 'version_group_id', 'weapon_type_id'],
            ],
            [['version_group_id'], 'exist',
                'skipOnError' => true,
                'targetClass' => SplatoonVersionGroup2::class,
                'targetAttribute' => ['version_group_id' => 'id'],
            ],
            [['weapon_type_id'], 'exist',
                'skipOnError' => true,
                'targetClass' => WeaponType2::class,
                'targetAttribute' => ['weapon_type_id' => 'id'],
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'start_date' => 'Start Date',
            'version_group_id' => 'Version Group ID',
            'weapon_type_id' => 'Weapon Type ID',
            'count' => 'Count',
            'updated_at' => 'Updated At',
        ];
    }

    public function getVersionGroup(): ActiveQuery
    {
        return $this->hasOne(SplatoonVersionGroup2::class, ['id' => 'version_group_id']);
    }

    public function getWeaponType(): ActiveQuery
    {
        return $this->hasOne(WeaponType2::class, ['id' => 'weapon_type_id']);
    }
}
