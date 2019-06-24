<?php
/**
 * @copyright Copyright (C) 2015-2019 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

use app\components\db\Migration;

class m190626_112533_fix_version2_starttime extends Migration
{
    public function safeUp()
    {
        foreach ($this->getList() as $vTag => $times) {
            $this->update(
                'splatoon_version2',
                ['released_at' => $times[0]],
                ['tag' => $vTag]
            );
        }
    }

    public function safeDown()
    {
        foreach ($this->getList() as $vTag => $times) {
            $this->update(
                'splatoon_version2',
                ['released_at' => $times[1]],
                ['tag' => $vTag]
            );
        }
    }

    public function getList(): array
    {
        return [
            '1.1.2' => ['2017-07-27T11:00:00+09:00', '2017-07-27T10:00:00+09:00'],
            '1.3.0' => ['2017-09-08T11:00:00+09:00', '2017-09-08T10:00:00+09:00'],
            '1.4.0' => ['2017-10-11T11:00:00+09:00', '2017-10-11T10:00:00+09:00'],
            '1.4.1' => ['2017-10-20T11:00:00+09:00', '2017-10-20T10:00:00+09:00'],
            '1.4.2' => ['2017-11-01T11:00:00+09:00', '2017-11-01T10:00:00+09:00'],
        ];
    }
}
