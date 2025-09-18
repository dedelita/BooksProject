<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\UserRepository;
use App\Repository\BookRepository;
use App\Repository\CommentRepository;
use App\Repository\UserBookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class CommentController extends AbstractController
{
    private $commentRepository;

    public function __construct(CommentRepository $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }
    
    /**
     * @Route("/{id_book}/editComment", name="edit_comment", methods={"GET", "POST"})
     * @IsGranted("ROLE_USER")
     */
    public function add(Request $request, UserRepository $userRepository, BookRepository $bookRepository, UserBookRepository $userbookRepository)
    {
        $user = $this->getUser();
        $idBook = $request->get('id_book');
        $userbook = $userbookRepository->findOneBy(["user" => $user, "book" => $idBook]);
        if ($userbook)
            $comment = $this->commentRepository->findOneByUserBook($userbook);
        
        if(!$comment)
            $comment = new Comment();
        
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $comment->setUserBook($userbook);
            $this->commentRepository->save($comment);
            return $this->redirectToRoute($request->getSession()->get("lastRoute", "home"));
        }
        return $this->render("user/editComment.html.twig", [
            'form' => $form->createView(), 
            "book" => $userbook->getBook(), 
            "com" => $comment, 
            "new" => false
        ]);
    }
}
