<?php

namespace Tests\Api\Auth;

use \ApiTester;
use App\Tests\Api\BaseApiCest;

class LoginCest extends BaseApiCest
{
    public function run(ApiTester $I)
    {
        $I->wantTo('See user new assignment');
    }
}
