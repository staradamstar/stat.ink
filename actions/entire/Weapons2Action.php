<?php
/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\actions\entire;

use DateTimeImmutable;
use DateTimeZone;
use Yii;
use app\models\SplatoonVersionGroup2;
use app\models\StatWeaponType2TrendAbstract;
use app\models\WeaponType2;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\ViewAction as BaseAction;

class Weapons2Action extends BaseAction
{
    public function run()
    {
        return Yii::$app->db->transaction(function () {
            return $this->controller->render('weapons2', [
                'weaponTypes' => $this->getWeaponTypeData(),
                'dateVersion' => $this->getDateAndVersionData(),
                'months' => $this->getMonths(),
            ]);
        });
    }

    private function getWeaponTypeData(): array
    {
        // {{{
        return Yii::$app->db->transaction(function (Connection $db): array {
            $tmpDateTable = sprintf('tmp_%s_%d', hash('crc32b', __METHOD__), time());
            $sql = vsprintf('CREATE TEMPORARY TABLE %s ( %s ) AS %s', [
                $db->quoteTableName($tmpDateTable),
                implode(', ', [
                    $db->quoteColumnName('start_date'),
                ]),
                (new Query())
                    ->distinct()
                    ->select(['start_date'])
                    ->from(StatWeaponType2TrendAbstract::tableName())
                    ->createCommand()
                    ->rawSql,
            ]);
            $db->createCommand($sql)->execute();

            $list = (new Query())
                ->select([
                    'start_date' => 'date.start_date',
                    'type_id' => 'type.id',
                    'type_name' => 'type.name',
                    'count' => new Expression(vsprintf('(CASE %s END)', implode(' ', [
                        vsprintf('WHEN %s.%s IS NULL THEN 0', [
                            $db->quoteTableName('trend'),
                            $db->quoteColumnName('count'),
                        ]),
                        vsprintf('ELSE %s.%s', [
                            $db->quoteTableName('trend'),
                            $db->quoteColumnName('count'),
                        ]),
                    ]))),
                ])
                ->from(['date' => $tmpDateTable])
                ->innerJoin(['type' => WeaponType2::tableName()], 'TRUE')
                ->leftJoin(
                    ['trend' => StatWeaponType2TrendAbstract::tableName()],
                    sprintf('((%s))', implode(') AND (', [
                        '{{trend}}.[[start_date]] = {{date}}.[[start_date]]',
                        '{{trend}}.[[weapon_type_id]] = {{type}}.[[id]]',
                    ]))
                )
                ->orderBy([
                    'type.category_id' => SORT_ASC,
                    'type.rank' => SORT_ASC,
                    'date.start_date' => SORT_ASC,
                ])
                ->all();
            $totals = [];
            foreach ($list as $row) {
                $t = strtotime($row['start_date']);
                $totals[$t] = ($totals[$t] ?? 0) + $row['count'];
            }

            $results = [];
            foreach ($list as $row) {
                $typeId = (int)$row['type_id'];
                if (!isset($results[$typeId])) {
                    $results[$typeId] = (object)[
                        'id' => $typeId,
                        'name' => Yii::t('app-weapon2', $row['type_name']),
                        'data' => [],
                    ];
                }
                $t = strtotime($row['start_date']);
                $results[$typeId]->data[] = [
                    $t,
                    ($totals[$t] ?? 0) < 1 ? null : ($row['count'] / $totals[$t]),
                ];
            }

            // 利便性のため、今の時刻のデータを作成する
            $t = (int)($_SERVER['REQUEST_TIME'] ?? time());
            foreach ($results as $typeId => $data) {
                $dataCount = count($data->data);
                $data->data[] = [$t, $data->data[$dataCount - 1][1]];
            }

            return array_values($results);
        });
        // }}}
    }

    private function getDateAndVersionData(): array
    {
        // {{{
        $q = (new Query())
            ->select([
                'version_name' => 'MAX(v.name)',
                'start_date' => 'MIN(t.start_date)',
            ])
            ->from(['t' => StatWeaponType2TrendAbstract::tableName()])
            ->innerJoin(
                ['v' => SplatoonVersionGroup2::tableName()],
                't.version_group_id = v.id'
            )
            ->groupBy([
                't.version_group_id',
            ])
            ->orderBy([
                'MIN(t.start_date)' => SORT_ASC,
            ]);
        return  array_map(
            function (array $row): array {
                return [
                    strtotime($row['start_date']),
                    Yii::t('app-version2', $row['version_name']),
                ];
            },
            $q->all()
        );
        // }}}
    }

    private function getMonths(): array
    {
        // {{{
        if (!$initDate = StatWeaponType2TrendAbstract::find()->min('start_date')) {
            return [];
        }

        $utc = new DateTimeZone('Etc/UTC');
        $t = (new DateTimeImmutable($initDate))->setTimezone($utc);
        $now = (new DateTimeImmutable())
            ->setTimezone($utc)
            ->setTimestamp((int)($_SERVER['REQUEST_TIME'] ?? time()));

        $results = [];
        while ($t <= $now) {
            $results[] = [
                (int)$t->getTimestamp(),
                $t->format('Y-m-d'),
            ];
            $t = $t->setTime(0, 0, 0)->setDate(
                (int)$t->format('Y'),
                (int)$t->format('n') + 1,
                1
            );
        }
        $results[] = [
            (int)$now->getTimestamp(),
            $now->format('Y-m-d'),
        ];
        return $results;
        // }}}
    }
}
