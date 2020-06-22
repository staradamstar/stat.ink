<?php

/**
 * @copyright Copyright (C) 2015-2020 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "stat_weapon2_entire_main".
 *
 * @property integer $rule_id
 * @property integer $weapon_id
 * @property integer $battles
 * @property integer $wins
 * @property double $avg_kill
 * @property double $med_kill
 * @property double $stddev_kill
 * @property double $avg_death
 * @property double $med_death
 * @property double $stddev_death
 * @property double $avg_special
 * @property double $med_special
 * @property double $stddev_special
 * @property double $avg_point
 * @property double $med_point
 * @property double $stddev_point
 * @property double $avg_time
 * @property string $updated_at
 *
 * @property Rule2 $rule
 * @property Weapon2 $weapon
 */
class StatWeapon2EntireMain extends ActiveRecord
{
    public static function tableName()
    {
        return 'stat_weapon2_entire_main';
    }

    public function rules()
    {
        return [
            [['rule_id', 'weapon_id', 'battles', 'wins', 'avg_kill', 'med_kill', 'avg_death', 'med_death'], 'required'],
            [['avg_special', 'med_special', 'avg_point', 'med_point', 'avg_time', 'updated_at'], 'required'],
            [['rule_id', 'weapon_id', 'battles', 'wins'], 'default', 'value' => null],
            [['rule_id', 'weapon_id', 'battles', 'wins'], 'integer'],
            [['avg_kill', 'med_kill', 'stddev_kill', 'avg_death', 'med_death', 'stddev_death'], 'number'],
            [['avg_special', 'med_special', 'stddev_special', 'avg_point', 'med_point', 'stddev_point'], 'number'],
            [['avg_time'], 'number'],
            [['updated_at'], 'safe'],
            [['rule_id', 'weapon_id'], 'unique', 'targetAttribute' => ['rule_id', 'weapon_id']],
            [['rule_id'], 'exist',
                'skipOnError' => true,
                'targetClass' => Rule2::class,
                'targetAttribute' => [
                    'rule_id' => 'id',
                ],
            ],
            [['weapon_id'], 'exist',
                'skipOnError' => true,
                'targetClass' => Weapon2::class,
                'targetAttribute' => [
                    'weapon_id' => 'id',
                ],
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'rule_id' => 'Rule ID',
            'weapon_id' => 'Weapon ID',
            'battles' => 'Battles',
            'wins' => 'Wins',
            'avg_kill' => 'Avg Kill',
            'med_kill' => 'Med Kill',
            'stddev_kill' => 'Stddev Kill',
            'avg_death' => 'Avg Death',
            'med_death' => 'Med Death',
            'stddev_death' => 'Stddev Death',
            'avg_special' => 'Avg Special',
            'med_special' => 'Med Special',
            'stddev_special' => 'Stddev Special',
            'avg_point' => 'Avg Point',
            'med_point' => 'Med Point',
            'stddev_point' => 'Stddev Point',
            'avg_time' => 'Avg Time',
            'updated_at' => 'Updated At',
        ];
    }

    public function getRule(): ActiveQuery
    {
        return $this->hasOne(Rule2::class, ['id' => 'rule_id']);
    }

    public function getWeapon(): ActiveQuery
    {
        return $this->hasOne(Weapon2::class, ['id' => 'weapon_id']);
    }
}