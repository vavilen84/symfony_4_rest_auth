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
