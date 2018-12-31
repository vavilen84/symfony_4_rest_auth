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
        echo 123;
//        $user = $this->getUser();
//        $apiKey = AuthHelper::generateApiKey();
//        $redisService->setApiKey($user, $apiKey);
//        $view = $this->view(['api_key' => $apiKey], 200);

        //return $this->handleView($view);
    }
}
