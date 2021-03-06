<?php

/**
 * @copyright Copyright (C) 2015-2017 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

namespace app\actions\api\v2;

use Yii;
use yii\web\ViewAction as BaseAction;
use app\models\Map2;

class StageAction extends BaseAction
{
    public function run()
    {
        $response = Yii::$app->getResponse();
        $response->format = 'json';
        return array_map(
            function (Map2 $map): array {
                return $map->toJsonArray();
            },
            Map2::find()->orderBy(['id' => SORT_ASC])->all()
        );
    }
}
