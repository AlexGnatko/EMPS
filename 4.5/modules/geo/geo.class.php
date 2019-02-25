<?php

define("EMPS_GEO_COUNTRY", 5);
define("EMPS_GEO_REGION", 10);
define("EMPS_GEO_COUNTY", 15);
define("EMPS_GEO_CITY", 20);
define("EMPS_GEO_DISTRICT", 30);
define("EMPS_GEO_METRO", 40);
define("EMPS_GEO_STREET", 50);
define("EMPS_GEO_ZIP", 60);

class EMPS_YandexGeocode
{
    public $default_kind = "house";

    public function short_name($name)
    {
        $x = explode(" ", $name);
        $nn = [];
        foreach ($x as $v) {
            $fc = mb_substr($v, 0, 1);
            if ($fc != mb_strtolower($fc) || (intval($fc) > 0)) {
                $nn[] = $v;
            }
        }
        $str = "";
        foreach ($nn as $v) {
            if ($str != "") {
                $str .= " ";
            }
            $str .= $v;
        }
        return $str;
    }

    public function ensure_area($type, $name, $lon, $lat, $parent)
    {
        global $emps;

        $name = trim($name);

        if (!$name) {
            return false;
        }

        $lon = floatval($lon);
        $lat = floatval($lat);
        $parent = intval($parent);
        $type = intval($type);

        if ($type == EMPS_GEO_CITY) {
            $area = $emps->db->get_row("geo_area", "name='" . $emps->db->sql_escape($name) .
                "' and type = {$type}");
        } else {
            $area = $emps->db->get_row("geo_area", "name='" . $emps->db->sql_escape($name) .
                "' and parent = {$parent} and type = {$type}");
        }

        if ($area) {
            return $area;
        }

        $nr = [];
        $nr['type'] = $type;
        $nr['parent'] = $parent;
        $nr['name'] = $name;
        $nr['short_name'] = $this->short_name($name);
        if (!$nr['short_name']) {
            $nr['short_name'] = $nr['name'];
        }
        $nr['lon'] = $lon;
        $nr['lat'] = $lat;
        $emps->db->sql_insert_row("geo_area", ['SET' => $nr]);
        $id = $emps->db->last_insert();
        $area = $emps->db->get_row("geo_area", "id = {$id}");
        if ($area) {
            return $area;
        }

        return false;
    }

    public function ensure_geo_location($type, $name, $lon, $lat)
    {
        global $emps;

        $name = trim($name);

        $name = $emps->db->sql_escape($name);

        $lon = floatval($lon);
        $lat = floatval($lat);

        $q = " name = '{$name}' and lon = {$lon} and lat = {$lat}";
        $location = $emps->db->get_row("geo_location", $q);
        if ($location) {
            return $location;
        }
        $nr = [];
        $nr['type'] = $type;
        $nr['name'] = $name;
        $nr['lon'] = $lon;
        $nr['lat'] = $lat;
        $emps->db->sql_insert_row("geo_location", ['SET' => $nr]);
        $id = $emps->db->last_insert();
        $location = $emps->db->get_row("geo_location", " id = {$id}");
        if ($location) {
            return $location;
        }
        return false;
    }

    public function ensure_location_in_area($location_id, $area_id)
    {
        global $emps;

        $location_id = intval($location_id);
        $area_id = intval($area_id);

        if (!$location_id) {
            return false;
        }

        if (!$area_id) {
            return false;
        }

        $al = $emps->db->get_row("geo_area_location", "location = {$location_id} and area = {$area_id}");
        if (!$al) {
            $area = $emps->db->get_row("geo_area", "id = {$area_id}");
            $nr = [];
            $nr['type'] = $area['type'];
            $nr['location'] = $location_id;
            $nr['area'] = $area_id;
            $emps->db->sql_insert_row("geo_area_location", ['SET' => $nr]);
            return true;
        }
        return false;
    }

