<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Component\Routing\Annotation\Route;
use App\Helpers\AuthHelper;
use App\Service\RedisService;

/**
 * @Route("/auth")
 */
class AuthController extends AbstractController
{
    /**
     * @Post("/login")
     */
    public function loginAction(RedisService $redisService)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $apiKey = AuthHelper::generateApiKey();
        $redisService->setApiKey($user, $apiKey);

        return $this->json(['api_key' => $apiKey]);
    }
}
