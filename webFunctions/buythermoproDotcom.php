<?php
// Website 3. https://buythermopro.com/products/tp49w-3-packs-digital-indoor-thermometer-hygrometer

function buythermoproDotcom_updateBySitemap($monthsAgo = 3)
{
    $urls = array();
    $urlMaps = array('https://buythermopro.com/sitemap.xml');
    for ($m = 0; $m < count($urlMaps); $m++) {
        $html = curl_get_v4($urlMaps[$m]);
        $xml = new SimpleXMLElement($html);
        foreach ($xml->sitemap as $item) {
            if (!isset($item->loc)) continue;
            $url = (string)$item->loc;
            if (!preg_match("/product/i", $url)) continue;
            array_push($urls, $url);
        }
    }
    $resultData = extract_bySitemap($urls, 'buythermoproDotcom_checkLink', $monthsAgo, 1, 'curl_get_v4');
    return $resultData;
}

function buythermoproDotcom_checkLink($link, $urlInfo)
{
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!$urlInfo || !isset($urlInfo['domain'])) return false;
    if (!preg_match("/buythermopro\.com/i", $urlInfo['domain'])) return false;
    if (!preg_match("/\/products\//i", $link)) return false;
    $link = explode('?', $link)[0];
    return explode('#', $link)[0];
}

function buythermoproDotcom_extractManuals($url, $runAuto = 'Yes')
{
    /*
        - Create: 2025-08-27
    */

    $data = array('brand' => 'ThermoPro', 'manualLang' => 'en');
    $domainUrl = getWebsiteUrl($url);
    if (!$domainUrl) return $data;

    $html = curl_get_v4($url);

    // 1. numOfRelatedUrls
    $data['numOfRelatedUrls'] = count(extract_getRelatedProducts($html, 'buythermoproDotcom_checkLink', $domainUrl, 0));

    // 2. Name
    // $data['name'] = get_string_between($html, '<h1', '</h1>');
    // if ($data['name'] != '') $data['name'] = '<h1' . $data['name'];
    // $data['name'] = extract_clearName($data['name']);
    // $data['name'] = normalize_productName($data['name']);
    // if (!$data['name']) return $data;

    // 3. Name & Model
    if (preg_match('/Samita\.SamitaLocksAccessParams\.product\s*=\s*(\{.*?\});/s', $html, $m)) {
        $modelJson = $m[1];
        $productData = json_decode($modelJson, true);
        if (isset($productData['variants']) && count($productData['variants']) > 0) {
            $variants = $productData['variants'];

            // Lấy name
            $data['name'] = extract_clearName($variants[0]['name']);
            $variants_name = extract_clearName($variants[0]['name']);
            $data['name'] = normalize_productName($variants_name);

            // Lấy model từ variant đầu tiên & Xóa cụm -W-[0-9] (package code)
            $variants_sku = extract_clearModel($variants[0]['sku']);
            $data['model'] = preg_replace('/-W-\d+/i', '', $variants_sku);

            // Nếu có nhiều hơn 1 variant -> related models
            if (count($variants) > 1) {
                $related = [];
                for ($i = 1; $i < count($variants); $i++) {
                    if (!empty($variants[$i]['sku'])) {
                        $related[] = extract_clearModel($variants[$i]['sku']);
                    }
                }

                if (count($related) > 0) {
                    $data['related_models'] = $related;
                    $data['modelAlias'] = $related; // alias copy from related
                }
            }
        }
    }
    if ($runAuto == 'Yes' && !$data['model']) return $data;

    $data = extract_checkNameAndBrand($data);

    // 5. Image
    $img = get_string_between($html, '<meta property="og:image" content="', '"');
    if ($img != '') {
        if (substr($img, 0, 2) == '//') $img = 'https:' . $img;
        if (substr($img, 0, 1) == '/') $img = $domainUrl . $img;
        $data['images'] = array(extract_clearUrl($img));
    }

    // 6. Category
    $category = findCategoryFromName($data['name']);
    if ($category) $data['category'] = $category;
    if (!isset($data['category'])) $data['category'] = 'Thermometer';

    // 7. Manual & OtherFiles
    $checkFileExist = array();
    preg_match_all('/<div\s+class\s*=\s*["\']acc_i_bot["\'][^>]*>(.*?)<\/div>/is', $html, $blocks);
    $manualHtml = '';
    if (!empty($blocks[1])) {
        foreach ($blocks[1] as $block) {
            if (preg_match("/\.pdf/i", $block)) {
                $manualHtml = $block;
                break;
            }
        }
    }
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

    // 8. Specifications 
    $data = extract_recheckManualsUrl($data);
    $spec = array();
    preg_match_all('/<tr>(.*?)<\/tr>/s', $html, $rows);
    foreach ($rows[1] as $row) {
        preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $row, $cols);
        if (count($cols[1]) < 2) continue;
        $name = strip_tags($cols[1][0]);
        $name = extract_clearItemFileName($name);
        if ($name == '') continue;

        $value = ($cols[1][1]);
        $value = trim(preg_replace('/\s+/', ' ', $value));
        $value = extract_clearItemFileName($value);

        if ($value == '' || $value == 'No' || $value == 'N/A') continue;

        $spec[] = array(
            'name' => $name,
            'value' => $value
        );
    }

    if (count($spec) > 0) {
        $data['specifications'] = $spec;
    }

    return $data;
}
