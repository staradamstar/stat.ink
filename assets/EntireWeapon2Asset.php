<?php
/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\assets;

use Yii;
use app\assets\StatByMapRuleAsset;
use app\assets\TableResponsiveForceAsset;
use jp3cki\yii2\flot\FlotAsset;
use jp3cki\yii2\flot\FlotPieAsset;
use jp3cki\yii2\flot\FlotTimeAsset;
use statink\yii2\sortableTable\SortableTableAsset;
use yii\helpers\ArrayHelper;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class EntireWeapon2Asset extends AssetBundle
{
    public $sourcePath = '@app/resources/.compiled/stat.ink';
    public $js = [
        'weapon2.js',
    ];
    public $depends = [
        BabelPolyfillAsset::class,
        ColorSchemeAsset::class,
        FlotAsset::class,
        FlotPieAsset::class,
        FlotTimeAsset::class,
        JqueryAsset::class,
        SortableTableAsset::class,
        StatByMapRuleAsset::class,
        TableResponsiveForceAsset::class,
    ];
}