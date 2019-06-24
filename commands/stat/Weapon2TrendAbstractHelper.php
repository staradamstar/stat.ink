<?php
/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

declare(strict_types=1);

namespace app\commands\stat;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Yii;
use app\models\SplatoonVersion2;
use app\models\SplatoonVersionGroup2;
use yii\base\Component;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\Query;

class Weapon2TrendAbstractHelper extends Component
{
    public function run(): bool
    {
        return Yii::$app->db->transaction(function (Connection $db): bool {
            $now = new DateTimeImmutable();

            $tmpTermName = $this->createTermTemporaryTable($this->getTermGroups());
            $tmpWeaponName = $this->createWeaponTemporaryTable($tmpTermName);

            $this->createTypeData($tmpWeaponName, $now);
            $this->createSubData($tmpWeaponName, $now);
            $this->createSpecialData($tmpWeaponName, $now);

            return true;
        });
    }

    // バージョングループと月替わりを考慮したグルーピングデータを作成する
    // 
    // あるバージョンの実施中に 1 日の 0 時 (UTC) が訪れたらその時点で
    // データを切り替える
    private function getTermGroups(): array
    {
        // {{{
        $results = [];
        foreach ($this->getTermGroupsImpl() as $row) {
            $results[] = $row;
        }
        return $results;
    }

    private function getTermGroupsImpl() //: Generator
    {
        $utc = new DateTimeZone('Etc/UTC');
        $nextMonth = null;
        $currentVersion = null;
        foreach ($this->getVersionGroups() as $vGroup) {
            $version = $vGroup->firstVersion;
            $releasedAt = (new DateTimeImmutable($version->released_at))->setTimezone($utc);

            // 初回実行時
            if ($nextMonth === null) {
                yield (object)[
                    'date' => $releasedAt,
                    'version' => $vGroup,
                ];
                $nextMonth = (new DateTimeImmutable())
                    ->setTimeZone($utc)
                    ->setDate((int)$releasedAt->format('Y'), (int)$releasedAt->format('n') + 1, 1)
                    ->setTime(0, 0, 0);
                $currentVersion = $vGroup;
                continue;
            }

            // 月替わりを埋める
            while ($nextMonth < $releasedAt) {
                yield (object)[
                    'date' => $nextMonth,
                    'version' => $currentVersion,
                ];
                $nextMonth =  (new DateTimeImmutable())
                    ->setTimeZone($utc)
                    ->setDate((int)$nextMonth->format('Y'), (int)$nextMonth->format('n') + 1, 1)
                    ->setTime(0, 0, 0);
            }

            yield (object)[
                'date' => $releasedAt,
                'version' => $vGroup,
            ];

            $currentVersion = $vGroup;
        }

        if ($nextMonth && $currentVersion) {
            $now = new DateTimeImmutable('now', $utc);
            while ($nextMonth < $now) {
                yield (object)[
                    'date' => $nextMonth,
                    'version' => $currentVersion,
                ];
                $nextMonth =  (new DateTimeImmutable())
                    ->setTimeZone($utc)
                    ->setDate((int)$nextMonth->format('Y'), (int)$nextMonth->format('n') + 1, 1)
                    ->setTime(0, 0, 0);
            }
        }
    }

    private function getVersionGroups(): array
    {
        $list = array_filter(
            SplatoonVersionGroup2::find()->with('versions')->all(),
            function (SplatoonVersionGroup2 $model): bool {
                return count($model->versions) > 0 && version_compare($model->tag, '1.0', '>=');
            }
        );
        usort($list, function (SplatoonVersionGroup2 $a, SplatoonVersionGroup2 $b): int {
            return version_compare(
                $a->firstVersion->tag ?? '0.0.0.0',
                $b->firstVersion->tag ?? '0.0.0.0'
            );
        });
        return $list;
        // }}}
    }

    // 実際の SELECT を簡単にするためのテンポラリテーブルを作成する
    // 戻り値はテンポラリテーブル名
    //
    // CREATE TEMPORARY TABLE <<NAME>> (
    //   "start_date" TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    //   "version_group_id" INTEGER NOT NULL,
    //   "period_range" INT4RANGE NOT NULL
    // )
    private function createTermTemporaryTable(array $termGroups): string
    {
        // {{{
        $db = Yii::$app->db;
        $values = [];
        foreach ($termGroups as $i => $item) {
            $nextItem = $termGroups[$i + 1] ?? null;
            $values[] = vsprintf('(%s, %d, %s::INT4RANGE)', [
                $db->quoteValue($item->date->format(DateTime::ATOM)),
                $item->version->id,
                $db->quoteValue(vsprintf('[%d,%s)', [
                    (int)ceil($item->date->getTimestamp() / 7200),
                    $nextItem
                        ? (string)(int)floor($nextItem->date->getTimestamp() / 7200)
                        : '',
                ])),
            ]);
        }
        $tableName = sprintf('tmp_%s_%d', hash('crc32b', __METHOD__), time());
        $sql = vsprintf('CREATE TEMPORARY TABLE %s (%s)', [
            $db->quoteTableName($tableName),
            implode(', ', [
                sprintf(
                    '%s TIMESTAMP(0) WITH TIME ZONE NOT NULL',
                    $db->quoteColumnName('start_date')
                ),
                sprintf('%s INTEGER NOT NULL', $db->quoteColumnName('version_group_id')),
                sprintf('%s INT4RANGE NOT NULL', $db->quoteColumnName('period_range')),
                sprintf('EXCLUDE USING GIST (%s WITH &&)', $db->quoteColumnName('period_range')),
            ]),
        ]);
        $db->createCommand($sql)->execute();

        $sql = vsprintf('INSERT INTO %s VALUES %s', [
            $db->quoteTableName($tableName),
            implode(', ', $values),
        ]);
        $db->createCommand($sql)->execute();
        return $tableName;
        // }}}
    }

