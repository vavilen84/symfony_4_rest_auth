# Implement Symfony 4 based REST authentication

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
Status 403
{
    "message": "Invalid credentials"
}
```
Requests to "open" area always should give 200 response code 
Api Key should be stored in Redis

## Implementation

### Create application skeleton
Use my application skeleton from here [https://github.com/vavilen84/symfony_4_basic_skeleton](https://github.com/vavilen84/symfony_4_basic_skeleton)<br>
or create you own by running command:
```
$ composer create-project symfony/skeleton "4.*" --stability=dev
```
Further information contains docker commands, so I recommend to use my skeleton for investigation.

### Security bundle
[official docs](https://symfony.com/doc/current/security.html)<br>
add this line to composer.json 
```yaml
"symfony/security-bundle": "^4.0",
```
and install(or update) dependencies

```
$ docker exec -it --user 1000 symfony4restauth_php_1 composer update
```

## User Entity 

#### Generate entity:
```
$ docker exec -it --user 1000 symfony4restauth_php_1 bin/console make:user

```
submit default values during generating entity

#### Add email validator

```php
<?php
// src/Entity/User.php

use Symfony\Component\Validator\Constraints as Assert;

...

/**
 * @Assert\Email()
 * @ORM\Column(type="string", length=255)
 */
private $email;

...

```
 
#### Add EntityListener (for password encoding)

[official docs](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#lifecycle-events)<br>

```php
<?php
// src/EntityListener/UserListener.php

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

```yaml
# config/services.yaml:
services:
  app.enity_listener.user_listener:
        class: App\EntityListener\UserListener
        tags:
            - { name: doctrine.orm.entity_listener, lazy: true }
```

Add a new line to User entity

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

```yaml
# config/security.yaml

security:
    firewalls:
        auth:
            stateless: true
            pattern: ^/auth
            logout: ~
            anonymous: ~
            guard:
                authenticators:
                  - app.security.auth_login_authenticator
        secured:
            stateless: true
            pattern: ^/secured
            logout: ~
            anonymous: ~
            guard:
                authenticators:
                  - app.security.api_key_authenticator

```

## Helpers

## Add helper (for super-secure api_key hash generating :-)) 

```php
<?php
// src/Helpers/AuthHelper.php
namespace App\Helpers;

class AuthHelper
{
    const API_KEY_HEADER_NAME = 'X-API-KEY';
    
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

```php
<?php
// src/Service/Redis.php
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
 
This is the main part of authentication

### Login Authenticator (for login action only)

This authenticator will check email and password on login action

```php
<?php
// src/Security/ApiKeyAuthenticator.php

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
use App\Service\RedisService;
use App\Helpers\AuthHelper;

class ApiKeyAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private $passwordEncoder;
    private $redisService;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder,
        RedisService $redisService)
    {
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;
        $this->redisService = $redisService;
    }

    public function supports(Request $request)
    {
        return true;
    }

    public function getCredentials(Request $request)
    {
        return array(
            'api_key' => $request->headers->get(AuthHelper::API_KEY_HEADER_NAME),
        );
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (empty($credentials['api_key'])) {
            return null;
        }
        $userId = $this->redisService->getUserIdByApiKey($credentials['api_key']);
        $userRepository = $this->em->getRepository(User::class);
        $user = $userRepository->find($userId);
        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {

    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = array(
            'message' => 'Invalid credentials'
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

If email and password okay - then AuthController loginAction will generate api key and return it in a response. 
This api key is required as X-API-KEY header for SecuredController actions. 

### ApiKey Authenticator (for secured area)

This authenticator validate X-API-KEY header for SecuredController actions

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
use App\Service\RedisService;
use App\Helpers\AuthHelper;

class ApiKeyAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private $passwordEncoder;
    private $redisService;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder,
        RedisService $redisService)
    {
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;
        $this->redisService = $redisService;
    }

    public function supports(Request $request)
    {
        return true;
    }

    public function getCredentials(Request $request)
    {
        return array(
            'api_key' => $request->headers->get(AuthHelper::API_KEY_HEADER_NAME),
        );
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (empty($credentials['api_key'])) {
            return null;
        }
        $userId = $this->redisService->getUserIdByApiKey($credentials['api_key']);
        $userRepository = $this->em->getRepository(User::class);
        $user = $userRepository->find($userId);
        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {

    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = array(
            'message' => 'Invalid credentials'
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

you can generate controller by command
```
$ docker exec -it --user 1000 symfony4restauth_php_1 bin/console make:controller
```

### AuthController
```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Helpers\AuthHelper;
use App\Service\RedisService;

/**
 * @Route("/auth")
 */
class AuthController extends AbstractController
{
    /**
     * @Route("/login")
     */
    public function loginAction(RedisService $redisService)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $apiKey = AuthHelper::generateApiKey();
        $redisService->setApiKey($user, $apiKey);

        return $this->json(['api_key' => $apiKey]);
    }
}

```
### OpenController
```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/open")
 */
class OpenController extends AbstractController
{
    /**
     * @Route("/index")
     */
    public function indexAction()
    {
        return $this->json(['data' => 'Success!']);
    }
}

```
### SecuredController
```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/secured")
 */
class SecuredController extends AbstractController
{
    /**
     * @Route("/index")
     */
    public function index()
    {
        return $this->json(['data' => 'Success!']);
    }
}

```

Thats all!
[Project repository link](https://github.com/vavilen84/symfony_4_rest_auth)







