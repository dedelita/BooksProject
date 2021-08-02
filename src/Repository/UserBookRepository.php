<?php

namespace App\Repository;

use App\Entity\UserBook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserBook|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserBook|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserBook[]    findAll()
 * @method UserBook[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserBookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBook::class);
    }

    public function save($ub) {
        $ub->setCreatedDate(new \DateTime());
        $this->_em->persist($ub);
        $this->_em->flush();
    }

    public function add($user, $book) {
        $ub = new UserBook();
        $ub->setUser($user);
        $ub->setBook($book);
        $this->save($ub);

        return $ub;
    }

    public function delete($ub) {
        $this->_em->remove($ub);
        $this->_em->flush();
    }

    private function createUserBookQuery($user) {
        return $this->createQueryBuilder('ub')
            ->andWhere('ub.user = :user')
            ->setParameter('user', $user)
           // ->innerJoin('App:Book', 'b', 'WITH', 'ub.book = b')
            ->leftJoin('ub.book', 'b')
            ->addSelect('b');
    }
    public function getUserBooksQuery($user) {
        return $this->createUserBookQuery($user)->getQuery();
    }

    public function getLastUserBooks($user) {
        return $this->createUserBookQuery($user)
            ->addOrderBy('ub.createdDate', 'DESC')
            ->getQuery()
            ->setMaxResults(5)
            ->getResult();
    }

    public function getUserBooksByAuthors($user) {
        return $this->createUserBookQuery($user)
            ->orderBy('b.author')
            ->getQuery()
            ->getResult();
    }

    public function countUserAuthors($user) {
        return $this->createUserBookQuery($user)
            ->select('count(distinct b.author)')
            ->getQuery()
            ->getSingleScalarResult();

    }
    public function getUserAuthorsQuery($user) {
        return $this->createUserBookQuery($user)
            ->select('b.author')
            ->distinct()
            ->groupBy('b.author')
            ->getQuery();
    }

    public function getUserBooksOfAuthor($user, $author) {
        return $this->createUserBookQuery($user)
            ->andWhere("b.author like :author")
            ->setParameter("author", "%".$author."%")
            ->getQuery()
            ->getResult();
    }
    // /**
    //  * @return UserBook[] Returns an array of UserBook objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserBook
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
