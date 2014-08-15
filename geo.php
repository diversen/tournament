<?php
$vendor = dirname(__FILE__) . "/vendor";

set_include_path(get_include_path() . PATH_SEPARATOR . $vendor);
require $vendor . "/autoload.php";


$gi = geoip_open("/usr/local/share/GeoIP.dat", /* GEOIP_SHARED_MEMORY*/ GEOIP_STANDARD);

echo $ip = '212.10.69.17'; // $_SERVER['REMOTE_ADDR'];
echo geoip_country_code_by_addr($gi, $ip) . "\t" .
     geoip_country_name_by_addr($gi, $ip) . "\n";
echo geoip_country_code_by_addr($gi, $ip) . "\t" .
     geoip_country_name_by_addr($gi, $ip) . "\n";

geoip_close($gi);