<?php
function moenDotcom_updateBySitemap($monthsAgo = 3)
{
    $urls = array();
    $urlMaps = array('https://shop.moen.com/sitemap.xml');
    for ($m = 0; $m < count($urlMaps); $m++) {
        $html = curl_get($urlMaps[$m]);
        $xml = new SimpleXMLElement($html);
        foreach ($xml->sitemap as $item) {
            if (!isset($item->loc)) continue;
            $url = (string)$item->loc;
            if (!preg_match("/product/i", $url)) continue;
            array_push($urls, $url);
        }
    }
    $resultData = extract_bySitemap($urls, 'moenDotcom_checkLink', $monthsAgo, 0, 'curl_get');
    return $resultData;
}

function moenDotcom_checkLink($link, $urlInfo)
{
    //https://shop.moen.com/products/gxp50c
    //urlInfo là kết quả của hàm parseURL($url); Gọi trước để truyền vào.
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!$urlInfo || !isset($urlInfo['domain'])) return false;
    if (!preg_match("/moen\.com/i", $urlInfo['domain'])) return false;
    if (!preg_match("/\/products\//i", $link)) return false;
    $link = explode('?', $link)[0];
    return explode('#', $link)[0];
}

function moenDotcom_extractManuals($url, $runAuto = 'Yes')
{
    /*
    - https://shop.moen.com/products/gxp50c
    - Created: 2025-06-26
    */
    $data = array('brand' => 'Moen', 'manualLang' => 'en');
    $domainUrl = getWebsiteUrl($url);
    if (!$domainUrl) return $data;

    $html = curl_get($url);
    $data['numOfRelatedUrls'] = count(extract_getRelatedProducts($html, 'moenDotcom_checkLink', $domainUrl, 0));

    //Name
    $data['name'] = get_string_between($html, '<h1', '</h1>');
    if ($data['name'] != '') $data['name'] = '<h1' . $data['name'];
    $data['name'] = extract_clearName($data['name']);
    if (!$data['name']) return $data;

    //Model
    $data['model'] = get_string_between($html, '"sku": "', '"');
    $data['model'] = extract_clearModel($data['model']);
    if ($runAuto == 'Yes' && !$data['model']) return $data;

    $data = extract_checkNameAndBrand($data);

    //Images
    $img = get_string_between($html, '<meta property="og:image:secure_url" content="', '"');
    if ($img != '') {
        if (substr($img, 0, 2) == '//') $img = 'https:' . $img;
        if (substr($img, 0, 1) == '/') $img = $domainUrl . $img;
        $data['images'] = array(extract_clearUrl($img));
    }

    //Category
    $category = findCategoryFromName($data['name']);
    if ($category) $data['category'] = $category;
    if (!isset($data['category'])) $data['category'] = 'Kitchen & Bath Fixtures';

    //manual
    $checkFileExist = array();
    $manualHtml = get_string_between($html, 'Documents (PDF)</h3>', '</details>');
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
            $itemFile['file'] = explode('?', $itemFile['file'])[0];
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

    //ReDetect manualsUrl.
    $data = extract_recheckManualsUrl($data);

    $spec = array();
    $specArr = explode('<div class="spec__item">', $html);
    for ($j = 0; $j < count($specArr); $j++) {
        $item = $specArr[$j];
        $itemData = array();
        $itemData['name'] = get_string_between($item, '<div class="spec__item__label">', '</div>');
        $itemData['name'] = extract_clearItemFileName($itemData['name']);
        if ($itemData['name'] == '') continue;

        $itemData['value'] = get_string_between($item, '<div class="spec__item__value">', '</div>');
        $itemData['value'] = extract_clearItemFileName($itemData['value']);
        if ($itemData['value'] == '' || $itemData['value'] == 'No' || $itemData['value'] == 'N/A') continue;
        array_push($spec, $itemData);
    }
    if (count($spec) > 0) $data['specifications'] = $spec;

    return $data;
}
