<?php
// Website 6. https://www.ametekesp.com/surgex/axess-elite/axess-elite-120-208v

function ametekespDotcom_updateBySitemap($monthsAgo = 3)
{
    $urls = array('https://www.ametekesp.com/sitemap.xml');
    $resultData = extract_bySitemap($urls, 'ametekespDotcom_checkLink', $monthsAgo, 1, 'curl_get_v4');
    return $resultData;
}

function ametekespDotcom_checkLink($link, $urlInfo)
{
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!$urlInfo || !isset($urlInfo['domain'])) return false;
    if (!preg_match("/ametekesp\.com/i", $urlInfo['domain'])) return false;

    $link = explode('?', $link)[0];
    $link = explode('#', $link)[0];

    // Điều kiện được coi là link products: path phải có ít nhất 3 phân đoạn
    $path = parse_url($link, PHP_URL_PATH);
    $segments = array_filter(explode('/', $path));
    if (count($segments) < 3) return false;

    // Thêm điều kiện: bỏ các link chứa từ khóa: 'resources', 'about-us', 'contact', 'news'
    $keywords = ['resources', 'about-us', 'contact', 'news'];
    foreach ($keywords as $word) {
        if (stripos($link, $word) !== false) {
            return false;
        }
    }

    return $link;
}

function ametekespDotcom_extractManuals($url, $runAuto = 'Yes')
{
    /*
        - Create: 2025-08-29
    */

    $data = array('brand' => 'Ametek', 'manualLang' => 'en');
    $domainUrl = getWebsiteUrl($url);
    if (!$domainUrl) return $data;

    $html = curl_get_v4($url);
    // 1. numOfRelatedUrls
    $data['numOfRelatedUrls'] = count(extract_getRelatedProducts($html, 'ametekespDotcom_checkLink', $domainUrl, 0));

    // 2. Name
    $data['name'] = get_string_between($html, '<h1', '</h1>');
    if ($data['name'] != '') $data['name'] = '<h1' . $data['name'];
    $data['name'] = extract_clearName($data['name']);
    if (!$data['name']) return $data;

    // 3. Model
    $data['model'] = get_string_between($html, 'Models:&nbsp;</strong>', '<br />');
    $data['model'] = extract_clearModel($data['model']);
    $models = explode(',', $data['model']);
    $models = array_map('trim', $models);
    $data['model'] = $models[0];
    if ($runAuto == 'Yes' && !$data['model']) return $data;

    $data = extract_checkNameAndBrand($data);

    // 4. Related Model - Model Alias
    if ($data['model'] && count($models) > 1) {
        $data['related_model'] =  array_slice($models, 1);
    }

    // 5. Image
    $img = get_string_between($html, '<img id="phbody_0_phbodycontent_0_ctl06_mainProductImage" class="img-responsive product-image" data-description="Product 1 description" src="', '"');
    if ($img != '') {
        if (substr($img, 0, 2) == '//') $img = 'https:' . $img;
        if (substr($img, 0, 1) == '/') $img = $domainUrl . $img;
        $data['images'] = array(extract_clearUrl($img));
    }

    // 6. Category
    $category = findCategoryFromName($data['name']);
    if ($category) $data['category'] = $category;
    if (!isset($data['category'])) $data['category'] = 'Axess ELITE';

    // 7. Manual & OtherFiles
    $checkFileExist = array();
    $manualHtml = '';
    if (preg_match('/<div class="child_tablist">(.*)<\/div>\s*<\/div>/sU', $html, $matches)) {
        $manualHtml = $matches[1];
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

    $data = extract_recheckManualsUrl($data);

    // 8. Specifications: Nothing

    return $data;
}
