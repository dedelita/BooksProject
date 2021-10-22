<?php

namespace App\Controller;

use App\Form\CommentType;
use App\Form\AdminEditUserType;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="app_admin_index")
     */
    public function index()
    {
        return $this->render('admin/index.html.twig', [
        ]);
    }   
    
    /**
     * @Route("/users")
     * @IsGranted("ROLE_ADMIN")
     */
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/users.html.twig', [
            "users" => $users
        ]);
    }
    /**
     * @Route("/comments")
     * @IsGranted("ROLE_ADMIN")
     */
    public function comments(CommentRepository $commentRepository): Response
    {
        $comments = $commentRepository->findAll();
        return $this->render('admin/comments.html.twig', [
            "comments" => $comments
        ]);
    }

    /**
     * @Route("/editUser/{id}")
     * @IsGranted("ROLE_ADMIN")
     */
    public function editUser(Request $request, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($request->get('id'));
        $form = $this->createForm(AdminEditUserType::class, $user);
        $form->handleRequest($request);
        return $this->render("admin/editUser.html.twig", [
            "form" => $form->createView(),
            "user" => $user
        ]);
    }

    /**
     * @Route("/deleteUser/{id}")
     * @IsGranted("ROLE_ADMIN")
     */
    public function deleteUser(Request $request, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($request->get("id"));
        $userRepository->delete($user);
        $this->addFlash('success', 'user ' . $user->getUsername() . ' deleted!');
        return $this->redirectToRoute('app_admin_users');
    }
    /**
     * @Route("/editComment")
     * @IsGranted("ROLE_ADMIN")
     */
    public function editComment(Request $request, CommentRepository $commentRepository): Response
    {
        $comment = $commentRepository->find($request->get("id"));
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $commentRepository->save($comment);
            return $this->redirectToRoute("app_admin_admincomments");
        }

        return $this->render('admin/modal_edit_comment.html.twig', [
            "form" => $form->createView(),
            "comment" => $comment
        ]);
    }
}
