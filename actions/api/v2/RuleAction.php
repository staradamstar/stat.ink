<?php

/**
 * @copyright Copyright (C) 2015-2017 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

namespace app\actions\api\v2;

use Yii;
use yii\web\ViewAction as BaseAction;
use app\models\Mode2;

class RuleAction extends BaseAction
{
    public function run()
    {
        $response = Yii::$app->getResponse();
        $response->format = 'json';
        return array_map(
            function (Mode2 $mode): array {
                return $mode->toJsonArray();
            },
            Mode2::find()->with('rules')->all()
        );
    }
}
