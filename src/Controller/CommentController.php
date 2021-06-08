<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\UserRepository;
use App\Repository\BookRepository;
use App\Repository\CommentRepository;
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
    public function add(Request $request, UserRepository $userRepository, BookRepository $bookRepository)
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute("index");
        }

        $user = $this->getUser();
        $idBook = $request->get('id_book');
        $book = $bookRepository->find($idBook);
        if ($user && $book)
            $comment = $this->commentRepository->findOneBy(["writer" => $user->getId(), "book" => $idBook]);
        
        if(!$comment) {
            $comment = new Comment();
        }
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $comment->setBook($book);
            $comment->setWriter($user);
            $this->commentRepository->save($comment);
        }
        return $this->render("user/editComment.html.twig", [
            'form' => $form->createView(), 
            "book" => $book, 
            "com"=>$comment, 
            "new" => false
        ]);
    }
}
