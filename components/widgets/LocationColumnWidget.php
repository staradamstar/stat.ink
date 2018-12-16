<?php
/**
 * @copyright Copyright (C) 2015-2018 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

declare(strict_types=1);

namespace app\components\widgets;

use GeoIp2\Model\City;
use Yii;
use hiqdev\assets\flagiconcss\FlagIconCssAsset;
use statink\yii2\jdenticon\Jdenticon;
use yii\base\Widget;
use yii\helpers\Html;

class LocationColumnWidget extends Widget
{
    public $geoip;
    private $cityInfo = false;

    public $remoteAddr;
    public $remoteAddrMasked;
    public $remoteHost;

    public function init()
    {
        parent::init();
        if (!$this->geoip) {
            $this->geoip = Yii::$app->geoip;
        }
    }

    public function run()
    {
        return Html::tag(
            'div',
            implode('', [
                $this->renderJdenticon(),
                $this->renderTexts(),
            ]),
            [
                'id' => $this->id,
                'class' => [
                    'd-flex',
                ],
            ]
        );
    }

    protected function renderJdenticon(): string
    {
        return Jdenticon::widget([
            'hash' => $this->getJdenticonHash(),
            'params' => [
                'style' => [
                    'width' => '2em',
                    'height' => '2em',
                    'flex' => '0 0 2em',
                ],
            ],
        ]);
    }

    protected function getJdenticonHash(): string
    {
        return hash(
            'sha256',
            $this->remoteAddrMasked
                ? $this->remoteAddrMasked
                : $this->remoteAddr
        );
    }

    protected function renderTexts(): string
    {
        return Html::tag(
            'div',
            implode('', array_map(
                function (string $html): string {
                    return Html::tag('div', $html);
                },
                array_filter([
                    $this->renderLocation(),
                    $this->renderIpAddress(),
                ])
            )),
            [
                'style' => [
                    'flex' => '1 1 auto',
                ],
            ]
        );
    }

    protected function renderLocation(): ?string
    {
        if (!$this->remoteAddr) {
            return null;
        }

        try {
            $city = $this->getCityInfo();
            if (!$city) {
                return null;
            }

            return implode(' ', array_filter([
                $this->renderLocationText($city),
                $this->renderLocationIcon($city),
            ]));
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function renderLocationText(City $city): ?string
    {
        $get = function ($obj): ?string {
            if (!$obj) {
                return null;
            }

            $lang = $this->geoip->lang;
            return isset($obj->names[$lang])
                ? $obj->names[$lang]
                : $obj->name;
        };

        return Html::encode(implode(', ', array_filter([
            $get($city->city),
            $get($city->mostSpecificSubdivision),
            $get($city->country),
        ])));
    }

    protected function renderLocationIcon(City $city): ?string
    {
        if (!$country = $city->country) {
            return null;
        }

        FlagIconCssAsset::register($this->view);
        return Html::tag('span', '', [
            'class' => [
                'flag-icon',
                'flag-icon-' . strtolower($country->isoCode),
            ],
        ]);
    }

    protected function renderIpAddress(): ?string
    {
        if (!$this->remoteAddr) {
            return null;
        }

        if ($this->remoteHost) {
            return Html::tag(
                'span',
                Html::encode(strtolower($this->remoteHost)),
                ['title' => $this->remoteAddr, 'class' => 'auto-tooltip']
            );
        }

        if (strpos($this->remoteAddr, ':') !== false && $this->remoteAddrMasked) {
            return Html::tag(
                'span',
                Html::encode(strtolower($this->remoteAddrMasked)),
                ['title' => $this->remoteAddr, 'class' => 'auto-tooltip']
            );
        }

        return Html::tag('span', Html::encode($this->remoteAddr));
    }

    private static function getGeoIpLang(): string
    {
        $lang = Yii::$app->language;
        switch (substr($lang, 0, 2)) {
            case 'de':
            case 'en':
            case 'es':
            case 'fr':
            case 'ja':
            case 'ru':
                return substr($lang, 0, 2);

            case 'zh':
                return ($lang === 'zh-CN')
                    ? 'zh-CN'
                    : 'en';

            case 'pt':
                return 'pt-BR';

            default:
                return 'en';
        }
    }

    private function getCityInfo(): ?City
    {
        if ($this->cityInfo === false) {
            try {
                $this->cityInfo = $this->geoip->city($this->remoteAddr);
            } catch (\Exception $e) {
                var_dump($e);
                exit;
                $this->cityInfo = null;
            }
        }
        return $this->cityInfo;
    }
}