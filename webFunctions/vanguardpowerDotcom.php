<?php
// Website 8. https://www.vanguardpower.com/na/en_us/product-catalog/engines/big-block-vtwin-vertical-shaft/40_0-gross-hp_-efietc.html

function vanguardpowerDotcom_updateBySitemap($monthsAgo = 3)
{
    $urls = array();
    $urlMaps = array('https://www.vanguardpower.com/sitemap.xml');
    for ($m = 0; $m < count($urlMaps); $m++) {
        $html = curl_get_v3($urlMaps[$m]);
        $xml = new SimpleXMLElement($html);
        foreach ($xml->sitemap as $item) {
            if (!isset($item->loc)) continue;
            $url = (string)$item->loc;
            array_push($urls, $url);
        }
    }

    $resultData = extract_bySitemap($urls, 'vanguardpowerDotcom_checkLink', $monthsAgo, 1, 'curl_get_v3');
    echo "Total link product: " . count($resultData) . PHP_EOL;
    foreach ($resultData as $link) {
        echo $link . PHP_EOL;
    }
    // return $resultData;
}

function vanguardpowerDotcom_checkLink($link, $urlInfo)
{
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!$urlInfo || !isset($urlInfo['domain'])) return false;
    if (!preg_match("/vanguardpower\.com/i", $urlInfo['domain'])) return false;

    $link = explode('?', $link)[0];
    $link = explode('#', $link)[0];

    $path = parse_url($link, PHP_URL_PATH);
    $segments = array_filter(explode('/', $path));
    if (count($segments) < 5 || !preg_match("/product-catalog/i", $link)) return false;

    return $link;
}

function vanguardpowerDotcom_extractManuals($url, $runAuto = 'Yes')
{
    /*
        - Create: 2025-08-29
    */

    $data = array('brand' => 'Vanguard ', 'manualLang' => 'en');
    $domainUrl = getWebsiteUrl($url);
    if (!$domainUrl) return $data;

    $html = curl_get_v3($url);
    // 1. numOfRelatedUrls
    $data['numOfRelatedUrls'] = count(extract_getRelatedProducts($html, 'vanguardpowerDotcom_checkLink', $domainUrl, 0));

    // 2. Name
    $data['name'] = get_string_between($html, '<h1', '</h1>');
    if ($data['name'] != '') $data['name'] = '<h1' . $data['name'];
    $data['name'] = extract_clearName($data['name']);
    if (!$data['name']) return $data;

    // 3. Model
    $modelHtml = get_string_between($html, '<strong>Model</strong></div>', '</div>');
    $data['model'] = extract_clearModel($modelHtml);
    if ($runAuto == 'Yes' && !$data['model']) return $data;

    $data = extract_checkNameAndBrand($data);

    // 4. Related Model - Model Alias: Nothing

    // 5. Image
    $thumbHtml = get_string_between($html, '<div class="thumb active">', '</div>');
    $img = get_string_between($thumbHtml, '<img src="', '"');

    if ($img != '') {
        if (substr($img, 0, 2) == '//') {
            $img = 'https:' . $img;
        } elseif (substr($img, 0, 1) == '/') {
            $img = $domainUrl . $img;
        }
        $data['images'] = [extract_clearUrl($img)];
    }

    // 6. Category
    $category = findCategoryFromName($data['name']);
    if ($category) $data['category'] = $category;
    if (!isset($data['category'])) $data['category'] = 'BIG BLOCK™ V-Twin Vertical Shaft';

    // 7. Manual & OtherFiles
    $checkFileExist = array();
    $manualHtml = get_string_between($html, '<strong>Certified Power Rating</strong></div>', '<script>');
    preg_match_all('/<a(.*?)<\/a>/s', $manualHtml, $m);
    if ($m != false) {
        for ($j = 0; $j < count($m[0]); $j++) {
            $item = $m[0][$j];
            $itemFile = array();

            $itemFile['file'] = get_string_between($item, 'href="', '"');
            if ($itemFile['file'] == '') $itemFile['file'] = get_string_between($item, "href='", "'");
            $itemFile['file'] = trim($itemFile['file']);
            if (substr($itemFile['file'], 0, 2) == '//') $itemFile['file'] = 'https:' . $itemFile['file'];
            if (substr($itemFile['file'], 0, 1) == '/') $itemFile['file'] = $domainUrl . $itemFile['file'];
            $itemFile['file'] = extract_clearUrl($itemFile['file']);
            //$itemFile['file'] = explode('?',$itemFile['file'])[0];
            if (isset($checkFileExist[md5($itemFile['file'])])) continue;
            if (!preg_match("/\.pdf/i", $itemFile['file'])) continue;

            $itemFile['name'] = extract_clearItemFileName($item);
            if (!$itemFile['name'] || preg_match("/Brochure|energy|warrant|Catalog|Sales/i", $itemFile['name'])) continue;

            $itemFile['fileType'] = findTypeOfFileFromString($itemFile['name']);
            if (!$itemFile['fileType']) $itemFile['fileType'] = 'Other';

            $itemFile['langCode'] = 'en';
            $langCode = findLangcodeFromString($itemFile['name']);
            if ($langCode) $itemFile['langCode'] = $langCode;

            $checkFileExist[md5($itemFile['file'])] = 1;
            if (preg_match("/Owner|Operat|manual|Care Guide/is", $itemFile['name']) && !isset($data['manualsUrl']) && !preg_match("/energy|dimension|warranty|specification/is", $itemFile['name'])) {
                $data['manualsUrl'] = $itemFile['file'];
                $data['manualLang'] = $itemFile['langCode'];
                $data['fileType'] = $itemFile['fileType'];
                $data['manualsTitle'] = $itemFile['name'];
                if ($data['fileType'] == 'Other') $data['fileType'] = 'User Manual';
            } else {
                if (!isset($data['otherFiles'])) $data['otherFiles'] = array();
                array_push($data['otherFiles'], array('name' => $itemFile['name'], 'url' => $itemFile['file'], 'lang' => $itemFile['langCode'], 'type' => 'origin', 'fileType' => $itemFile['fileType']));
            }
        }
    }

    $data = extract_recheckManualsUrl($data);

    // 8. Specifications
    $data = extract_recheckManualsUrl($data);
    $spec = [];
    preg_match_all('/<div\s+class="divTableCell-sync[^">]*"[^>]*>(.*?)<\/div>/si', $html, $cells);
    $cellContents = $cells[1] ?? [];
    for ($i = 0; $i + 1 < count($cellContents); $i += 2) {
        $left = $cellContents[$i];
        $right = $cellContents[$i + 1];
        if (stripos($left, '<a') !== false || stripos($right, '<a') !== false) continue;

        if (!preg_match('/<strong[^>]*>(.*?)<\/strong>/si', $left, $mName)) continue;
        $name = $mName[1];
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = $name;
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        $name = extract_clearItemFileName($name);
        if ($name === '') continue;

        $valueHtml = str_ireplace(["<br>", "<br/>", "<br />"], ' ', $right);
        $value = html_entity_decode($valueHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = trim(preg_replace('/\s+/u', ' ', $value));
        $value = extract_clearItemFileName($value);
        if ($value === '' || strcasecmp($value, 'No') === 0 || strcasecmp($value, 'N/A') === 0) continue;

        $spec[] = [
            'name' => $name,
            'value' => $value,
        ];
    }
    if (count($spec) > 0) {
        $data['specifications'] = $spec;
    }

    return $data;
}
