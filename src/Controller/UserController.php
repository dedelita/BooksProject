<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Comment;
use App\Form\BookType;
use App\Form\CommentType;
use App\Form\GBookIsbnType;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\BookRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Form\FormError;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class UserController extends AbstractController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }


    /**
     * @Route("/", name="index") 
     */
    public function index(Request $request)
    {
        return $this->render("index.html.twig");
    }

    /**
     * @Route("/home", name="home")
     * @IsGranted("ROLE_USER")
     */
    public function home(BookRepository $bookRepository)
    {
        $user = $this->getUser();

        $genres = $bookRepository->getMyGenres($user->getId());
        $authors = $bookRepository->getMyAuthors($user->getId());
        
        return $this->render('user/home.html.twig', ["genres" => $genres, "authors" =>$authors]);
    }

    /**
     * @Route("/books_genre/{genre}", name="byGenre")
     * @IsGranted("ROLE_USER")
     */
    public function showBooksOfGenre(Request $request, BookRepository $bookRepository)
    {
        $user = $this->getUser();
        $genre = $request->get("genre");
        $books = $bookRepository->getUserBooksOfGenre($user->getId(), $genre);

        return $this->render("user/booksOfGenre.html.twig", ["genre" => $genre, "books" => $books]);
    }

    /**
     * @Route("/books_author/{author}", name="byAuthor")
     * @IsGranted("ROLE_USER")
     */
    public function showBooksOfAuthors(Request $request, BookRepository $bookRepository)
    {
        $user = $this->getUser();
        $author = $request->get("author");
        $books = $bookRepository->getUserBooksOfAuthor($user->getId(), $author);
        return $this->render("user/booksOfAuthor.html.twig", ["author" => $author, "books" => $books]);
    }

    /**
     * @Route("/deleteUser", name="delete_user", methods="DELETE")
     */
    public function delete(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if ($user) {
            $this->userRepository->delete($user);
        }

        return $this->redirectToRoute("index");
    }

    /**
     * @Route("/addBook", name="add_book")
     * @IsGranted("ROLE_USER")
     */
    public function addBook(Request $request, BookController $bookController, BookRepository $bookRepository, CommentRepository $commentRepository)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $gbooks = [];
        $formIsbn = $this->createForm(GBookIsbnType::class);
        $formIsbn->handleRequest($request);
        if($formIsbn->isSubmitted() && $formIsbn->isValid()) {
            $gbooks[] = $bookController->getGBooksByIsbn($formIsbn->get('isbn')->getData());
        }
        
        $book = new Book();
        $formBook = $this->createForm(BookType::class, $book);
        $formBook->handleRequest($request);

        if ($formBook->isSubmitted() && $formBook->isValid()) {
            //9780439023481
            //$gbooks = $bookController->getGBooks("The Hunger Games", "Suzanne Collins", "en");
            //$gbooks = $bookController->getGBooks("Eve of Man", "Giovanna Fletcher Tom Fletcher", "en");
            $gbooks = $bookController->getGBooks($book->getTitle(), $book->getAuthor(), $book->getLanguage());
        }

        if(sizeof($gbooks) == 1) {
            $book = $gbooks[0];
            $res = $bookRepository->findOneBy(["title" => $book->getTitle(), "author" => $book->getAuthor()]);
            
            if(!$res) {
                $bookRepository->save($book);
                $user->addBook($book);
            } else {
                $user->addBook($res);
            }
            $this->userRepository->save($user);
            $res = $bookRepository->findOneBy(["title" => $book->getTitle(), "author" => $book->getAuthor()]);
            
            return $this->redirectToRoute("add_com_book", ["idBook" => $res->getId()]);//$book, $request, $commentRepository);
        }
        
        return $this->render('user/addBook.html.twig', [
            'formBook' => $formBook->createView(),
            'formIsbn' => $formIsbn->createView(),
            'gbooks' => $gbooks
        ]);
    }


    /**
     * @Route("/addComment/{idBook}", name="add_com_book")
     * @IsGranted("ROLE_USER")
     */
    public function addComment(Request $request, CommentRepository $commentRepository, BookRepository $bookRepository)
    {
        $idBook = $request->get('idBook');
        $book = $bookRepository->find($idBook);
        var_dump($book);
        $user = $this->getUser();
        $comment = new Comment();
        $formComment = $this->createForm(CommentType::class, $comment);
        $formComment->handleRequest($request);
        if($formComment->isSubmitted() && $formComment->isValid()) {
            var_dump($comment);
            $comment->setBook($book);
            $comment->setWriter($user);
            //$comment = $commentRepository->add($comment->getContent(), $comment->getStars(), $user, $book);
            $commentRepository->save($comment);
            return $this->redirectToRoute('home');
        }
        

        return $this->render("user/addComment.html.twig", [
            "form" => $formComment->createView(),
            "book" => $book
            ]);
    }
    /**
     * @Route("/myBooks", name="get_books", methods="GET")
     * @IsGranted("ROLE_USER")
     */
    public function getBooks(CommentRepository $commentRepository, BookRepository $bookRepository)
    {
        $user = $this->getUser();
        $books = $bookRepository->getUserBooks($user->getId(), 'author');

        foreach ($books as $book) {
            $com = $commentRepository->findOneBy([
                'book' => $book->getId(),
                'writer' => $user->getId(),
            ]);
            if ($com) {
                $book->addComment($com);
            }
        }
        return $this->render('user/books.html.twig', ['books' => $books]);
    }

    /**
     * @Route("/removeBook/{idBook}", name="remove_book")
     * @IsGranted("ROLE_USER")
     */
    public function removeBook(Request $request)
    {
        $user = $this->getUser();
        $book = $this->bookRepository->find($request->get('idBook'));
        $user->removeBook($book);
        $this->userRepository->save($user);

        return $this->redirectToRoute("home");
    }

    /**
     * @Route("/edit", name="edit_user")
     * @IsGranted("ROLE_USER")
     */
    public function editUser(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $u = $this->userRepository->findOneByEmail($user->getEmail());
            if ($u && $u->getId() != $user->getId()) {
                $form->addError(
                    new FormError('Email already used by someone else')
                );
            }
            $u = $this->userRepository->findOneByUsername($user->getUsername());
            if ($u && $u->getId() != $user->getId()) {
                $form->addError(new FormError('Username already used by someone else'));
            }
            $password = $form->get('currentPassword')->getData();
            if ($form->get('changePassword')->getData()) {
                if ($passwordEncoder->isPasswordValid($user, $password)) {
                    $user->setPassword(
                        $passwordEncoder->encodePassword(
                            $user,
                            $form->get('plainPassword')->getData()
                        )
                    );
                } else {
                    $form->addError(new FormError('Wrong Password'));
                }
            }
            
            $this->userRepository->save($user);
        }

        return $this->render('security/editUser.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
