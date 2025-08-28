<?php
$SCRIPT_FOLDER = __DIR__ . '/';
include $SCRIPT_FOLDER . 'config/functions.php';
include $SCRIPT_FOLDER . 'config/functions_extract.php';
include $SCRIPT_FOLDER . 'config/functions_fake.php';
$CRAWLER_FUNCTIONS_FOLDER = $SCRIPT_FOLDER . 'webFunctions/';
echo "\n";

/*
PHp yeu cầu:
- version 7.4+ 
- Cài SimpleXMLElement
- Cài curl.
*/

$url = 'https://buythermopro.com/products/tp49w-3-packs-digital-indoor-thermometer-hygrometer';

$type = 'extract';
// $type = 'updateBySitemap';
// $type = 'updateManualPages';
// $type = 'updateByWebsite';


$testCurl = 'No';
if ($testCurl == 'Yes') {
    $headerDf = array(
        'Accept: application/json, text/plain, */*',
        'Accept-Encoding: gzip, deflate, br, zstd',
        'Accept-Language: en-US,en;q=0.9,vi;q=0.8,la;q=0.7',
        'Connection: keep-alive',
        'Sec-Ch-Ua: "Chromium";v="128", "Not;A=Brand";v="24", "Brave";v="128"',
        'Sec-Ch-Ua-Mobile: ?0',
        'Sec-Ch-Ua-Platform: "Windows"',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'Sec-Gpc: 1',
        'x-requested-with: Hiwie-Fetch',
        'Upgrade-Insecure-Requests: 1'
    );
    $html = curl_get_v3($url, $headerDf);
    echo $html;
    exit();
}


$crawler = crawler_findFuntions_byUrl($url);
if ($crawler === false) exit("No functionName.");
if (isset($crawler['webFunctions'])) include_once $crawler['webFunctions'];

$functionName = $crawler['functionName'];
if ($type == 'extract' && (!isset($functionName['extract']) || !function_exists($functionName['extract']))) {
    exit("The function extract is not exist!\n");
}

if ($type == 'extract') {
    //Extract data product:
    $data = call_user_func($functionName['extract'], $url, 'No');
    print_r($data);
    echo "\n";
}

//Update by Sitemap:
if (isset($crawler['webFunctions_prefix']) && $type == 'updateBySitemap') {
    $updateBySitemap_function = $crawler['webFunctions_prefix'] . "_updateBySitemap";
    if (isset($updateBySitemap_function) && function_exists($updateBySitemap_function)) {
        echo "Update product urls by Sitemap\n";
        print_r(json_encode(call_user_func($updateBySitemap_function, 30000)));
    }
}

//Update by updateManualPages:
if (isset($crawler['webFunctions_prefix']) && $type == 'updateManualPages') {
    $updateManualPages_function = $crawler['webFunctions_prefix'] . "_updateManualPages";
    if (isset($updateManualPages_function) && function_exists($updateManualPages_function)) {
        echo "Update product Page Manuals\n";
        print_r(call_user_func($updateManualPages_function, 300));
    }
}

//Update by updateByWebsite:
if (isset($crawler['webFunctions_prefix']) && $type == 'updateByWebsite') {
    $updateByWebsite_function = $crawler['webFunctions_prefix'] . "_updateByWebsite";
    if (isset($updateByWebsite_function) && function_exists($updateByWebsite_function)) {
        echo "Update product by Webiste\n";
        print_r(call_user_func($updateByWebsite_function));
    }
}


function crawler_findFuntions_byUrl($url, $urlInfo = array())
{
    global $CRAWLER_FUNCTIONS_FOLDER;
    if (!$urlInfo || gettype($urlInfo) != 'array' || count($urlInfo) == 0) $urlInfo = parseURL($url);
    if (!$urlInfo || !isset($urlInfo['domain'])) return false;
    $urlInfo['domain'] = str_replace('www.', '', $urlInfo['domain']);
    $urlInfo['webFunctions_prefix'] = str_replace(array('.', '-'), array('Dot', '_'), $urlInfo['domain']);
    $result = array();

    $functionName = manuals_DomainFuction($urlInfo['domain']);
    if ($functionName !== false) $result['functionName'] = $functionName; //is extract and checkLink.

    if (file_exists($CRAWLER_FUNCTIONS_FOLDER . $urlInfo['webFunctions_prefix'] . '.php')) {
        $result['webFunctions'] = $CRAWLER_FUNCTIONS_FOLDER . $urlInfo['webFunctions_prefix'] . '.php';
        $result['webFunctions_prefix'] = $urlInfo['webFunctions_prefix'];
        if (preg_match("/^[0-9]/", $result['webFunctions_prefix'])) {
            $result['webFunctions_prefix'] = 'number_' . $result['webFunctions_prefix'];
        }
        if (!isset($result['functionName'])) $result['functionName'] = array();
        if (!isset($result['functionName']['extract'])) {
            $result['functionName']['extract'] = $result['webFunctions_prefix'] . "_extractManuals";
        }
        if (!isset($result['functionName']['checkLink'])) {
            $result['functionName']['checkLink'] = $result['webFunctions_prefix'] . "_checkLink";
        }
        //Function updateBySitemap or updateByWebsite sẽ được check khi sử dụng ở ngoài.
    }

    if (!isset($result['functionName']) || count($result['functionName']) == 0) return false;
    $result['domain'] = $urlInfo['domain'];
    return $result;
}
