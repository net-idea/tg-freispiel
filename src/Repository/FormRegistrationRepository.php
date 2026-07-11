<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FormRegistrationEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormRegistrationEntity>
 */
class FormRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormRegistrationEntity::class);
    }
}
