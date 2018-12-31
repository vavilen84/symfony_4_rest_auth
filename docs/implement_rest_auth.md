# Implement symfony 4 based REST authentication

## Authentication flow:
Login Request:
```yaml
POST /auth/login
{
    "email": "user@example.com"
    "password": "secretpassword"
}
```
Response:
```yaml
Status 200
{
    "api_key": "s8g7v6wl5jh6lk2lks09bdd87fg76as0"
}
```
Go to secured area 
```yaml
HEADER X-API-KEY: s8g7v6wl5jh6lk2lks09bdd87fg76as0
GET /secured/page
```
Response:
```yaml
Status 200
{
    "content": "Success!"
}
```
Go to secured area without X-API-KEY header should cause 401 response
```yaml
Status 401
{
    "message": "Authentication required"
}
```
Requests to "open" area always should give 200 response code 

Redis will store our api keys

## Implementation

### Create application skeleton
First of all I have taken my variation of base symfony skeleton from here [https://github.com/vavilen84/symfony_4_basic_skeleton](https://github.com/vavilen84/symfony_4_basic_skeleton)
But if you need your custom skeleton - follow official [docs](https://symfony.com/doc/2.6/cookbook/install/index.html) or use a command
```yaml
composer create-project symfony/skeleton "4.*" --stability=dev

```

### Add next lines to composer.json
```yaml
"symfony/security-bundle": "^4.0",
"symfony/serializer": "^4.0",
"friendsofsymfony/rest-bundle": "^2.4"
```
and install required bundles
```
$ docker exec -it --user 1000 symfony4restauth_php_1 composer update
```

This project contains only simple basic implementation - so our classes are very simple.

## Configure FOS rest bundle
[official docs](https://symfony.com/doc/master/bundles/FOSRestBundle/index.html)
```yaml
config/ros_rest.yml

fos_rest:
    routing_loader: true
    view:
        view_response_listener:  true
    format_listener:
        rules:
            - { path: ^/, prefer_extension: true, fallback_format: json, priorities: [ json ] }

```

## User Entity 

#### Generate entity:
```
$ docker exec -it --user 1000 symfony4restauth_php_1 bin/console make:user

```

submit default values

#### Add email validator

```php
//src/Entity/User.php

use Symfony\Component\Validator\Constraints as Assert;

...

/**
 * @Assert\Email()
 * @ORM\Column(type="string", length=255)
 */
private $email;

...

```
 
#### Add EntityListener (for password encoding) ()

[official docs](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#lifecycle-events)

create a new file src/EntityListener/UserListener.php 

```php
<?php
//src/EntityListener/UserListener.php

namespace App\EntityListener;

use App\Entity\User;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserListener
{
    protected $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function prePersist(User $user)
    {
        $this->hashPassword($user);
    }

    public function preUpdate(User $user, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('password')) {
            $this->hashPassword($user);
        }
    }

    protected function hashPassword(User $user)
    {
        $password = $user->getPassword();
        $encodedPassword = $this->encoder->encodePassword($user, $password);
        $user->setPassword($encodedPassword);
    }
}


```

#### Register listener.
 
Add to config/services.yaml:
```yaml
services:
  app.enity_listener.user_listener:
        class: App\EntityListener\UserListener
        tags:
            - { name: doctrine.orm.entity_listener, lazy: true }
```

Add new line to entity
```php
<?php
// src/Entity/User.php
/**
 * @ORM\EntityListeners({"App\EntityListener\UserListener"})
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface

```

#### Add table name. 

My advice - don`t give a "user" name to a table if you use PostgreSQL - "user" is a special name and there possible 
problems with it. Choose another name.

```php
<?php
// src/Entity/User.php
/**
 * @ORM\Table(name="user_table")
 * @ORM\EntityListeners({"App\EntityListener\UserListener"})
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface
```

## Setup security component
add new lines to security.firewalls section
```yaml
//config/security.yaml

security:
    firewalls:
        auth:
            pattern: ^/auth
            logout: ~
            anonymous: ~
            guard:
                authenticators:
                  - app.security.auth_login_authenticator
        secured:
            pattern: ^/secured
            logout: ~
            anonymous: ~
            guard:
                authenticators:
                  - app.security.api_key_authenticator

```

## Helpers

## Add helper (for super-secure api_key hash generating :-)) 
create src/Helpers/AuthHelper.php
```php
<?php

namespace App\Helpers;

class AuthHelper
{
    public static function generateApiKey(): string
    {
        $result = sha1(self::generateRandomString());

        return $result;
    }

    public static function generateRandomString($length = 10): string
    {
        $result = substr(
            str_shuffle(
                str_repeat(
                    $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    ceil($length / strlen($x)
                    )
                )
            ), 1, $length
        );

        return $result;
    }
}
```

## Services

### Redis service

create new file src/Service/Redis.php
```php
<?php

namespace App\Service;

use App\Entity\User;
use Redis;

class RedisService
{
    const HOST = 'redis';
    const PORT = 6379;
    const API_KEY_DB = 1; // api_key => user_id

    protected $client;

    public function __construct()
    {
        $redis = new Redis();
        $redis->connect(self::HOST, self::PORT);
        $this->client = $redis;
    }

    public function setApiKey(User $user, string $apiKey)
    {
        $this->client->select(self::API_KEY_DB);
        $this->client->append($apiKey, $user->getId());
    }

    public function getUserIdByApiKey(string $apiKey)
    {
        $this->client->select(self::API_KEY_DB);
        $result = $this->client->get($apiKey);

        return $result;
    }
}

```

## Auth Guards 
 
### Login Authenticator (for login action only)
Create new file src/Security/ApiKeyAuthenticator.php
```php
<?php
namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Service\UserService;

class AuthLoginAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private $passwordEncoder;
    private $userService;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder, UserService $userService)
    {
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;
        $this->userService = $userService;
    }

    public function supports(Request $request)
    {
        return true;
    }

    public function getCredentials(Request $request): array
    {
        $data = $this->getRequestData($request);

        return [
            'email' => $data['email'] ?? null,
            'password' => $data['password'] ?? null
        ];
    }

    protected function getRequestData(Request $request): array
    {
        $requestBody = $request->getContent();
        $requestData = @json_decode($requestBody, true);

        return $requestData;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $user = $this->userService->findByEmail($credentials['email']);

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $password = $credentials['password'];
        if ($this->passwordEncoder->isPasswordValid($user, $password)) {
            return true;
        }

        return false;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $request->request->set('key', 'value');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = array(
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        );

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = array(
            'message' => 'Authentication Required'
        );

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}

```

## Controllers

We will have 3 controllers:<br>
AuthController - contain login method<br>
OpenController - should give 200 response for both (authenticated and non-authenticated) user types
SecuredController - should return 401 for non-authentiacted users and 200 for authenticated 

### AuthController

### Generate  controllers
```
$ docker exec -it --user 1000 symfony4restauth_php_1 bin/console make:controller
```






