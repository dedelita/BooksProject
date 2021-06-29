<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function save($book) {
        $this->_em->persist($book);
        $this->_em->flush();
    }

    public function add($author, $title, $genre) {
        $book = new Book();
        $book->setAuthor($author);
        $book->setTitle($title);
        $book->setGenre($genre);
        $this->save($book);

        return $book;
    }

    public function delete($book) {
        $this->_em->remove($book);
        $this->_em->flush();
    }

    public function findBook($title, $author) {
        return $this->createQueryBuilder('b')
            ->andWhere('b.title = :title AND b.author = :author')
            ->setParameter('title', $title)
            ->setParameter('author', $author)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createGetUserBooks($userId) {
        return $this->createQueryBuilder('b')
            ->innerJoin('App:User', 'u', 'WITH', 'u.id = :id')
            ->innerJoin('u.books', 'ub', 'WITH', 'b.id = ub.id')
            ->setParameter('id', $userId);
    }

    public function getUserBooksQuery($userId, $orderBy = null) {
        $q = $this->createGetUserBooks($userId);
        switch($orderBy) {
            case "author": 
                $q->orderBy("b.author");
                break;
            case "author_desc": 
                $q->orderBy("b.author", "DESC");
                break;
        }
        return $q->getQuery();
    }

    public function getUserBooks($userId, $orderBy = null) {
        return $this->getUserBooksQuery($userId, $orderBy)
            ->getResult();
    }

    public function getUserBooksByAuthors($userId) {
        return $this->createGetUserBooks($userId)
                ->orderBy('b.author')
                ->getQuery()
                ->getResult();
    }

    public function countUserAuthors($userId) {
        return $this->createGetUserBooks($userId)
        ->select('count(distinct b.author)')
        ->getQuery()
        ->getSingleScalarResult();

    }
    public function getUserAuthorsQuery($userId) {
        return $this->createGetUserBooks($userId)
        ->select('b.author')
        ->distinct()
        ->groupBy('b.author')
        ->getQuery();
    }
    /* public function getUserAuthors($userId)
    {
        $results = $this->createGetUserBooks($userId)
            ->select('b.author')
            ->distinct()
            ->orderBy('b.author')
            ->getQuery()
            ->getResult();
            
        $authors = [];
        foreach ($results as $res) {
        foreach ($res as $r) 
            $authors[] = $r;
        }
        return $authors;
    } */

    public function getUserBooksOfAuthor($userId, $author)
    {
        return $this->createGetUserBooks($userId)
                ->andWhere("b.author like :author")
                ->setParameter("author", "%".$author."%")
                ->getQuery()
                ->getResult();
    }

    public function deleteByIdUser($idUser) {
        $books = $this->findByIdUser($idUser);

        foreach($books as $book) {
            $this->delete($book);
        }      
    }

    // /**
    //  * @return Book[] Returns an array of Book objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Book
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
