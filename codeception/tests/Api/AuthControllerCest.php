<?php

namespace Tests\Api;

use \ApiTester;
use App\Tests\Api\BaseApiCest;

class AuthControllerCest extends BaseApiCest
{
    public function run(ApiTester $I)
    {
        $I->wantTo('Login');
        $this->sendBadLoginRequest($I, '', '', $this->usernameNotFoundMessage);
        $this->sendBadLoginRequest($I, $this->notValidEmail, '', $this->usernameNotFoundMessage);
        $this->sendBadLoginRequest($I, $this->badEmail, '', $this->usernameNotFoundMessage);
        $this->sendBadLoginRequest($I, $this->validEmail, '', $this->invalidCredentialsMessage);
        $this->sendSuccessLoginRequest($I, $this->validEmail, $this->validPassword);
    }
}
