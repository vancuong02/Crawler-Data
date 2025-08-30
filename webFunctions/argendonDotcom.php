<?php
// Website 4. https://www.argendon.com/products/argendon-crawl-space-bundle-shield-35p-dehumidifier-with-remote-control-and-hanging-kit

// => Tên không hợp lệ vì có chứa từ khóa "Bundle"

function argendonDotcom_updateBySitemap($monthsAgo = 3)
{
    $urls = array();
    $urlMaps = array('https://www.argendon.com/sitemap.xml');
    for ($m = 0; $m < count($urlMaps); $m++) {
        $html = curl_get_v4($urlMaps[$m]);
        $xml = new SimpleXMLElement($html);
        foreach ($xml->sitemap as $item) {
            if (!isset($item->loc)) continue;
            $url = (string)$item->loc;
            if (!preg_match("/products/i", $url)) continue;
            array_push($urls, $url);
        }
    }
    $resultData = extract_bySitemap($urls, 'argendonDotcom_checkLink', $monthsAgo, 1, 'curl_get_v4');
    return $resultData;
}

function argendonDotcom_checkLink($link, $urlInfo)
{
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!$urlInfo || !isset($urlInfo['domain'])) return false;
    if (!preg_match("/argendon\.com/i", $urlInfo['domain'])) return false;
    if (!preg_match("/\/products\//i", $link)) return false;
    $link = explode('?', $link)[0];
    return explode('#', $link)[0];
}

function argendonDotcom_extractManuals($url, $runAuto = 'Yes')
{
    /*
        - Create: 2025-08-29
    */

    $data = array('brand' => 'Argendon', 'manualLang' => 'en');
    $domainUrl = getWebsiteUrl($url);
    if (!$domainUrl) return $data;

    $html = curl_get_v4($url);
    // 1. numOfRelatedUrls
    $data['numOfRelatedUrls'] = count(extract_getRelatedProducts($html, 'argendonDotcom_checkLink', $domainUrl, 0));

    // 2. Name
    $data['name'] = get_string_between($html, '<h1', '</h1>');
    if ($data['name'] != '') $data['name'] = '<h1' . $data['name'];
    $data['name'] = extract_clearName($data['name']);
    $data['name'] = normalize_productName($data['name']);
    if (!$data['name']) return $data;

    // 3. Model
    $data['model'] = get_string_between($html, '<h2 dir="ltr"><strong>Model: ', '</strong></h2>');
    $data['model'] = extract_clearModel($data['model']);
    if ($runAuto == 'Yes' && !$data['model']) return $data;

    $data = extract_checkNameAndBrand($data);

    // 4. Related Model - Model Alias: Nothing

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
    if (!isset($data['category'])) $data['category'] = 'Designer';

    // 7. Manual & OtherFiles
    $checkFileExist = array();
    $manualHtml = get_string_between($html, '<h4 class="attach-box-main-title">ATTACHED FILES</h4>', '<div id="product-social-links">');
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
    preg_match_all('/<h4 class="third-col-main-title">TECHNICAL DATA<\/h4>(.*?)<\/div>/is', $html, $blocks);
    foreach ($blocks[1] as $block) {
        $name = get_string_between($block, '<span class="">', '</span>');
        $name = extract_clearItemFileName($name);
        if ($name == '') continue;
        $value = get_string_between($block, '<span class="attr-value-third-col">', '</span>');
        $value = extract_clearItemFileName($value);
        if ($value == '' || $value == 'No' || $value == 'N/A') continue;
        $spec[] = array(
            'name'  => $name,
            'value' => $value
        );
    }
    if (count($spec) > 0) {
        $data['specifications'] = $spec;
    }


    return $data;
}
