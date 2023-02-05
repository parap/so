<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/profile", name="app_user_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, UserRepository $userRepository,
                         UserPasswordHasherInterface $userPasswordHasher, FileUploader $fileUploader): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatar')->getData();

            if ($avatarFile) {
                try {
                    $newFilename = $fileUploader->upload($avatarFile, $user->getAvatarFileName());
                } catch (FileException $e) {
                    return new Response('Failed to upload image: ' . $e->getMessage());
                }

                $user->setAvatarFileName($newFilename);
            }

            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $form->get('password')->getData())
            );

            $userRepository->add($user, true);

            return $this->redirectToRoute('homepage', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'path' => $this->getParameter('avatars_web_directory')
        ]);
    }
}