    public function ensure_location($address)
    {
        $query = urlencode(trim($address)) . "&kind=" . $this->default_kind;

        $q = "http://geocode-maps.yandex.ru/1.x/?&geocode={$query}&format=json";
        echo $q;

        $answer = file_get_contents($q);
        $data = json_decode($answer, true);

        $found = false;
        foreach ($data['response']['GeoObjectCollection']['featureMember'] as $v) {
            $member = $v['GeoObject'];
            if ($member['metaDataProperty']['GeocoderMetaData']['kind'] == $this->default_kind) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return false;
        }

        if ($member) {
            $region = $member['metaDataProperty']['GeocoderMetaData']['AddressDetails']
            ['Country']['AdministrativeArea'];
            if ($region) {
                $region_name = $region['AdministrativeAreaName'];

                $region_geo = $this->ensure_area(EMPS_GEO_REGION, $region_name, 0, 0, 0);

                $city = $region['Locality'];
                if (!$city) {
                    $city = $region['SubAdministrativeArea']['Locality'];
                }
            } else {
                $city = $member['metaDataProperty']['GeocoderMetaData']['AddressDetails']['Country']['Locality'];
            }

            if ($region['SubAdministrativeArea']) {
                $county = $region['SubAdministrativeArea'];
                $county_name = $county['SubAdministrativeAreaName'];
                $county_geo = $this->ensure_area(EMPS_GEO_COUNTY, $county_name, 0, 0, $region_geo['id']);
            }

            $city_name = $city['LocalityName'];

            $city_name = $this->short_name($city_name);

            if ($region_geo) {
                $city_geo = $this->ensure_area(EMPS_GEO_CITY, $city_name, 0, 0, $region_geo['id']);
            } else {
                $city_geo = $this->ensure_area(EMPS_GEO_CITY, $city_name, 0, 0, 0);
            }

            if ($city_geo) {
                $address = $city['Thoroughfare'];
                if (!$address) {
                    $address = $city['DependentLocality']['Thoroughfare'];
                }
                $street_name = $address['ThoroughfareName'];

                if (!$street_name) {
                    $address = $city['DependentLocality'];
                    if (!$address['Premise']) {
                        $address = $address['DependentLocality'];
                    }
                    $street_name = $address['DependentLocalityName'];
                }

                $street_geo = $this->ensure_area(EMPS_GEO_STREET, $street_name, 0, 0, $city_geo['id']);
                if ($street_geo) {
                    $house_name = $address['Premise']['PremiseNumber'];

                    $point = $member['Point']['pos'];
                    $x = explode(" ", $point, 2);
                    $lon = $x[0];
                    $lat = $x[1];

                    $location = $this->ensure_geo_location(0, $house_name, $lon, $lat);
                    if ($location) {

                        $metro_query = $lon . ',' . $lat . '&kind=metro';
                        $metro_answer = file_get_contents("http://geocode-maps.yandex.ru/1.x/?&geocode=" .
                            $metro_query . "&format=json");

                        $metro_data = json_decode($metro_answer, true);
                        $metro_geo = false;
                        if ($metro_data) {
                            $metro_member = $metro_data['response']['GeoObjectCollection']
                            ['featureMember'][0]['GeoObject'];
                            if ($metro_member) {
                                $point = $metro_member['Point']['pos'];
                                $x = explode(" ", $point, 2);
                                $metro_lon = $x[0];
                                $metro_lat = $x[1];
                                $metro_name = $metro_member['name'];

                                $metro_geo = $this->ensure_area(EMPS_GEO_METRO, $metro_name,
                                    $metro_lon, $metro_lat, $city_geo['id']);
                            }
                        }

                        $district_query = $lon . ',' . $lat . '&kind=district';
                        $district_answer = file_get_contents("http://geocode-maps.yandex.ru/1.x/?&geocode=" .
                            $district_query . "&format=json");

                        $district_data = json_decode($district_answer, true);

                        $district_geo = false;
                        if ($district_data) {

                            foreach ($district_data['response']['GeoObjectCollection']['featureMember'] as $v) {
                                $district_member = $v['GeoObject'];
                                //							dump($district_member);
                                if ($district_member) {
                                    if ($district_member['metaDataProperty']['GeocoderMetaData']
                                        ['AddressDetails']['Country']['Locality']['DependentLocality']
                                        ['DependentLocalityName'] == $district_member['name']) {
                                        break;
                                    }
                                }
                            }

                            $point = $district_member['Point']['pos'];
                            $x = explode(" ", $point, 2);
                            $district_lon = $x[0];
                            $district_lat = $x[1];
                            $district_name = $district_member['name'];

                            $district_geo = $this->ensure_area(EMPS_GEO_DISTRICT, $district_name,
                                $district_lon, $district_lat, $city_geo['id']);

                        }

                        $this->ensure_location_in_area($location['id'], $street_geo['id']);
                        $this->ensure_location_in_area($location['id'], $district_geo['id']);
                        $this->ensure_location_in_area($location['id'], $metro_geo['id']);
                        return $location;
                    }
                } else {
//                    echo "NO STREET!";
                    var_dump($member);
                    $point = $member['Point']['pos'];
                    $x = explode(" ", $point, 2);
                    $lon = $x[0];
                    $lat = $x[1];

                    $location = $this->ensure_geo_location(0, "центр", $lon, $lat);
                    if ($location) {
                        $this->ensure_location_in_area($location['id'], $city_geo['id']);
                        $this->ensure_location_in_area($location['id'], $county_geo['id']);
                        $this->ensure_location_in_area($location['id'], $region_geo['id']);
                        return $location;
                    }
                }
            } else {
//                echo "NO CITY!";
//                var_dump($member);
            }
        }else{
//            echo "NO MEMBER!";

        }
    }

