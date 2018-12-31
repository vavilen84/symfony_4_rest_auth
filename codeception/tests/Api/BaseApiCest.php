<?php

namespace App\Tests\Api;

use \ApiTester;
use App\DataFixtures\AppFixtures;

class BaseApiCest
{
    protected $validEmail;
    protected $validPassword;
    protected $notValidEmail = 'not_email';
    protected $badEmail = 'invalid@email.com';
    protected $invalidPassword = 'invalid_password';
    protected $loginUrl = '/auth/login';
    protected $usernameNotFoundMessage = 'Username could not be found';
    protected $invalidCredentialsMessage = 'Invalid credentials';
    protected $securePageUrl = '/secured/index';
    protected $apiKeyHeaderName = 'X-API-KEY';

    public function _before(ApiTester $I)
    {
        $this->setRequestData();
        $this->setRequestHeaders($I);
    }

    protected function setRequestData()
    {
        $this->validEmail = AppFixtures::USERS[1]['email'];
        $this->validPassword = AppFixtures::USERS[1]['password'];
    }

    protected function setRequestHeaders(ApiTester $I)
    {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    protected function sendBadLoginRequest(ApiTester $I, $email, $password, $message)
    {
        $I->sendPOST('/auth/login', [
            'email' => $email,
            'password' => $password
        ]);
        $I->cantSeeResponseCodeIs(200);
        $I->seeResponseContains($message);
    }

    protected function sendSuccessLoginRequest(ApiTester $I, $email, $password): string
    {
        $I->sendPOST('/auth/login', [
            'email' => $email,
            'password' => $password
        ]);
        $I->seeResponseCodeIs(200);
        $apiKey = $this->getApiKeyFromResponse($I);
        $I->assertEquals(!empty($apiKey), true, "Api Key is empty");

        return $apiKey;
    }

    protected function getApiKeyFromResponse(ApiTester $I)
    {
        $response = $I->grabDataFromResponseByJsonPath('$.');
        $result = $response[0]['api_key'] ?? null;

        return $result;
    }
}
