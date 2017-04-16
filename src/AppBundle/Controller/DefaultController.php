<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Vehicle;
use AppBundle\Type\VehicleType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('AppBundle:default:index.html.twig');
    }

    /**
     * @Route("/search", name="detailed_search")
     */
    public function searchAction(Request $request)
    {
        $searchForm = $this->createForm(VehicleType::class, null, [
            'action' => $this->generateUrl('results_page')
        ]);
        $searchForm->handleRequest($request);
        return $this->render('AppBundle:default:detailed_search.html.twig',
            ['searchForm' => $searchForm->createView()]);
    }

    /**
     * @Route("/results/{page}", name="results_page", requirements={"page": "^[1-9]\d*$"})
     */
    public function resultsAction(Request $request, $page = 1)
    {
        $entityManager = $this->get('doctrine.orm.default_entity_manager');
        $repository = $entityManager->getRepository('AppBundle:Vehicle');
        $queryVehicleParams = (isset($request->query->all()['vehicle'])) ? $request->query->all()['vehicle'] : array();
        $results = $repository->findAllByCriteria($queryVehicleParams, $page);
        return $this->render('AppBundle:default:results_page.html.twig', [
            'items' => $results['vehicles'],
            'total_pages_count' => $results['total_pages_count'],
        ]);
    }

    /**
     * @Route("/generate", name="generate_fakes")
     */
    public function generateFakesAction()
    {
        $entityManager = $this->get('doctrine.orm.default_entity_manager');
        $brands = $entityManager->getRepository('AppBundle:Brand')->findAll();
        $models = $entityManager->getRepository('AppBundle:Model')->findAll();
        $bodyTypes = $entityManager->getRepository('AppBundle:BodyType')->findAll();
        $fuelTypes = $entityManager->getRepository('AppBundle:FuelType')->findAll();
        $countries = $entityManager->getRepository('AppBundle:Country')->findAll();
        $cities = $entityManager->getRepository('AppBundle:City')->findAll();
        $colors = $entityManager->getRepository('AppBundle:Color')->findAll();
        for($i = 0; $i < 100; $i++)
        {
            $vehicle = new Vehicle();
            $vehicle->setBrand($brands[$i%sizeof($brands)]);
            $vehicle->setBodyType($bodyTypes[$i%sizeof($bodyTypes)]);
            $vehicle->setModel($models[$i%sizeof($models)]);
            $vehicle->setFuelType($fuelTypes[$i%sizeof($fuelTypes)]);
            $vehicle->setCountry($countries[$i%sizeof($countries)]);
            $vehicle->setCity($cities[$i%sizeof($cities)]);
            $vehicle->setColor($colors[$i%sizeof($colors)]);
            $vehicle->setClimateControl("Lala");
            $vehicle->setDefects("Lala");
            $vehicle->setDoorsNumber($i % 4);
            $vehicle->setSeatsNumber($i % 4);
            $vehicle->setDriveType("Gaga");
            $vehicle->setEngineSize($i*1000 % 2000);
            $vehicle->setMileage($i*100000 % 100000);
            $vehicle->setProviderId($i * 100000 % 200000);
            $vehicle->setProvider("Autoplius");
            $vehicle->setLink("https://www.Autoplius.lt");
            $vehicle->setPrice($i * 4211 % 10000);
            $vehicle->setYear($i * 515151 % 2010);
            $vehicle->setPower($i * 545 % 100);
            $vehicle->setTransmission("RW");
            $vehicle->setSteeringWheel($i % 2);
            $vehicle->setWheelsDiameter($i % 20);
            $vehicle->setWeight($i * 5454 % 2000);
            $entityManager->persist($vehicle);

        }
        $entityManager->flush();
        return $this->render('AppBundle:default:index.html.twig');
    }
}
