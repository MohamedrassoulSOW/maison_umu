<?php

namespace App\Repository;

use App\Entity\FooterSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FooterSettings>
 */
class FooterSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FooterSettings::class);
    }

    public function getCurrent(): FooterSettings
    {
        $settings = $this->findOneBy([]);
        if ($settings instanceof FooterSettings) {
            return $settings;
        }

        $settings = new FooterSettings();
        $this->getEntityManager()->persist($settings);
        $this->getEntityManager()->flush();

        return $settings;
    }
}
