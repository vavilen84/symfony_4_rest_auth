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