    // 中間集計用にブキごとに集計したテンポラリテーブルを作成する
    // 戻り値はテンポラリテーブル名
    private function createWeaponTemporaryTable(string $tmpTermTable): string
    {
        // {{{
        $db = Yii::$app->db;
        $query = (new Query())
            ->select([
                'start_date' => 't.start_date',
                'version_group_id' => 't.version_group_id',
                'weapon_id' => 'p.weapon_id',
                'count' => 'COUNT(*)',
            ])
            ->from(['b' => 'battle2'])
            ->innerJoin(['p' => 'battle_player2'], 'b.id = p.battle_id')
            ->innerJoin(['v' => 'splatoon_version2'], 'b.version_id = v.id')
            ->innerJoin(['t' => $tmpTermTable], implode(' AND ', [
                'v.group_id = t.version_group_id',
                'b.period <@ t.period_range',
            ]))
            ->andWhere(['and',
                ['b.is_automated' => true],
                ['b.use_for_entire' => true],
                ['b.is_win' => [true, false]],
                ['not', ['p.weapon_id' => null]],
                ['p.is_me' => false],
            ])
            ->groupBy([
                't.start_date',
                't.version_group_id',
                'p.weapon_id',
            ]);

        $tableName = sprintf('tmp_%s_%d', hash('crc32b', __METHOD__), time());
        $sql = vsprintf('CREATE TEMPORARY TABLE %s ( %s ) AS %s', [
            $db->quoteTableName($tableName),
            implode(', ', array_map(
                [$db, 'quoteColumnName'],
                [
                    'start_date',
                    'version_group_id',
                    'weapon_id',
                    'count',
                ]
            )),
            $query->createCommand()->rawSql
        ]);

        echo "Creating weapon use trends for Splatoon 2...\n";
        $db->createCommand($sql)->execute();
        echo "done\n";
        return $tableName;
        // }}}
    }

    private function createTypeData(string $tmpWeaponName, DateTimeImmutable $now): void
    {
        // {{{
        $db = Yii::$app->db;
        $query = (new Query())
            ->select([
                'start_date' => 't.start_date',
                'version_group_id' => 't.version_group_id',
                'weapon_type_id' => 'type.id',
                'count' => 'SUM(count)',
                'updated_at' => new Expression($db->quoteValue($now->format(DateTime::ATOM))),
            ])
            ->from(['t' => $tmpWeaponName])
            ->innerJoin(['w' => 'weapon2'], 't.weapon_id = w.id')
            ->innerJoin(['type' => 'weapon_type2'], 'w.type_id = type.id')
            ->groupBy([
                't.start_date',
                't.version_group_id',
                'type.id',
            ]);
        echo "Cleanup stat_weapon_type2_trend_abstract...\n";
        $db->createCommand()->delete('stat_weapon_type2_trend_abstract')->execute();
        echo "Insert stat_weapon_type2_trend_abstract...\n";
        $db->createCommand()->insert('stat_weapon_type2_trend_abstract', $query)->execute();
        // }}}
    }

    private function createSubData(string $tmpWeaponName, DateTimeImmutable $now): void
    {
        // {{{
        $db = Yii::$app->db;
        $query = (new Query())
            ->select([
                'start_date' => 't.start_date',
                'version_group_id' => 't.version_group_id',
                'subweapon_id' => 'w.subweapon_id',
                'count' => 'SUM(count)',
                'updated_at' => new Expression($db->quoteValue($now->format(DateTime::ATOM))),
            ])
            ->from(['t' => $tmpWeaponName])
            ->innerJoin(['w' => 'weapon2'], 't.weapon_id = w.id')
            ->groupBy([
                't.start_date',
                't.version_group_id',
                'w.subweapon_id',
            ]);
        echo "Cleanup stat_subweapon2_trend_abstract...\n";
        $db->createCommand()->delete('stat_subweapon2_trend_abstract')->execute();
        echo "Insert stat_subweapon2_trend_abstract...\n";
        $db->createCommand()->insert('stat_subweapon2_trend_abstract', $query)->execute();
        // }}}
    }

    private function createSpecialData(string $tmpWeaponName, DateTimeImmutable $now): void
    {
        // {{{
        $db = Yii::$app->db;
        $query = (new Query())
            ->select([
                'start_date' => 't.start_date',
                'version_group_id' => 't.version_group_id',
                'special_id' => 'w.special_id',
                'count' => 'SUM(count)',
                'updated_at' => new Expression($db->quoteValue($now->format(DateTime::ATOM))),
            ])
            ->from(['t' => $tmpWeaponName])
            ->innerJoin(['w' => 'weapon2'], 't.weapon_id = w.id')
            ->innerJoin(['type' => 'weapon_type2'], 'w.type_id = type.id')
            ->groupBy([
                't.start_date',
                't.version_group_id',
                'w.special_id',
            ]);
        echo "Cleanup stat_special2_trend_abstract...\n";
        $db->createCommand()->delete('stat_special2_trend_abstract')->execute();
        echo "Insert stat_special2_trend_abstract...\n";
        $db->createCommand()->insert('stat_special2_trend_abstract', $query)->execute();
        // }}}
    }
}
