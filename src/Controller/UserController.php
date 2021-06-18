<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Comment;
use App\Form\BookType;
use App\Form\CommentType;
use App\Form\GBookIsbnType;
use App\Form\CheckDeleteCommentType;
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
     * @Route("/home", name="home")
     * @IsGranted("ROLE_USER")
     */
    public function home(Request $request, BookRepository $bookRepository)
    {
        $user = $this->getUser();
        $books = $bookRepository->getUserBooks($user->getId(), 'author');
        $genres = $bookRepository->getMyGenres($user->getId());
        $authors = $bookRepository->getMyAuthors($user->getId());
        
        return $this->render('user/home.html.twig', ["books" => $books, "genres" => $genres, "authors" => $authors]);
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
     * @Route("/authors",name="authors")
     * @IsGranted("ROLE_USER")
     */
    public function getAuthors(Request $request, BookRepository $bookRepository) {
        $user = $this->getUser();
        $authors = $bookRepository->getMyAuthors($user->getId());

        $list_authors = [];
        foreach ($authors as $a) {
            $author = [];
            $author["name"] = $a;
            $author["books"] = $bookRepository->findByAuthor($a);
            $list_authors[] = $author;
        }
        return $this->render("user/authors.html.twig", [
            "authors" => $list_authors
        ]);
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
     * @Route("/addBook", name="add_book")
     * @IsGranted("ROLE_USER")
     */
    public function addBook(Request $request, BookController $bookController, BookRepository $bookRepository, CommentRepository $commentRepository)
    {
        $selected = false;
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
            $author = preg_replace('/[^a-zA-Z0-9%\[\]\ \(\)%&-]/s', '', $book->getAuthor());
            $book->setAuthor($author);
            $b = $bookRepository->findOneBy(["title" => $book->getTitle(), "author" => $author]);
            if(!$b)
                $gbooks = $bookController->getGBooks($book->getTitle(), $author, $book->getLanguage());
            else {
                $user->addBook($b);
                $this->userRepository->save($user);
                return $this->redirectToRoute("add_com_book", ["idBook" => $b->getId()]);
            }
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
            
            return $this->redirectToRoute("add_com_book", ["idBook" => $res->getId()]);
        } elseif($formBook->isSubmitted()) {
            $selected = true;
        }
        
        return $this->render('user/addBook.html.twig', [
            'formBook' => $formBook->createView(),
            'formIsbn' => $formIsbn->createView(),
            'gbooks' => $gbooks, 
            'selected' => $selected
        ]);
    }

    /**
     * @Route("/addComment/{idBook}", name="add_com_book")
     * @IsGranted("ROLE_USER")
     */
    public function addComment(Request $request, BookRepository $bookRepository, CommentRepository $commentRepository)
    {
        $idBook = $request->get('idBook');
        $book = $bookRepository->find($idBook);
        
        $user = $this->getUser();
        $comment = $commentRepository->findOneBy([
            'book' => $book->getId(),
            'writer' => $user->getId(),
        ]);
        if(!$comment)
            $comment = new Comment();
        $formComment = $this->createForm(CommentType::class, $comment);
        $formComment->handleRequest($request);
        if($formComment->isSubmitted() && $formComment->isValid()) {
            $comment->setBook($book);
            $comment->setWriter($user);
            $commentRepository->save($comment);
            return $this->redirectToRoute('home');
        }
        
        return $this->render("user/editComment.html.twig", [
            "form" => $formComment->createView(),
            "book" => $book,
            "new" => true
            ]);
    }
    /**
     * @Route("/myBooks", name="get_books")
     * @IsGranted("ROLE_USER")
     */
    public function getBooks(BookRepository $bookRepository, CommentRepository $commentRepository)
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
        return $this->render('user/books.html.twig', ['books' => $books, 'selected' => true]);
    }

    /**
     * @Route("/removeBook/{idBook}", name="remove_book")
     * @IsGranted("ROLE_USER")
     */
    public function removeBook(Request $request, BookRepository $bookRepository, CommentRepository $commentRepository)
    {
        $idBook = $request->get('idBook');
        $type = $request->get('type');
        $book = $bookRepository->find($idBook);
        $user = $this->getUser();

        $comment = $commentRepository->findOneBy([
            'book' => $book->getId(),
            'writer' => $user->getId(),
        ]);
        
        $form = $this->createForm(CheckDeleteCommentType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $com = $form->get("deleteCom")->getData();
            if($com) {
                $commentRepository->delete($comment);
            }
            $user->removeBook($book);
            $this->userRepository->save($user);
            return $this->redirectToRoute("home");
        }
        return $this->render("modals/confRemoveBook.html.twig", [
            "form" => $form->createView(),
            "id" => $idBook,
            "title" => $book->getTitle(),
            "comment" => $comment,
            "type" => $type
        ]);
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

    /**
     * @Route("/deleteUser", name="delete_user")
     * @IsGranted("ROLE_USER")
     */
    public function delete(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if ($user) {
            $this->userRepository->delete($user);
        }

        return $this->redirectToRoute("app_login");
    }

}
