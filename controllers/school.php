<?php

class School
{
    public function find($coords)
    {
        header('Content-Type: application/json');
        list($lat, $long) = array_map('floatval', explode(',', $coords));
        $f = fopen('db/edubase.csv', 'rb');
        $headers = fgetcsv($f);

        $idCol = array_search('URN', $headers);
        $wardCol = array_search('AdministrativeWard (name)', $headers);
        $constituencyCol = array_search('ParliamentaryConstituency (name)', $headers);
        $localityCol = array_search('Locality', $headers);
        $countyCol = array_search('County (name)', $headers);
        $postcodeCol = array_search('Postcode', $headers);

        $locData = json_decode(file_get_contents("http://uk-postcodes.com/latlng/{$lat},{$long}.json"));

        $likely = array();

        $lAd = $locData->administrative;
        //$locCouncil = $lAd->council->title;
        $locCounty = $lAd->county->title;
        $locWard = $lAd->ward->title;
        $locConstituency = $lAd->constituency->title;
        $locParish = $lAd->parish->title;
        $locPostcode = $locData->postcode;

        $schools = array();
        while (($line = fgetcsv($f)) !== false) {
            $id = $line[$idCol];
            if (!isset($likely[$id])) {
                $likely[$id] = 0;
            }
            if ($line[$countyCol] == $locCounty) {
                $likely[$id] += 1;
                $schools[$id] = $line;
            }
            if ($line[$wardCol] == $locWard) {
                $likely[$id] += 1;
                $schools[$id] = $line;
            }
            if ($line[$constituencyCol] == $locConstituency) {
                $likely[$id] += 1;
                $schools[$id] = $line;
            }
            if ($line[$localityCol] == $locParish) {
                $likely[$id] += 1;
                $schools[$id] = $line;
            }
            if ($line[$postcodeCol] == $locPostcode) {
                $likely[$id] += 1;
                $schools[$id] = $line;
            }
            if ($likely[$id] == 0) {
                unset($likely[$id]);
            }
        }
        fclose($f);
        $most = 0;
        $mostId = null;
        foreach ($likely as $id => $num) {
            if ($num > $most) {
                $most = $num;
                $mostId = $id;
            }
        }
        if ($mostId !== null) {
            $school = array();
            foreach ($headers as $i => $h) {
                $school[$h] = $schools[$mostId][$i];
            }
            $schools = null;
            echo json_encode(array(
                'name' => $school['EstablishmentName']
            ));
        } else {
            echo 'false';
        }
    }
}
