<?php
namespace App\Security;

use App\Entity\User;
use App\Service\UserService;
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

class ApiKeyAuthenticator extends AbstractGuardAuthenticator
{
    const API_KEY_HEADER_ATTRIBUTE_NAME = 'X-API-KEY';

    private $em;
    private $passwordEncoder;
    private $redisService;
    private $userService;

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
            'api_key' => $request->headers->get(self::API_KEY_HEADER_ATTRIBUTE_NAME),
        );
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (empty($credentials['api_key'])) {
            return null;
        }
        $userId = $this->redisService->getUserIdByApiKey($credentials['api_key']);
        $user = $this->userService->getById($userId);
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
            'message' => 'Bad credentials'
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
