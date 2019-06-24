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
 * This is the model class for table "stat_subweapon2_trend_abstract".
 *
 * @property string $start_date
 * @property integer $version_group_id
 * @property integer $subweapon_id
 * @property integer $count
 * @property string $updated_at
 *
 * @property SplatoonVersionGroup2 $versionGroup
 * @property Subweapon2 $subweapon
 */
class StatSubweapon2TrendAbstract extends ActiveRecord
{
    public static function tableName()
    {
        return 'stat_subweapon2_trend_abstract';
    }

    public function rules()
    {
        return [
            [['start_date', 'version_group_id', 'subweapon_id', 'count', 'updated_at'], 'required'],
            [['start_date', 'updated_at'], 'safe'],
            [['version_group_id', 'subweapon_id', 'count'], 'default', 'value' => null],
            [['version_group_id', 'subweapon_id', 'count'], 'integer'],
            [['start_date', 'version_group_id', 'subweapon_id'], 'unique',
                'targetAttribute' => ['start_date', 'version_group_id', 'subweapon_id'],
            ],
            [['version_group_id'], 'exist',
                'skipOnError' => true,
                'targetClass' => SplatoonVersionGroup2::class,
                'targetAttribute' => ['version_group_id' => 'id'],
            ],
            [['subweapon_id'], 'exist',
                'skipOnError' => true,
                'targetClass' => Subweapon2::class,
                'targetAttribute' => ['subweapon_id' => 'id'],
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'start_date' => 'Start Date',
            'version_group_id' => 'Version Group ID',
            'subweapon_id' => 'Subweapon ID',
            'count' => 'Count',
            'updated_at' => 'Updated At',
        ];
    }

    public function getVersionGroup(): ActiveQuery
    {
        return $this->hasOne(SplatoonVersionGroup2::class, ['id' => 'version_group_id']);
    }

    public function getSubweapon(): ActiveQuery
    {
        return $this->hasOne(Subweapon2::class, ['id' => 'subweapon_id']);
    }
}
