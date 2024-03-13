<?php

namespace App\Repository;

use App\Entity\Timing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Timing>
 *
 * @method Timing|null find($id, $lockMode = null, $lockVersion = null)
 * @method Timing|null findOneBy(array $criteria, array $orderBy = null)
 * @method Timing[]    findAll()
 * @method Timing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Timing::class);
    }

    public function add(Timing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Timing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
