<?php

namespace Tests\Api;

use \ApiTester;
use App\Tests\Api\BaseApiCest;

class OpenControllerCest extends BaseApiCest
{
    public function run(ApiTester $I)
    {
        $I->wantTo('Get open data');
        $I->sendGET('/open/index');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('Success!');
    }
}
