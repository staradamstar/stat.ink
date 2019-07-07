<?php
declare(strict_types=1);

use app\assets\EntireWeapons2TrendAsset;
use app\components\widgets\AdWidget;
use app\components\widgets\FA;
use app\components\widgets\GameModeIcon;
use app\components\widgets\SnsWidget;
use app\models\Rule2;
use yii\bootstrap\Nav;
use yii\helpers\Html;
use yii\helpers\Json;

$title = Yii::t('app', 'Weapons');
$this->title = Yii::$app->name . ' | ' . $title;

$this->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary']);
$this->registerMetaTag(['name' => 'twitter:title', 'content' => $title]);
$this->registerMetaTag(['name' => 'twitter:description', 'content' => $title]);
$this->registerMetaTag(['name' => 'twitter:site', 'content' => '@stat_ink']);

EntireWeapons2TrendAsset::register($this);
?>
<div class="container">
  <h1>
    <?= Html::encode($title) . "\n" ?>
  </h1>

  <?= AdWidget::widget() . "\n" ?>  
  <?= SnsWidget::widget() . "\n" ?>

  <nav><?= Nav::widget([
    'options' => [
      'class' =>'nav-tabs',
    ],
    'items' => [
      [
        'label' => 'Splatoon 2',
        'url' => ['entire/weapons2'],
        'active' => true,
      ],
      [
        'label' => 'Splatoon',
        'url' => ['entire/weapons'],
      ],
    ],
  ]) ?></nav>

  <h2><?= Html::encode(Yii::t('app', 'Trends')) ?></h2>
  <script type="application/json" id="months"><?= Json::encode($months) ?></script>
  <script type="application/json" id="weaponTypes"><?= Json::encode(array_values($weaponTypes)) ?></script>
  <script type="application/json" id="dateAndVersions"><?= Json::encode($dateVersion) ?></script>
  <div id="trendLegends"></div>
  <div class="embed-responsive embed-responsive-16by9">
    <?= Html::tag('div', '', [
      'class' => 'graph embed-responsive-item trend-graph',
      'data' => [
        'legend' => '#trendLegends',
        'months' => '#months',
        'target' => '#weaponTypes',
        'xaxis' => '#dateAndVersions',
      ],
    ]) . "\n" ?>
  </div>

  <h2><?= Html::encode(Yii::t('app', 'Stats')) ?></h2>
  <div class="list-group d-inline-block">
<?php foreach (Rule2::getSortedAll(null) as $key => $name) { ?>
    <?= Html::a(
      implode(' ', [
        GameModeIcon::spl2($key),
        Html::encode($name),
      ]),
      ['entire/weapons2-rule',
        'rule' => $key,
        'version' => 'latest',
      ],
      ['class' => 'list-group-item']
    ) . "\n" ?>
<?php } ?>
  </div>
</div>
