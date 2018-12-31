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
