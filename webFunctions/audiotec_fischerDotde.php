<?php
// Website 1. https://www.audiotec-fischer.de/en/helix/accessories/hec-hd-audio-usb-interface

function audiotec_fischerDotde_updateBySitemap($monthsAgo = 3)
{
    $urls = array('https://www.audiotec-fischer.de/web/sitemap/shop-3/sitemap-1.xml.gz');
    $resultData = extract_bySitemap($urls, 'audiotec_fischerDotde_checkLink', $monthsAgo, 1, 'curl_get_v4', "", "Yes");
    return $resultData;
}

function audiotec_fischerDotde_checkLink($link, $urlInfo)
{
    // https://www.audiotec-fischer.de/en/helix/accessories/hec-hd-audio-usb-interface
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!$urlInfo || !isset($urlInfo['domain'])) return false;
    if (!preg_match("/audiotec-fischer\.de/i", $urlInfo['domain'])) return false;

    $link = explode('?', $link)[0];
    $link = explode('#', $link)[0];

    // loại bỏ link thuộc news, blog, brands, product-archive, listing, tools
    if (preg_match("~/(news|blog|brands|product-archive|media|knowledge-base|listing|tools)~i", $link)) {
        return false;
    }

    // Chỉ giữ link có ít nhất 3 cấp sau /en/
    // format: /en/<brand>/<category>/<product-slug>
    $path = parse_url($link, PHP_URL_PATH);

    // Không nhận URL kết thúc bằng "/"
    if (substr($path, -1) === '/') {
        return false;
    }

    $segments = array_values(array_filter(explode('/', trim($path, '/'))));

    if (!isset($segments[0]) || strtolower($segments[0]) !== 'en') {
        return false;
    }
    if (count($segments) < 4) {
        return false;
    }

    return $link;
}



function audiotec_fischerDotde_extractManuals($url, $runAuto = 'Yes')
{
    /*
    - https://www.audiotec-fischer.de/en/helix/accessories/hec-hd-audio-usb-interface
    - Create 2025-08-26
    */

    $data = array('brand' => 'Audiotec Fischer', 'manualLang' => 'en');
    $domainUrl = getWebsiteUrl($url);
    if (!$domainUrl) return $data;

    $html = curl_get_v4($url);
    // 1. numOfRelatedUrls
    $data['numOfRelatedUrls'] = count(extract_getRelatedProducts($html, 'audiotec_fischerDotde_checkLink', $domainUrl, 0));

    // 2. Name
    $data['name'] = get_string_between($html, '<h1', '</h1>');
    if ($data['name'] != '') $data['name'] = '<h1' . $data['name'];
    $data['name'] = extract_clearName($data['name']);
    if (!$data['name']) return $data;

    // 3. Model
    $data['model'] = get_string_between($html, '<span itemprop="sku" content="', '"');
    $data['model'] = extract_clearModel($data['model']);
    if ($runAuto == 'Yes' && !$data['model']) return $data;

    $data = extract_checkNameAndBrand($data);

    // 4. Related Model - Model Alias
    if ($data['model']) {
        $related = [];
        // Lấy mã thị trường Đức (WEEE-Reg.-Nr.)
        $weee = get_string_between($html, '<span class="entry--content" itemprop="weee">', '</span>');
        if ($weee != '') $related[] = extract_clearModel($weee);

        if (count($related) > 0) {
            $data['related_models'] = $related;
            $data['modelAlias'] = $related; // alias copy from related
        }
    }

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
    if (!isset($data['category'])) $data['category'] = 'Helix';

    // 7. Manual & OtherFiles
    $checkFileExist = array();
    $manualHtml = get_string_between($html, '<ul class="content--list list--unstyled">', '</ul>');
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
        $name = get_string_between($row, '<strong>', '</strong>');
        $name = extract_clearItemFileName($name);
        if ($name == '') continue;

        $value = get_string_between($row, '</td>', '</td>');
        preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $row, $cols);

        if (count($cols[1]) < 2) continue;
        $value = extract_clearItemFileName($cols[1][1]);
        if ($value == '' || $value == 'No' || $value == 'N/A') continue;

        $itemData = array(
            'name' => $name,
            'value' => $value
        );
        $spec[] = $itemData;
    }
    if (count($spec) > 0) {
        $data['specifications'] = $spec;
    }

    return $data;
}
