<?php

namespace App\Repository;

use App\Entity\HeroSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HeroSettings>
 */
class HeroSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeroSettings::class);
    }

    public function getCurrent(): HeroSettings
    {
        $hero = $this->findOneBy([], ['id' => 'ASC']);
        if ($hero) {
            return $hero;
        }

        $hero = new HeroSettings();
        $em = $this->getEntityManager();
        $em->persist($hero);
        $em->flush();

        return $hero;
    }
}
