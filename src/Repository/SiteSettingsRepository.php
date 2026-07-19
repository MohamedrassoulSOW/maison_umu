<?php

namespace App\Repository;

use App\Entity\SiteSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteSettings>
 */
class SiteSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteSettings::class);
    }

    public function getCurrent(): SiteSettings
    {
        $settings = $this->findOneBy([], ['id' => 'ASC']);
        if ($settings) {
            return $settings;
        }

        $settings = new SiteSettings();
        $em = $this->getEntityManager();
        $em->persist($settings);
        $em->flush();

        return $settings;
    }
}
