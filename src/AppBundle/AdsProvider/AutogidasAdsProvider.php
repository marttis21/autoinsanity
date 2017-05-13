<?php

namespace AppBundle\AdsProvider;

use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AutogidasAdsProvider extends AdsProvider
{
    public function __construct(EntityManager $em, string $imgDirectory)
    {
        $this->em = $em;
        $this->imgDirectory = $imgDirectory;
        $this->link = 'https://autogidas.lt/automobiliai/%psl%-psl/?f_60=732';
        $this->providerName = 'Autogidas.lt';
    }

    protected function parseAdsPage($html)
    {
        $cars = [];

        $crawler = new Crawler($html);
        $crawler = $crawler->filter('.item-link');

        foreach ($crawler as $domRow) {
            $row = new Crawler($domRow);

            $lastUpdate = $row->filter('.inserted-before');
            $lastUpdateDate = null;
            if ($lastUpdate->count() > 0) {
                $lastUpdateDate = $this->parseDate($lastUpdate->text());
            }
            $innerUrl = $row->filter('.item-link')->attr('href');
            $innerUrl = 'https://autogidas.lt' . $innerUrl;
            $car = null;
            try {
                $car = $this->parseAd($innerUrl);
            } catch (Exception $e) {
                echo $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
            }
            if ($car !== null) {
                $car['last_update'] = $lastUpdateDate;
                $accessor = PropertyAccess::createPropertyAccessor();
                $vehicle = $this->saveToModel($accessor, $car);
                $cars[] = $vehicle;
            }
            sleep(1);
        }
        return $cars;
    }

    private function parseAd(string $innerUrl)
    {
        $car = [];
        $innerHtml = $this->getHtml($innerUrl);
        $innerCrawler = new Crawler($innerHtml);

        //vehicle is not saved if it is sold
        if ($innerCrawler->filter('.container .sold')->count() > 0) {
            return null;
        }
        $brand = trim($innerCrawler->filter('.bread-crumb a')->eq(1)->text());
        $model = trim($innerCrawler->filter('.bread-crumb a')->eq(2)->text());

        $brandModelRegex = '~(?<=[A-Za-z0-9])-(?=[A-Za-z0-9])~';
        preg_replace($brandModelRegex, ' ', $brand);
        preg_replace($brandModelRegex, ' ', $model);

        $price = trim($innerCrawler->filter('.params-block .price')->text());
        $price = (int)str_replace(' ', '', $price);

        $dummy = explode('-', $innerUrl);
        $dummy = explode('.', array_pop($dummy));
        $providerId = intval($dummy[0]);

        $dummy = trim($innerCrawler->filter('.contacts-wrapper .seller-location')->text());
        $dummy = explode(",", $dummy);
        $city = trim($dummy[0]);
        $country = trim($dummy[1]);

        $imageElement = $innerCrawler->filter('.right-media .big-photo > img');
        $imageUrl = trim($imageElement->attr('src'));
        $imageUrl = str_replace('_21_', '_17_', $imageUrl);
        $car['image'] = $this->saveImages($imageUrl, $this->provider->getName(), $providerId);

        $items = $innerCrawler->filterXPath('//div[@class="params-block"]//div[@class="param"]');

        foreach ($items as $innerDomRow) {
            $row = new Crawler($innerDomRow);
            $key = ($row->filterXPath('//div[@class="left"]')->count()) ?
                trim($row->filterXPath('//div[@class="left"]')->text()) :
                'Not set';
            $value = ($row->filterXPath('//div[@class="right"]')->count()) ?
                trim($row->filterXPath('//div[@class="right"]')->text()) :
                '';
            $key = $this->getKeyName($key);
            if ($key !== null) {
                $func = $this->getFunctionFromKey($key);
                $value = $this->$func($value);
                $car[$key] = $value;
            }
        }
        $car = array_merge(
            $car, [
            'brand' => $brand,
            'model' => $model,
            'price' => $price,
            'city' => $city,
            'country' => $country,
            'url' => $innerUrl,
            'providerId' => $providerId,
            ]
        );
        return $car;
    }

    public function parseDate(string $dateString): \DateTime
    {
        $date = new \DateTime();
        $dateString = str_replace("Prieš ", "-", $dateString);
        $dateString = str_replace('val.', 'hours', $dateString);
        $dateString = str_replace('min.', 'minutes', $dateString);
        $dateString = str_replace('d.', 'days', $dateString);
        $dateString = str_replace('sav.', 'weeks', $dateString);
        $dateString = str_replace('mėn.', 'months', $dateString);
        $dateString = str_replace('m.', 'years', $dateString);
        $date->setTimestamp(strtotime($dateString));
        return $date;
    }

    protected function getKeyName(string $title)
    {
        $keyMap = [
            'Metai' => 'year',
            'Variklis' => 'engine',
            'Kuro tipas' => 'fuel_type',
            'Kėbulo tipas' => 'body_type',
            'Spalva' => 'color',
            'Pavarų dėžė' => 'drive_type',
            'Rida, km' => 'mileage',
            'Varomieji ratai' => 'transmission',
            'Defektai' => 'defects',
            'Vairo padėtis' => 'steering_wheel',
            'Durų skaičius' => 'doors_number',
            'Pavarų skaičius' => 'gears_number',
            'Sėdimų vietų skaičius' => 'seats_number',
            'TA iki' => 'next_check',
            'Svoris, kg' => 'weight',
            'Pirmosios registracijos šalis' => 'first_country',
            'Ratlankiai' => 'wheels_diameter',
        ];
        if (isset($keyMap[$title])) {
            return $keyMap[$title];
        } else {
            return null;
        }
    }

    protected function adParseYear($value)
    {
        $dummy = explode("/", $value);
        $dummy = intval(preg_replace("/[^0-9,.]/", "", $dummy[0]));
        $value = intval($dummy);
        return $value;
    }

    protected function adParseEngine($value)
    {
        $dummy = explode(" ", $value);
        $value = [];
        // parsing engine size
        if (isset($dummy[0])) {
            $dummy[0] = floatval(preg_replace("/[^0-9,.]/", "", $dummy[0]));
            $dummy[0] = intval($dummy[0] * 1000);
            $value['engine_size'] = $dummy[0];
        }
        // parsing engine power
        if (isset($dummy[1])) {
            $dummy[1] = intval(preg_replace("/[^0-9,.]/", "", $dummy[1]));
            $value['power'] = $dummy[1];
        }
        return $value;
    }

    protected function adParseFuelType($value)
    {
        if ($value == 'Benzinas/Dujos') {
            $value = 'Benzinas / dujos';
        } elseif ($value == 'Benzinas/Elektra') {
            $value = 'Benzinas / elektra';
        } elseif ($value == 'Dyzelinas/Elektra') {
            $value = 'Dyzelinas / elektra';
        } elseif ($value == 'Benzinas/Gamtinės dujos') {
            $value = 'Benzinas / gamtinės dujos';
        } elseif ($value == 'Etanolis') {
            $value = 'Bioetanolis (E85)';
        }
        return $value;
    }

    protected function adParseBodyType($value)
    {
        if ($value == 'Coupe') {
            $value = 'Kupė (Coupe)';
        } elseif ($value == 'Kabrioletas') {
            $value = 'Kabrioletas / Roadster';
        } elseif ($value == 'Komercinis auto(su būda)') {
            $value = 'Komercinis';
        }
        return $value;
    }

    protected function adParseColor($value)
    {
        if ($value == 'Raudona' || $value == 'Raudona/Vyšninė') {
            $value = 'Raudona / vyšninė';
        } elseif ($value == 'Žalia') {
            $value = 'Žalia / chaki';
        } elseif ($value == 'Ruda/Smėlio') {
            $value = 'Ruda';
        } elseif ($value == 'Pilka/Sidabrinė') {
            $value = 'Sidabrinė';
        } elseif ($value == 'Geltona/Aukso') {
            $value = 'Auksinė';
        } elseif ($value == 'Mėlyna/Žydra') {
            $value = 'Mėlyna';
        }
        return $value;
    }

    protected function adParseDriveType($value)
    {
        if ($value == 'Mechaninė') {
            $value = 0;
        } elseif ($value == 'Automatinė') {
            $value = 1;
        }
        return $value;
    }

    protected function adParseMileage($value)
    {
        $value = intval(preg_replace("/[^0-9,.]/", "", $value));
        return $value;
    }

    protected function adParseTransmission($value)
    {
        if ($value == 'Priekiniai varantys ratai') {
            $value = 'Priekiniai';
        } elseif ($value == 'Galiniai varantys ratai') {
            $value = 'Galiniai';
        } elseif ($value == 'Visi varantys ratai') {
            $value = 'Visi varantys (4х4)';
        }
        return $value;
    }

    protected function adParseDefects($value)
    {
        return $value;
    }

    protected function adParseSteeringWheel($value)
    {
        if ($value == 'Kairėje') {
            $value = 0;
        } elseif ($value == 'Dešinėje') {
            $value = 1;
        }
        return $value;
    }

    protected function adParseGearsNumber($value)
    {
        return $value;
    }

    protected function adParseDoorsNumber($value)
    {
        $firstNum = $secondNum = null;
        sscanf($value, "%d/%d", $firstNum, $secondNum);
        $value = $firstNum;
        return $value;
    }

    protected function adParseSeatsNumber($value)
    {
        $value = intval($value);
        return $value;
    }

    protected function adParseNextCheck($value)
    {
        $dummy = explode('-', $value);
        $value = intval($dummy[0]);
        return $value;
    }

    protected function adParseWeight($value)
    {
        $value = intval(preg_replace("/[^0-9,.]/", "", $value));
        return $value;
    }

    protected function adParseFirstCountry($value)
    {
        return $value;
    }

    protected function adParseWheelsDiameter($value)
    {
            $value = intval(preg_replace("/[^0-9,.]/", "", $value));
        return $value;
    }
}
