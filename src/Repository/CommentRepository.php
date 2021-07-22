<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function save($comment) {
        $comment->setDate(new \DateTime());
        $this->_em->persist($comment);
        $this->_em->flush();
    }

    public function add($content, $stars, $user, $book) {
        $comment = new Comment();
        $comment->setContent($content);
        $comment->setStars($stars);
        $comment->setWriter($user);
        $comment->setBook($book);
        $this->save($comment);

        return $comment;
    }

    public function delete($comment) {
        $this->_em->remove($comment);
        $this->_em->flush();
    }

    public function findByBook($book) {
        return $this->createQueryBuilder('c')
            ->join('c.userBook','ub')
            ->addSelect('ub')
            ->where('ub.book = :book')
            ->setParameter('book', $book)
            ->orderBy('c.date')
            ->getQuery()
            ->getResult();
    }

    public function findByUserQuery($user) {
        return $this->createQueryBuilder('c')
            ->join('c.userBook','ub')
            ->addSelect('ub')
            ->where('ub.user = :user')
            ->setParameter('user', $user)
            ->getQuery();
    }

    // /**
    //  * @return Comment[] Returns an array of Comment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Comment
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
