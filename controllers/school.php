<?php

class School
{
    public function find($coords)
    {
        header('Content-Type: application/json');
        list($lat, $long) = array_map('floatval', explode(',', $coords));
        $f = fopen('db/edubase.csv', 'rb');
        $headers = fgetcsv($f);

        $wardCol = array_search('AdministrativeWard (name)', $headers);
        $constituencyCol = array_search('ParliamentaryConstituency (name)', $headers);
        $localityCol = array_search('Locality', $headers);
        $countyCol = array_search('County (name)', $headers);
        $postcodeCol = array_search('Postcode', $headers);

        $jsonData = file_get_contents("http://uk-postcodes.com/latlng/{$lat},{$long}.json");
        if ($jsonData === false) {
            echo 'false';
            return;
        }
        $locData = json_decode($jsonData);
        if (!$locData) {
            echo 'false';
            return;
        }
        $lAd = $locData->administrative;
        $locCounty = isset($lAd->county) ? $lAd->county->title : null;
        $locWard = isset($lAd->ward) ? $lAd->ward->title : null;
        $locConstituency = isset($lAd->constituency) ? $lAd->constituency->title : null;
        $locParish = isset($lAd->parish) ? $lAd->parish->title : null;
        $locPostcode = isset($lAd->postcode) ? $locData->postcode : null;

        $most = null;
        $mostCount = 0;

        while (($line = fgetcsv($f)) !== false) {
            $count = 0;
            if ($locCounty != null && $line[$countyCol] == $locCounty) {
                $count++;
            }
            if ($locWard != null && $line[$wardCol] == $locWard) {
                $count++;
            }
            if ($locConstituency != null && $line[$constituencyCol] == $locConstituency) {
                $count++;
            }
            if ($locParish != null && $line[$localityCol] == $locParish) {
                $count++;
            }
            if ($locPostcode != null && $line[$postcodeCol] == $locPostcode) {
                $count++;
            }

            if ($count > $mostCount) {
                $mostCount = $count;
                $most = $line;
            }
        }
        fclose($f);
        if ($most !== null) {
            $school = array();
            foreach ($headers as $i => $h) {
                $school[$h] = $most[$i];
            }
            echo json_encode(array(
                'name' => $school['EstablishmentName']
            ));
        } else {
            echo 'false';
        }
    }
}
