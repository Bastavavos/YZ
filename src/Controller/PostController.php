<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
final class PostController extends AbstractController
{
    #[Route(name: 'app_post_index', methods: ['GET'])]
    public function index(Request $request, PostRepository $postRepository): Response
    {
        $search = $request->query->get('search');
        if ($search) {
            $posts = $postRepository->findBySearch($search);
        } else {
            $posts = $postRepository->findPosts();
        }

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);

//        return $this->render('post/index.html.twig', [
//            'posts' => $postRepository->findPosts(),
//        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté pour créer un post.'], Response::HTTP_UNAUTHORIZED);
        }

        $parentId = $request->query->get('parent'); // On récupère l'ID du parent
        $post = new Post();

        if ($parentId) {
            $parentPost = $entityManager->getRepository(Post::class)->find($parentId);
            if ($parentPost) {
                $post->setParent($parentPost); // définis le parent
            }
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('picture')->getData();
            if ($file) {
                $filename = $post->getId() . '.' . $file->getClientOriginalExtension();
                $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/posts', $filename);
                $post->setPicture($filename);
            }

            $post->setAuthor($user);
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/show', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_post_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté pour supprimer un post.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($post->getAuthor()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Vous ne pouvez pas supprimer un post que vous n\'avez pas écrit.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }
}
