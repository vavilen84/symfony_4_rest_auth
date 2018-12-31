<?php

namespace App\Tests\Api;

use \ApiTester;

class BaseApiCest
{
    protected $apiKey;
    protected $userEmail;
    protected $userPassword = '123456qwertyQ1!';

    public function _before(ApiTester $I)
    {
//        $this->setHomePathAlias();
//        $this->setHttpHeaders($I);
//        $this->cleanDb();
//        $this->setDefaults();
    }

    protected function setDefaults()
    {
//        $this->userEmail = UserFixtures::USER_1['email'];
//        $this->userPassword = UserFixtures::PASSWORD;
    }

    protected function setHomePathAlias()
    {
//        Yii::setAlias('@home', '/var/www/revolve');
    }

    protected function setHttpHeaders(ApiTester $I)
    {
//        $I->haveHttpHeader('Accept', 'application/json');
//        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    protected function cleanDb()
    {
//        ConsoleHelper::runConsoleCommand('database/drop-tables');
//        ConsoleHelper::runConsoleCommand('database/restore-dump');
    }

    protected function login(ApiTester $I)
    {
//        $I->sendPOST('auth/login', [
//            'email' => $this->userEmail,
//            'password' => $this->userPassword
//        ]);
//        $I->seeResponseCodeIs(200);
//        $I->seeResponseContains('api_key');
//        $this->setApiKey($I);
    }

    protected function setApiKey(ApiTester $I)
    {
//        $response = $I->grabDataFromResponseByJsonPath('$.');
//        $this->apiKey = $response[0]['result']['api_key'];
    }
}
