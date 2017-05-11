<?php
namespace AppBundle\Command;

use AppBundle\Model\Vehicle;
use AppBundle\Entity\Vehicle as VehicleEntity;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCrawlerCommand extends Command
{
    private $adsProviders;
    private $em;
    private $imgDirectory;

    public function __construct(array $adsProviders, EntityManager $em, string $imgDirectory)
    {
        parent::__construct();

        $this->adsProviders = $adsProviders;
        $this->em = $em;
        $this->imgDirectory = $imgDirectory;
    }

    protected function configure()
    {
        $this->setName('crawler:start')
            ->setDescription('Start a crawler');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_dir($this->imgDirectory)) {
            mkdir($this->imgDirectory);
        }

        foreach ($this->adsProviders as $adsProvider) {
            $crawlerManager = new $adsProvider($this->em, $this->imgDirectory);

            echo "Starting " . $crawlerManager->getName() . "\n";

            $provider = $this->em->getRepository("AppBundle:Provider")->findOneBy(
                ['name' => $crawlerManager->getName()]
            );
            $crawlerManager->setProvider($provider);
            $pageNumber = 1;
            while ($pageNumber == 1 || !empty($ads)) {
                $ads = $crawlerManager->getNewAds($pageNumber);
                foreach ($ads as $ad) {
                    $this->save($ad);
                }
                $this->em->flush();
                echo "(Page " . $pageNumber . ") Saved to database " . count($ads) . " entries.\n";

                $pageNumber++;
                //sleep(1);
            }

            echo "Finishing " . $adsProvider->getName() . "\n";
        }
    }

    private function save(Vehicle $ad)
    {
        $em = $this->em;
        $relations = [];
        $relationsMap = [
            'brand' => [
                'AppBundle:Brand',
                ['name' => $ad->getBrand()],
            ],
            'country' => [
                'AppBundle:Country',
                ['name' => $ad->getCountry(),],
            ],
            'body_type' => [
                'AppBundle:BodyType',
                ['name' => $ad->getBodyType(),],
            ],
            'fuel_type' => [
                'AppBundle:FuelType',
                ['name' => $ad->getFuelType(),],
            ],
            'color' => [
                'AppBundle:Color',
                ['name' => $ad->getColor(),],
            ],
            'defects' => [
                'AppBundle:Defects',
                ['name' => $ad->getDefects(),],
            ],
            'transmission' => [
                'AppBundle:Transmission',
                ['name' => $ad->getTransmission(),],
            ],
            'climate_control' => [
                'AppBundle:ClimateControl',
                ['name' => $ad->getClimateControl(),],
            ],
            'first_country' => [
                'AppBundle:Country',
                ['name' => $ad->getFirstCountry(),],
            ],
        ];
        $firstRelations = $this->resolveFields($relationsMap);
        if ($firstRelations == null) {
            return 0;
        }
        $relations = array_merge($relations, $firstRelations);
        $dependedRelationsMap = [
            'model' => [
                'AppBundle:Model',
                ['name' => $ad->getModel(), 'brand' => $relations['brand'],],
            ],
            'city' => [
                'AppBundle:City',
                ['name' => $ad->getCity(), 'country' => $relations['country'],],
            ],
        ];
        $secondRelations = $this->resolveFields($dependedRelationsMap);
        if ($secondRelations == null) {
            return 0;
        }
        $relations = array_merge($relations, $secondRelations);

        // update vehicle if it already exists
        $repository = $em->getRepository("AppBundle:Vehicle");
        $vehicle = $repository->findOneBy(
            [
            'provider' => $ad->getProvider(),
            'providerId' => $ad->getProviderId(),
            ]
        );
        // if not found, create new
        if ($vehicle == null) {
            $vehicle = new VehicleEntity();
        }

        $vehicle->setBrand($relations['brand']);
        $vehicle->setModel($relations['model']);
        $vehicle->setCountry($relations['country']);
        $vehicle->setCity($relations['city']);
        $vehicle->setBodyType($relations['body_type']);
        $vehicle->setFuelType($relations['fuel_type']);
        $vehicle->setColor($relations['color']);
        $vehicle->setProviderId($ad->getProviderId());
        $vehicle->setProvider($ad->getProvider());
        $vehicle->setLink($ad->getLink());
        $vehicle->setPrice($ad->getPrice());
        $vehicle->setYear($ad->getYear());
        $vehicle->setEngineSize($ad->getEngineSize());
        $vehicle->setPower($ad->getPower());
        $vehicle->setDoorsNumber($ad->getDoorsNumber());
        $vehicle->setSeatsNumber($ad->getSeatsNumber());
        $vehicle->setDriveType($ad->getDriveType());
        $vehicle->setTransmission($relations['transmission']);
        $vehicle->setClimateControl($relations['climate_control']);
        $vehicle->setDefects($relations['defects']);
        $vehicle->setSteeringWheel($ad->getSteeringWheel());
        $vehicle->setWheelsDiameter($ad->getWheelsDiameter());
        $vehicle->setWeight($ad->getWeight());
        $vehicle->setMileage($ad->getMileage());
        $vehicle->setImage($ad->getImage());
        $vehicle->setNextCheckYear($ad->getNextCheckYear());
        $vehicle->setFirstCountry($relations['first_country']);
        $vehicle->setGearsNumber($ad->getGearsNumber());
        $vehicle->setLastAdUpdate($ad->getLastAdUpdate());
        $vehicle->setLastCheck(new \DateTime());
        $em->persist($vehicle);
        return 1;
    }

    private function resolveFields($fieldsMap)
    {
        $relations = [];
        foreach ($fieldsMap as $key => $relationMap) {
            $relations[$key] = $this->findOneInRepository($relationMap[0], $relationMap[1]);
            if ($relations[$key] == null && !empty($relationMap[1]['name'])) {
                echo "Skipped: Failed to find element in " . $relationMap[0]
                    . " with parameters: " . print_r($relationMap[1]) . "\n";
                return null;
            }
        }
        return $relations;
    }

    private function findOneInRepository(string $repository, array $params)
    {
        $repository = $this->em->getRepository($repository);
        $item = $repository->findOneBy($params);
        return $item;
    }
}