<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\User;
use App\Form\AnswerType;
use App\Repository\AnswerRepository;
use App\Repository\RatingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/answer")
 */
class AnswerController extends AbstractController
{
    /**
     * @Route("/", name="app_answer_index", methods={"GET"})
     */
    public function index(AnswerRepository $answerRepository): Response
    {
        return $this->render('answer/index.html.twig', [
            'answers' => $answerRepository->findByUser($this->getUser()),
        ]);
    }

    /**
     * @Route("/new/{id}", name="app_answer_new", methods={"GET", "POST"})
     *
     * @ParamConverter("question")
     */
    public function new(Request $request, AnswerRepository $answerRepository, Question $question): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canAnswerQuestion($question)) {
            return new Response('You cannot answer this question because it is your question or you have already gave answer');
        }

        $answer = new Answer();
        $answer->setUser($user);
        $answer->setQuestion($question);
        $form = $this->createForm(AnswerType::class, $answer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $answerRepository->add($answer, true);

            return $this->redirectToRoute('app_answer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('answer/new.html.twig', [
            'answer' => $answer,
            'question' => $question,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_answer_show", methods={"GET"})
     */
    public function show(Answer $answer): Response
    {
        return $this->render('answer/show.html.twig', [
            'answer' => $answer,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_answer_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Answer $answer, AnswerRepository $answerRepository): Response
    {
        if ($this->getUser() !== $answer->getUser()) {
            return new Response('Cannot edit answer of another user');
        }

        $form = $this->createForm(AnswerType::class, $answer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $answerRepository->add($answer, true);

            return $this->redirectToRoute('app_answer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('answer/edit.html.twig', [
            'answer' => $answer,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/vote-up/{id}", name="vote_up", methods={"GET", "POST"})
     *
     * @return JsonResponse|Response
     * @throws \TypeError
     */
    public function voteUp(Answer $answer, RatingRepository $ratingRepository, AnswerRepository $answerRepository)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canVoteUp($answer)) {
            return new Response('Could not vote answer up');
        }

        $rating = $user->voteUp($answer);

        $ratingRepository->add($rating, true);
        $answer->increaseTotalRatingsCount();

        $ratingRepository->add($rating, true);
        $answerRepository->add($answer, true);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/vote-down/{id}", name="vote_down", methods={"GET", "POST"})
     * @return JsonResponse|Response
     * @throws \TypeError
     */
    public function voteDown(RatingRepository $ratingRepository, Answer $answer, AnswerRepository $answerRepository)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canVoteDown($answer)) {
            return new Response('Could not vote answer down');
        }

        $rating = $user->voteDown($answer);
        $answer->decreaseTotalRatingsCount();

        $ratingRepository->add($rating, true);
        $answerRepository->add($answer, true);

        return new JsonResponse(['success' => true]);
    }
}
