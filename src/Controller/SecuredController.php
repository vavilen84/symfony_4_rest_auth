<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations\Get;

/**
 * @Route("/secured")
 */
class SecuredController extends AbstractController
{
    /**
     * @Get("/index")
     */
    public function index()
    {
        return $this->json(['data' => 'Success!']);
    }
}
