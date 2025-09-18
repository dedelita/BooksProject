<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Comment;
use App\Entity\UserBook;
use App\Form\BookType;
use App\Form\CommentType;
use App\Form\GBookIsbnType;
use App\Form\CheckDeleteCommentType;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\BookRepository;
use App\Repository\UserBookRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\FormError;
use Knp\Component\Pager\PaginatorInterface;

class UserController extends AbstractController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function locale(Request $request)
    {
        $request->getSession()->set("_locale", $this->getParameter("locale"));
        return $this->redirectToRoute("index");
    }
    /**
     * @Route("/home", name="home")
     * @IsGranted("ROLE_USER")
     */
    public function home(Request $request, UserBookRepository $userbookRepository)
    {
        $user = $this->getUser();
        $books = $bookRepository->getUserBooks($user->getId(), 'author');

        $genres = $bookRepository->getMyGenres($user->getId());
        $authors = $bookRepository->getMyAuthors($user->getId());

        return $this->render('user/home.html.twig', ["books" => $books, "genres" => $genres, "authors" => $authors]);
    }

    /**
     * @Route("/authors",name="authors")
     * @IsGranted("ROLE_USER")
     */
    public function getAuthors(Request $request, UserBookRepository $userbookRepository, PaginatorInterface $paginator)
    {
        $user = $this->getUser();
        $request->getSession()->set("lastRoute", "authors");
        $query = $userbookRepository->getUserAuthorsQuery($user)
            ->setHint(
                'knp_paginator.count',
                $userbookRepository->countUserAuthors($user)
            );
        $pagination = $paginator->paginate($query, $request->query->getInt('page', 1), 3);
        $pagination->setCustomParameters(['align' => 'center']);
        return $this->render("user/authors.html.twig", [
            "authors" => $pagination
        ]);
    }
    /**
     * @Route("/books_author/{author}", name="byAuthor")
     * @IsGranted("ROLE_USER")
     */
    public function showBooksOfAuthors(Request $request, UserBookRepository $userbookRepository)
    {
        $user = $this->getUser();
        $author = $request->get("author");
        $books = $userbookRepository->getUserBooksOfAuthor($user, $author);
        return $this->render("books/list_grid.html.twig", ["books" => $books, "page" => "authors"]);
    }

    /**
     * @Route("/addBook", name="add_book")
     * @IsGranted("ROLE_USER")
     */
    public function addBook(Request $request, BookController $bookController, BookRepository $bookRepository,
                CommentRepository $commentRepository, UserBookRepository $userbookRepository)
    {
        $selected = false;
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $request->getSession()->set("lastRoute", "add_book");
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
            $b = $bookRepository->findOneBy(["title" => $book->getTitle(), "author" => $book->getAuthor()]);
            if(!$b)
                $gbooks = $bookController->getGBooks($book->getTitle(), $book->getAuthor(), $book->getLanguage());
            else {
                $this->addUserBook($b, $userbookRepository);
                return $this->redirectToRoute("add_com_book", ["idBook" => $b->getId()]);
            }
        }

        if(sizeof($gbooks) == 1) {
            $book = $gbooks[0];
            $res = $bookRepository->findOneBy(["title" => $book->getTitle(), "author" => $book->getAuthor()]);

            if(!$res) {
                $bookRepository->save($book);
                $res = $book;
            }
            $this->addUserBook($res, $userbookRepository);
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

    private function addUserBook(Book $book, UserBookRepository $userbookRepository)
    {
        $user = $this->getUser();
        $ub = $userbookRepository->findOneBy(["user" => $user, "book" => $book]);
        if(!$ub) {
            $ub = $userbookRepository->add($user, $book);
            $user->addBook($ub);
        } else {
            $ub->setDeleted(false);
            $userbookRepository->save($ub);
        }
        $this->userRepository->save($user);
    }
    /**
     * @Route("/addComment/{idBook}", name="add_com_book")
     * @IsGranted("ROLE_USER")
     */
    public function addComment(Request $request, UserBookRepository $userbookRepository, CommentRepository $commentRepository)
    {
        $idBook = $request->get('idBook');
        $book = $bookRepository->find($idBook);

        $user = $this->getUser();
        $userBook = $userbookRepository->findOneBy(["user" => $user, "book" => $idBook]);

        $comment = $commentRepository->findOneByUserBook($userBook);
        if(!$comment)
            $comment = new Comment();
        $formComment = $this->createForm(CommentType::class, $comment);
        $formComment->handleRequest($request);
        if($formComment->isSubmitted() && $formComment->isValid()) {
            $comment->setUserBook($userBook);
            $commentRepository->save($comment);
            return $this->redirectToRoute($request->getSession()->get("lastRoute", "home"));
        }


        return $this->render("user/editComment.html.twig", [
            "form" => $formComment->createView(),
            "book" => $userBook->getBook(),
            "new" => true
            ]);
    }
    /**
     * @Route("/myBooks", name="get_books")
     * @IsGranted("ROLE_USER")
     */
    public function getBooks(UserBookRepository $userbookRepository, CommentRepository $commentRepository,
            PaginatorInterface $paginator, Request $request)
    {
        $user = $this->getUser();
        $query = $userbookRepository->getUserBooksQuery($user);
        $request->getSession()->set("lastRoute", "get_books");
        $pagination = $paginator->paginate($query, $request->query->getInt('page', 1), 21);
        $pagination->setCustomParameters(['align' => 'center']);
        $selected = true;
        if($request->getSession()->get("booksList") == "line") {
            $selected = false;
        }
        return $this->render('user/books.html.twig', [
            'pagination' => $pagination,
            'selected' => $selected
        ]);
    }

    /**
     * @Route("/myComments", name="get_comments")
     * @IsGranted("ROLE_USER")
     */
    public function getComments(UserBookRepository $userbookRepository, CommentRepository $commentRepository,
            PaginatorInterface $paginator, Request $request)
    {
        $user = $this->getUser();
        $request->getSession()->set("lastRoute", "get_comments");
        $commentsQuery = $commentRepository->findByUserQuery($user);
        $pagination = $paginator->paginate($commentsQuery, $request->query->getInt('page', 1), 10);
        $pagination->setCustomParameters(['align' => 'center']);
        return $this->render("user/comments.html.twig", [
            "pagination" => $pagination
        ]);
    }
    /**
     * @Route("/setBooksList", name="setBooksList")
     */
    public function setBooksList(Request $request) {
        $booksList = $request->get("list");
        $request->getSession()->set("booksList", $booksList);
        return new Response($booksList);
    }

    /**
     * @Route("/removeBook/{idBook}", name="remove_book")
     * @IsGranted("ROLE_USER")
     */
    public function removeBook(Request $request, UserBookRepository $userbookRepository, CommentRepository $commentRepository)
    {
        $idBook = $request->get('idBook');
        $type = $request->get('type');
        $user = $this->getUser();
        $userbook = $userbookRepository->findOneBy(["user" => $user, "book" => $idBook]);

        $comment = $commentRepository->findOneBy(['userBook' => $userbook]);

        $form = $this->createForm(CheckDeleteCommentType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $com = $form->get("deleteCom")->getData();
            if($com) {
                $commentRepository->delete($comment);
                $userbookRepository->delete($userbook);
            } elseif(!$com && $comment) {
                $userbook->setDeleted(true);
                $userbookRepository->save($userbook);
            } else {
                $userbookRepository->delete($userbook);
            }
            $this->userRepository->save($user);
            return $this->redirectToRoute($request->getSession()->get('lastRoute', 'home'));
        }
        return $this->render("modals/confRemoveBook.html.twig", [
            "form" => $form->createView(),
            "id" => $idBook,
            "title" => $userbook->getBook()->getTitle(),
            "comment" => $comment,
            "type" => $type
        ]);
    }

    /**
     * @Route("/edit", name="edit_user")
     * @IsGranted("ROLE_USER")
     */
    public function editUser(Request $request, UserPasswordHasherInterface $passwordEncoder)
    {
        $user = $this->getUser();
        $request->getSession()->set("lastRoute", "edit_user");
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
                        $passwordEncoder->hashPassword(
                            $user,
                            $form->get('plainPassword')->getData()
                        )
                    );
                } else {
                    $form->get('currentPassword')->addError(new FormError($translator->trans('password.bad')));
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
    public function delete(Request $request, CommentRepository $commentRepository)
    {
        $user = $this->getUser();
        if ($user) {
            $this->get('security.token_storage')->setToken(null);
            $commentRepository->deleteByUser($user);
            $this->userRepository->delete($user);
            $request->getSession()->invalidate();
        }

        return $this->redirectToRoute("app_logout");
    }

    /**
     * @Route("/switchLocale", name="switchLocale")
     */
    public function switchLocale(Request $request)
    {
        $user = $this->getUser();
        $locale = $request->getLocale();

        $request->getSession()->set("_locale", $locale);
        $request->setLocale($locale);
        if($user) {
            $user->setPreferredLanguage($locale);
            $this->userRepository->save($user);
        }
        return $this->redirectToRoute($request->getSession()->get('lastRoute', 'index'));
    }
}