    public function list_location_areas($location_id)
    {
        global $emps;

        $r = $emps->db->query("select * from " . TP . "geo_area_location where location = {$location_id}");
        $lst = [];
        while ($ra = $emps->db->fetch_named($r)) {
            $area = $emps->db->get_row("geo_area", "id=" . $ra['area']);
            switch ($ra['type']) {
                case EMPS_GEO_STREET:
                    $lst['street'] = $area;
                    break;
                case EMPS_GEO_CITY:
                    $lst['city'] = $area;
                    break;
                case EMPS_GEO_COUNTY:
                    $lst['county'] = $area;
                    break;
                case EMPS_GEO_REGION:
                    $lst['region'] = $area;
                    break;
                case EMPS_GEO_COUNTRY:
                    $lst['country'] = $area;
                    break;
                case EMPS_GEO_DISTRICT:
                    $lst['district'] = $area;
                    break;
                case EMPS_GEO_METRO:
                    $lst['metro'] = $area;
                    break;
                case EMPS_GEO_ZIP:
                    $lst['zip'] = $area;
                    break;
            }
            if (!$lst['city']) {
                $parent = $emps->db->get_row("geo_area", "id = {$area['parent']}");
                if ($parent['type'] == EMPS_GEO_CITY) {
                    $lst['city'] = $parent;
                    if ($parent['parent']) {
                        $parent = $emps->db->get_row("geo_area", "id = {$parent['parent']}");
                        if ($parent) {
                            $lst['region'] = $parent;
                            if ($parent['parent']) {
                                $parent = $emps->db->get_row("geo_area", "id = {$parent['parent']}");
                                if ($parent) {
                                    $lst['country'] = $parent;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $lst;
    }

    public function load_location($location_id)
    {
        global $emps;

        $location = $emps->db->get_row("geo_location", "id = {$location_id}");
        if ($location) {
            $location['areas'] = $this->list_location_areas($location['id']);
            $tz_id = $location['areas']['city']['tz_id'];
            if (!$tz_id) {
                $tz_id = $location['areas']['region']['tz_id'];
            }
            $location['tz_id'] = $tz_id;

            $areas = $location['areas'];
            $a = [];
            if ($areas['country']['name']) {
                $a[] = $areas['country']['name'];
            }
            if ($areas['region']['name']) {
                $a[] = $areas['region']['name'];
            }
            if ($areas['county']['name']) {
                $a[] = $areas['county']['name'];
            }
            if ($areas['city']['name']) {
                $a[] = $areas['city']['name'];
            }
            if ($areas['street']['name']) {
                $a[] = $areas['street']['name'];
            }
            if ($location['name']) {
                $a[] = $location['name'];
            }
            $text = implode(", ", $a);
            $location['text'] = $text;
            $text = urlencode($text);
            $location['querytext'] = $text;
            return $location;
        }
        return false;
    }

    public function list_cities($parent)
    {
        global $emps;

        $parent = intval($parent);
        $where = "";

        if ($parent >= 0) {
            $where .= " and parent = $parent ";
        }

        $r = $emps->db->query("select * from " . TP . "geo_area where type = 20 {$where} order by name asc");
        $lst = [];
        while ($ra = $emps->db->fetch_named($r)) {
            $lst[] = $ra;
        }
        return $lst;
    }
}

class EMPS_GoogleGeocode extends EMPS_YandexGeocode
{
    public $region = 'us';

    public function ensure_location($input)
    {
        if (isset($input['address'])) {
            $full_address = $input['address'];
            $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" .
                urlencode($full_address) . "&region=" . $this->region . "&key=" . GOOGLE_MAPS_KEY;
        } else {
            $latlng = $input['latlng'];
            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" .
                urlencode($latlng) . "&region=" . $this->region . "&key=" . GOOGLE_MAPS_KEY;
        }
        $text = file_get_contents($url);
        $data = json_decode($text, true);
        if ($data['status'] == 'OK') {
            dump($data);
//		exit();
            $res = $data['results'][0];
            if (!$res) {
                return false;
            }
            foreach ($res['address_components'] as $ac) {
                if (in_array("street_number", $ac['types'])) {
                    $street_number = $ac['short_name'];
                }
                if (in_array("route", $ac['types'])) {
                    $street_name = $ac['long_name'];
                }
                if (in_array("locality", $ac['types'])) {
                    $city_name = $ac['long_name'];
                }
                if (in_array("administrative_area_level_1", $ac['types'])) {
                    $region_name = $ac['long_name'];
                }
                if (in_array("postal_code", $ac['types'])) {
                    $zip_code = $ac['long_name'];
                }
                if (in_array("neighborhood", $ac['types'])) {
                    $district_name = $ac['short_name'];
                }
                if (in_array("sublocality_level_1", $ac['types'])) {
                    $district_name = $ac['short_name'];
                }
                if (in_array("postal_code", $ac['types'])) {
                    $zip_code = $ac['long_name'];
                }
                if (in_array("country", $ac['types'])) {
                    $country_name = $ac['long_name'];
                }

            }

            if ($region_name == "New York") {
                if (!$city_name) {
                    $city_name = "New York";
                }
            }

            $lat = $res['geometry']['location']['lat'];
            $lon = $res['geometry']['location']['lng'];

            if ($country_name) {
                $country_geo = $this->ensure_area(EMPS_GEO_COUNTRY, $country_name, 0, 0, 0);
            }

            if ($region_name) {
                $region_geo = $this->ensure_area(EMPS_GEO_REGION, $region_name, 0, 0, $country_geo['id']);
            }

            if ($region_geo) {
                if (!$region_geo['tz_id']) {
                    $this->get_tz($region_geo, $lat, $lon);
                }
            }

            if ($city_name) {
                $city_name = $this->short_name($city_name);
                if ($region_geo) {
                    $city_geo = $this->ensure_area(EMPS_GEO_CITY, $city_name, 0, 0, $region_geo['id']);
                } else {
                    $city_geo = $this->ensure_area(EMPS_GEO_CITY, $city_name, 0, 0, 0);
                }
            }

            if ($city_geo) {
                if (!$city_geo['tz_id']) {
                    $this->get_tz($city_geo, $lat, $lon);
                }
            }

            if ($street_name) {
                if ($city_geo) {
                    $street_geo = $this->ensure_area(EMPS_GEO_STREET, $street_name,
                        0, 0, $city_geo['id']);
                } else {
                    $street_geo = $this->ensure_area(EMPS_GEO_STREET, $street_name,
                        0, 0, $region_geo['id']);
                }
            }

            $location = $this->ensure_geo_location(0, $street_number, $lon, $lat);

            if ($street_geo && $city_geo) {
                $this->ensure_location_in_area($location['id'], $street_geo['id']);
            } else {
                $this->ensure_location_in_area($location['id'], $street_geo['id']);
                if ($region_geo) {
                    $this->ensure_location_in_area($location['id'], $region_geo['id']);
                }
            }

            if ($country_geo) {
                $this->ensure_location_in_area($location['id'], $country_geo['id']);
            }
            return $location;
        } else {
            dump($data);
        }

    }

    public function ensure_timezone($name)
    {
        global $emps, $SET;

        $row = $emps->db->get_row("geo_timezone", "name = '" . $emps->db->sql_escape($name) . "'");
        if ($row) {
            return $row['id'];
        }

        $SET = array();
        $SET['name'] = $name;
        $emps->db->sql_insert("geo_timezone");
        return $emps->db->last_insert();
    }

    public function get_tz($geo, $lat, $lon)
    {
        global $emps;

        if (!$geo) {
            return false;
        }

        $latlng = $lat . "," . $lon;
        $url = "https://maps.googleapis.com/maps/api/timezone/json?location=" .
            urlencode($latlng) . "&timestamp=" . time() . "&key=" . GOOGLE_MAPS_KEY;

        $text = file_get_contents($url);
        $data = json_decode($text, true);
        dump($data);
        if ($data['status'] == 'OK') {
            $zone_name = $data['timeZoneId'];
            $zone_id = $this->ensure_timezone($zone_name);
            $emps->db->query("update " . TP . "geo_area set tz_id = " . $zone_id . " where id = " . $geo['id']);
        }
    }
}
