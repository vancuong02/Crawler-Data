<?php
// Website 9: https://shopping.panasonic.ca/products/home-appliances/rice-cookers/srdl105/product.aspx?ID=78e518be-2480-409a-a880-22c70f2bee62&g=96768f4a-af6a-48be-a21b-c6770e28c7bf&c=16c89151-c4cc-4bca-a424-1ec3093634f5&lang=en

function panasonicDotca_updateBySitemap($monthsAgo = 3)
{
    $urls = array('https://shopping.panasonic.ca/sitemap.xml');
    $resultData = extract_bySitemap($urls, 'panasonicDotca_checkLink', $monthsAgo, 1, 'curl_get_v4');
    return $resultData;
}

function panasonicDotca_checkLink($link, $urlInfo)
{
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!$urlInfo || !isset($urlInfo['domain'])) return false;
    if (!preg_match("/panasonic\.ca/i", $urlInfo['domain'])) return false;
    if (!preg_match("/\/products\//i", $link)) return false;
    $path = parse_url($link, PHP_URL_PATH);
    $segments = array_filter(explode('/', $path));
    if (count($segments) < 5) return false;

    return explode('#', $link)[0];
}

function panasonicDotca_extractManuals($url, $runAuto = 'Yes')
{
    $data = array('brand' => 'Panasonic', 'manualLang' => 'en');
    $domainUrl = getWebsiteUrl($url);
    if (!$domainUrl) return $data;

    $html = curl_get_v4($url);
    $data['numOfRelatedUrls'] = count(extract_getRelatedProducts($html, 'panasonicDotca_checkLink', $domainUrl, 0));

    // 1. Name & Model
    $htmlApplication = get_string_between($html, '<script type="application/ld+json">', '</script>');
    if ($htmlApplication) {
        $json = json_decode($htmlApplication, true);
        if ($json) {
            $data['name']  = extract_clearName($json['name'] ?? '');
            $data['model'] = extract_clearModel($json['mpn'] ?? '');
        }
    }
    if (!$data['name']) return $data;
    if ($runAuto == 'Yes' && !$data['model']) return $data;

    $data = extract_checkNameAndBrand($data);

    // 3. Related Model:
    if ($data['model']) {
        $related_model = preg_replace('/UK$/i', '', $data['model']);
        if ($related_model != $data['model']) $data['related_models'] = array($related_model);
    }

    // 4. Image
    $img = get_string_between($html, '<img id="ctl00_ctl00_ContentPlaceHolder1_ContentPlaceHolder1_Image1" src="', '"');
    if ($img != '') {
        if (substr($img, 0, 2) == '//') $img = 'https:' . $img;
        if (substr($img, 0, 1) == '/') $img = $domainUrl . $img;
        $data['images'] = array(extract_clearUrl($img));
    }

    // 5. Category
    $category = findCategoryFromName($data['name']);
    if ($category) $data['category'] = $category;
    if (!isset($data['category'])) $data['category'] = 'Rice Cookers';

    // 6. Manual
    $checkFileExist = array();
    $manualHtml = get_string_between($html, '<ul class="prod-foot">', '</div>');
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

    // 7. Specifications: Nothing

    return $data;
}
