<?php

namespace AppBundle\Repository;

use AppBundle\Entity\User;
use AppBundle\Entity\VehicleSearch;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * VehicleRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VehicleRepository extends EntityRepository
{
    const RESULTS_PER_PAGE = 20;

    public function findAllJoinedTables(): array
    {
        return $this->getJoinedTablesQuery()->orderBy('v.id')
            ->getQuery()->getResult();
    }

    public function findAllByCriteria(VehicleSearch $search, int $page): array
    {
        $query = $this->getJoinedTablesQuery();
        $criteriaMap = [
            [
                'field_name' => 'provider',
                'criterium_value' => $search->getProvider()->toArray(),
                'collection' => true,
            ],
            [
                'field_name' => 'brand',
                'criterium_value' =>
                    (null !== $search->getBrand())
                        ? $search->getBrand()->getId()
                        : null,
            ],
            [
                'field_name' => 'model',
                'criterium_value' => $search->getModel()->toArray(),
                'collection' => true,
            ],
            [
                'field_name' => 'price',
                'criterium_value' => $search->getPriceFrom(),
                'compare' => '>=',
            ],
            [
                'field_name' => 'price',
                'criterium_value' => $search->getPriceTo(),
                'compare' => '<=',
            ],
            [
                'field_name' => 'year',
                'criterium_value' => $search->getYearFrom(),
                'compare' => '>=',
            ],
            [
                'field_name' => 'year',
                'criterium_value' => $search->getYearTo(),
                'compare' => '<=',
            ],
            [
                'field_name' => 'country',
                'criterium_value' =>
                    (null !== $search->getCountry())
                        ? $search->getCountry()->getId()
                        : null,
            ],
            [
                'field_name' => 'city',
                'criterium_value' => $search->getCity()->toArray(),
                'collection' => true,
            ],
            [
                'field_name' => 'engineSize',
                'criterium_value' => $search->getEngineSizeFrom(),
                'compare' => '>=',
            ],
            [
                'field_name' => 'engineSize',
                'criterium_value' => $search->getEngineSizeTo(),
                'compare' => '<=',
            ],
            [
                'field_name' => 'power',
                'criterium_value' => $search->getPowerFrom(),
                'compare' => '>=',
            ],
            [
                'field_name' => 'power',
                'criterium_value' => $search->getPowerTo(),
                'compare' => '<=',
            ],
            [
                'field_name' => 'fuelType',
                'criterium_value' => $search->getFuelType()->toArray(),
                'collection' => true,
            ],
            [
                'field_name' => 'bodyType',
                'criterium_value' => $search->getBodyType()->toArray(),
                'collection' => true,
            ],
            [
                'field_name' => 'transmission',
                'criterium_value' => $search->getTransmission()->toArray(),
                'collection' => true,
            ],
            [
                'field_name' => 'doorsNumber',
                'criterium_value' => $search->getDoorsNumber(),
            ],
            [
                'field_name' => 'seatsNumber',
                'criterium_value' => $search->getSeatsNumber(),
            ],
            [
                'field_name' => 'driveType',
                'criterium_value' => $search->getDriveType(),
            ],
            [
                'field_name' => 'climateControl',
                'criterium_value' => $search->getClimateControl()->toArray(),
                'collection' => true,
            ],
            [
                'field_name' => 'color',
                'criterium_value' => $search->getColor()->toArray(),
                'collection' => true,
            ],
            [
                'field_name' => 'defects',
                'criterium_value' => $search->getDefects()->toArray(),
                'collection' => true,
            ],
            [
                'field_name' => 'wheelsDiameter',
                'criterium_value' => $search->getWheelsDiameter(),
            ],
            [
                'field_name' => 'steeringWheel',
                'criterium_value' => $search->getSteeringWheel(),
            ],
            [
                'field_name' => 'mileage',
                'criterium_value' => $search->getMileageFrom(),
                'compare' => '>=',
            ],
            [
                'field_name' => 'mileage',
                'criterium_value' => $search->getMileageTo(),
                'compare' => '<=',
            ],
            [
                'field_name' => 'nextCheckYear',
                'criterium_value' => $search->getNextCheckYear(),
                'compare' => '>=',
            ],
            [
                'field_name' => 'firstCountry',
                'criterium_value' => $search->getFirstCountry()->toArray(),
                'collection' => true,
            ],
            [
                'field_name' => 'gearsNumber',
                'criterium_value' => $search->getGearsNumber(),
            ],
            [
                'field_name' => 'lastAdUpdate',
                'criterium_value' => (new \DateTime())
                    ->setTimestamp(strtotime('-'.$search->getLastAdUpdate().' days'))
                    ->format('Y-m-d'),
                'compare' => '>='
            ],
        ];
        foreach ($criteriaMap as $criteriumMap) {
            $query = $this->addSearchCriterium($query, $criteriumMap);
        }

        // sorting of results
        $sortValue = 0; // default value
        $sortField = 'price';
        $sortDir = 'asc';
        if ($search->getSortType() !== null) {
            $sortValue = $search->getSortType();
        }
        // set sorting sql parameters
        if ($sortValue === 1) {
            $sortField = 'price';
            $sortDir = 'desc';
        } elseif ($sortValue === 2) {
            $sortField = 'lastAdUpdate';
            $sortDir = 'desc';
        } elseif ($sortValue === 3) {
            $sortField = 'lastAdUpdate';
            $sortDir = 'asc';
        }
        $query = $query->orderBy("v.$sortField", $sortDir);
        $totalPagesCount = $this->createQueryPagination($query, $page);
        return [
            'vehicles' => $query->getQuery()->getResult(),
            'total_pages_count' => $totalPagesCount
        ];
    }

    public function getPinnedVehicles(User $user, int $page): array
    {
        $query = $this->getJoinedTablesQuery();
        $query->innerJoin('v.users', 'u')
            ->where('u.id = :user_id')
            ->setParameter('user_id', $user->getId());
        $totalPagesCount = $this->createQueryPagination($query, $page);
        return [
            'vehicles' => $query->getQuery()->getResult(),
            'total_pages_count' => $totalPagesCount
        ];
    }

    public static function createQueryPagination(QueryBuilder $query, int $page): int
    {
        $allResults = $query->getQuery()->getResult();
        $totalPagesCount = intdiv(count($allResults), self::RESULTS_PER_PAGE);
        if (count($allResults) % self::RESULTS_PER_PAGE != 0) {
            $totalPagesCount++;
        }
        // filter results for pagination
        $query->setFirstResult(self::RESULTS_PER_PAGE * ($page - 1))
            ->setMaxResults(self::RESULTS_PER_PAGE);
        return $totalPagesCount;
    }

    /**
     * Generates database query that joins vehicle table with other related tables
     */
    private function getJoinedTablesQuery(): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('v, bra, mod, bod, cli, col, cou, cit, def, fue, pro, tra')
            ->from('AppBundle:Vehicle', 'v')
            ->leftJoin('v.brand', 'bra')
            ->leftJoin('v.model', 'mod')
            ->leftJoin('v.bodyType', 'bod')
            ->leftJoin('v.climateControl', 'cli')
            ->leftJoin('v.color', 'col')
            ->leftJoin('v.country', 'cou')
            ->leftJoin('v.city', 'cit')
            ->leftJoin('v.defects', 'def')
            ->leftJoin('v.fuelType', 'fue')
            ->leftJoin('v.provider', 'pro')
            ->leftJoin('v.transmission', 'tra');
    }

    private function addSearchCriterium(QueryBuilder $query, array $criterium): QueryBuilder
    {
        if (!isset($criterium['criterium_value']) || empty($criterium['criterium_value'])) {
            return $query;
        }
        if (!isset($criterium['compare'])) {
            $criterium['compare'] = '=';
        }
        if (isset($criterium['collection']) && $criterium['collection'] == true) {
            $idsArray = [];
            foreach ($criterium['criterium_value'] as $item) {
                $idsArray[] = $item->getId();
            }
            $criterium['criterium_value'] = $idsArray;
            $criterium['compare'] = 'IN';
        }
        $whereClause = 'v.' . $criterium['field_name']
            . ' ' . $criterium['compare']
            . ' (:' . $criterium['field_name'] . ')';
        $query = $query->andWhere($whereClause)
            ->setParameter($criterium['field_name'], $criterium['criterium_value']);
        return $query;
    }
}
