<?php
/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */
declare(strict_types=1);

use app\components\db\Migration;

class m190702_000000_weapon2_trend_abstract extends Migration
{
    public function safeUp()
    {
        $list = [
            'stat_weapon_type2_trend_abstract' => [
                'weapon_type_id' => $this->pkRef('weapon_type2')->notNull(),
            ],
            'stat_subweapon2_trend_abstract' => [
                'subweapon_id' => $this->pkRef('subweapon2')->notNull(),
            ],
            'stat_special2_trend_abstract' => [
                'special_id' => $this->pkRef('special2')->notNull(),
            ],
        ];
        foreach ($list as $table => $columns) {
            $this->createTable($table, array_merge(
                [
                    'start_date' => $this->timestampTZ(0)->notNull(),
                    'version_group_id' => $this->pkRef('splatoon_version_group2')->notNull(),
                ],
                $columns,
                [
                    'count' => $this->bigInteger()->notNull(),
                    'updated_at' => $this->timestampTZ(0)->notNull(),
                ],
                [
                    sprintf('PRIMARY KEY (%s)', implode(', ', array_merge(
                        ['start_date', 'version_group_id'],
                        array_keys($columns),
                    ))),
                ]
            ));
        }
    }

    public function safeDown()
    {
        $this->dropTables([
            'stat_special2_trend_abstract',
            'stat_subweapon2_trend_abstract',
            'stat_weapon_type2_trend_abstract',
        ]);
    }
}
