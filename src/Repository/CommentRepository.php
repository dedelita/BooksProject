<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    private EntityManager $em;

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

    
    public function getBooksOfUserOrderByGenre($userId) {
        return $this->createQueryBuilder('c')
                ->select('b')
                ->from('App:Book', 'b')
                ->andWhere('c.book = b')
                ->andWhere('c.writer = :id')
                ->setParameter('id', $userId)
                ->orderBy('b.genre', 'ASC')
                ->getQuery()
                ->getResult();
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
