<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Programmer;
use Doctrine\ORM\EntityRepository;

class BattleRepository extends EntityRepository
{
    public function createQueryBuilderForProgrammer(Programmer $programmer)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.programmer = :programmer')
            ->setParameter('programmer', $programmer)
            ->getQuery();
    }
}
