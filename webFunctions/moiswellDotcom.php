<?php
// Website 5. https://moiswell.com/collections/200-pints/products/145-pints-crawl-space-dehumidifier-with-pump-and-drain-hose-moiswell-defender-mp70

function moiswellDotcom_updateBySitemap($monthsAgo = 3)
{
    $urls = array();
    $urlMaps = array('https://moiswell.com/sitemap.xml');
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
    $resultData = extract_bySitemap($urls, 'moiswellDotcom_checkLink', $monthsAgo, 1, 'curl_get_v4');
    return $resultData;
}

function moiswellDotcom_checkLink($link, $urlInfo)
{
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!$urlInfo || !isset($urlInfo['domain'])) return false;
    if (!preg_match("/moiswell\.com/i", $urlInfo['domain'])) return false;
    if (!preg_match("/\/products\//i", $link)) return false;

    $blockedSlugs = ['payment-of-the-difference', 'refund-request', 'return'];
    foreach ($blockedSlugs as $slug) {
        if (stripos($link, $slug) !== false) {
            return false;
        }
    }
    $link = explode('?', $link)[0];
    return explode('#', $link)[0];
}

function moiswellDotcom_extractManuals($url, $runAuto = 'Yes')
{
    /*
        - Create: 2025-08-29
    */

    $data = array('brand' => 'Moiswell', 'manualLang' => 'en');
    $domainUrl = getWebsiteUrl($url);
    if (!$domainUrl) return $data;

    $html = curl_get_v4($url);
    // 1. numOfRelatedUrls
    $data['numOfRelatedUrls'] = count(extract_getRelatedProducts($html, 'moiswellDotcom_checkLink', $domainUrl, 0));

    // 2. Name
    $data['name'] = get_string_between($html, '<h1', '</h1>');
    if ($data['name'] != '') $data['name'] = '<h1' . $data['name'];
    $data['name'] = extract_clearName($data['name']);
    if (!$data['name']) return $data;

    // 3. Model
    $data['model'] = get_string_between($html, '<br><span style="font-weight: bold;">Model</span>:', '<br>');
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
    if (!isset($data['category'])) $data['category'] = '<200 Pints';

    // 7. Manual & OtherFiles
    $checkFileExist = array();
    $manualHtml = get_string_between($html, '<div data-pf-type="Accordion.Content.Wrapper" class="sc-iGgVNO dygSQz pf-122_">', '<div data-pf-type="Accordion.Content.Wrapper" class="sc-iGgVNO dygSQz pf-131_">');
    preg_match_all('/<a(.*?)<\/a>/s', $manualHtml, $m);
    $isManualSection = (stripos($manualHtml, '>Manuals<') !== false);

    if ($m != false) {
        for ($j = 0; $j < count($m[0]); $j++) {
            $item = $m[0][$j];
            $itemFile = array();

            // --- URL ---
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
            if ($isManualSection && !preg_match('/manual/i', $itemFile['name'])) {
                $itemFile['name'] .= ' User Manual';
            }

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
    $specHtml = get_string_between(
        $html,
        '<div data-pf-type="Accordion.Content.Wrapper" class="sc-iGgVNO dygSQz pf-106_">',
        '</div></div></div></div></div></div>'
    );

    // Tên (name) nằm trong <span style="font-weight:bold;">…</span> hoặc <strong>…</strong>
    preg_match_all('/<(?:span|strong)[^>]*>(.*?)<\/(?:span|strong)>\s*:?(&nbsp;)?\s*([^<]*)/is', $specHtml, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $name  = extract_clearItemFileName($m[1]);
        $value = extract_clearItemFileName($m[3]);

        if ($name == '' || $value == '' || $value == 'No' || $value == 'N/A') continue;

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
