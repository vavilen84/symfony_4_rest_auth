<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/open")
 */
class OpenController extends AbstractController
{
    /**
     * @Get("/index")
     */
    public function indexAction()
    {
        return $this->json(['data' => 'Success!']);
    }
}
