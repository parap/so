<?php

namespace App\Controller;

use App\Repository\QuestionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(QuestionRepository $questionRepository): Response
    {
        return $this->render('homepage.html.twig', [
            'questions' => $questionRepository->findAll(),
            'path' => $this->getParameter('avatars_directory')
//            'registrationForm' => $form->createView(),
        ]);

    }
}
