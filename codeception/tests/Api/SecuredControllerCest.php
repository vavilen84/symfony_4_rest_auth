<?php

namespace Tests\Api;

use \ApiTester;
use App\Tests\Api\BaseApiCest;
use App\Helpers\AuthHelper;

class SecuredControllerCest extends BaseApiCest
{
    public function run(ApiTester $I)
    {
        $I->wantTo('Get secure data');
        $this->dontSeeData($I);
        $this->dontSeeDataWithEmptyApiKeyHeader($I);
        $this->dontSeeDataWithInvalidApiKeyHeader($I);
        $this->seeDataWithValidApiKeyHeader($I);
    }

    protected function dontSeeDataWithEmptyApiKeyHeader(ApiTester $I)
    {
        $I->haveHttpHeader(AuthHelper::API_KEY_HEADER_NAME, '');
        $this->dontSeeData($I);
    }

    protected function dontSeeDataWithInvalidApiKeyHeader(ApiTester $I)
    {
        $I->haveHttpHeader(AuthHelper::API_KEY_HEADER_NAME, 'invalid');
        $this->dontSeeData($I);
    }

    protected function seeDataWithValidApiKeyHeader(ApiTester $I)
    {
        $apiKey = $this->sendSuccessLoginRequest($I, $this->validEmail, $this->validPassword);
        $I->haveHttpHeader(AuthHelper::API_KEY_HEADER_NAME, $apiKey);
        $this->seeData($I);
    }

    protected function dontSeeData(ApiTester $I)
    {
        $I->sendGET($this->securePageUrl);
        $I->cantSeeResponseCodeIs(200);
        $I->seeResponseContains($this->invalidCredentialsMessage);
    }

    protected function seeData(ApiTester $I)
    {
        $I->sendGET($this->securePageUrl);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('Success!');
    }
}
