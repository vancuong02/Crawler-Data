<?php

function zoellerpumpsDotcom_updateBySitemap($monthsAgo = 3)
{
    $urls = array();
    $urlMaps = array('https://zoellerpumps.com/sitemap_index.xml');
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

    // echo "Total link product: " . count($resultData) . PHP_EOL;
    // foreach ($resultData as $link) {
    //     echo $link . PHP_EOL;
    // }

    $resultData = extract_bySitemap($urls, 'zoellerpumpsDotcom_checkLink', $monthsAgo, 1, 'curl_get_v4');
    return $resultData;
}

function zoellerpumpsDotcom_checkLink($link, $urlInfo)
{
    //https://zoellerpumps.com/product/model-49-sump-pump
    //urlInfo là kết quả của hàm parseURL($url); Gọi trước để truyền vào.
    if (!isset($link) || !isset($urlInfo)) return false;
    if (!$urlInfo || !isset($urlInfo['domain'])) return false;
    if (!preg_match("/zoellerpumps\.com/i", $urlInfo['domain'])) return false;
    if (!preg_match("/\/product\//i", $link)) return false;
    $link = explode('?', $link)[0];
    return explode('#', $link)[0];
}

function zoellerpumpsDotcom_extractManuals($url, $runAuto = 'Yes')
{
    /*
    - https://zoellerpumps.com/product/model-49-sump-pump
    - Created: 2025-08-24
    */
    $data = array(
        'brand' => 'Zoeller',
        'manuallang' => 'en'
    );
    $domainUrl = getWebsiteUrl($url);
    if (!$domainUrl) return $data;

    $html = curl_get_v4($url);
    $data['numOfRelatedUrls'] = count(extract_getRelatedProducts($html, 'zoellerpumpsDotcom_checkLink', $domainUrl, 0));

    //Name
    $data['name'] = get_string_between($html, '<h1', '</h1>');
    if ($data['name'] != '') $data['name'] = '<h1' . $data['name'];
    $data['name'] = extract_clearName($data['name']);
    if (!$data['name']) return $data;

    //Model
    $data['model'] = '';
    if (preg_match('/<span class="sku">(.*?)<\/span>/', $html, $m)) {
        $data['model'] = trim($m[1]);
    }
    $data['model'] = extract_clearModel($data['model']);
    if ($runAuto == 'Yes' && !$data['model']) return $data;


    $data = extract_checkNameAndBrand($data);

    //Images
    $img = get_string_between($html, '<meta property="og:image" content="', '"');
    if ($img != '') {
        if (substr($img, 0, 2) == '//') $img = 'https:' . $img;
        if (substr($img, 0, 1) == '/') $img = $domainUrl . $img;
        $data['images'] = array(extract_clearUrl($img));
    }

    //Category
    $category = findCategoryFromName($data['name']);
    if ($category) $data['category'] = $category;
    if (!isset($data['category'])) $data['category'] = 'Sump Pumps';

    // manual
    $checkFileExist = array();

    // Lấy đầy đủ HTML các khối chứa tài liệu:  <div class="resource_item">...</div>
    $manualHtml = '';
    if (preg_match_all('/<div\s+class="resource_item"[^>]*>.*?<\/div>/is', $html, $mm) && !empty($mm[0])) {
        $manualHtml = implode("\n", $mm[0]);
    }
    preg_match_all('/<a(.*?)<\/a>/s', $manualHtml, $links);

    if ($links) {
        foreach ($links as $a) {
            $item        = $a[0];
            $href        = trim($a[2]);
            $anchorInner = $a[3];

            // URL
            $itemFile = array();
            $itemFile['file'] = $href;
            if ($itemFile['file'] == '') continue;

            if (substr($itemFile['file'], 0, 2) == '//') $itemFile['file'] = 'https:' . $itemFile['file'];
            if (substr($itemFile['file'], 0, 1) == '/')  $itemFile['file'] = $domainUrl . $itemFile['file'];
            $itemFile['file'] = extract_clearUrl($itemFile['file']);
            $itemFile['file'] = explode('?', $itemFile['file'])[0];

            if (!preg_match('/\.pdf(\b|$)/i', $itemFile['file'])) continue;
            if (isset($checkFileExist[md5($itemFile['file'])])) continue;

            if (preg_match('/<strong[^>]*>(.*?)<\/strong>/is', $anchorInner, $st)) {
                $itemFile['name'] = extract_clearItemFileName($st[1]);
            } else {
                $itemFile['name'] = extract_clearItemFileName($anchorInner);
            }
            if (!$itemFile['name']) continue;

            if (!$itemFile['name'] || preg_match("/Brochure|energy|warrant|Catalog|Sales/i", $itemFile['name'])) continue;


            $itemFile['fileType'] = findTypeOfFileFromString($itemFile['name']);
            if (!$itemFile['fileType']) $itemFile['fileType'] = 'Other';

            $itemFile['langCode'] = 'en';
            $langCode = findLangcodeFromString($itemFile['name']);
            if ($langCode) $itemFile['langCode'] = $langCode;

            $checkFileExist[md5($itemFile['file'])] = 1;

            // 1) User/Owner/Operator/Care Guide
            if (
                !isset($data['manualsUrl']) &&
                preg_match('/Owner|Operat|Manual|Care\s*Guide/i', $itemFile['name']) &&
                !preg_match('/Energy|Dimension|Warranty|Specification?/i', $itemFile['name'])
            ) {
                $data['manualsUrl']   = $itemFile['file'];
                // $data['manualLang']   = $itemFile['langCode'];
                $data['fileType']     = ($itemFile['fileType'] === 'Other') ? 'User Manual' : $itemFile['fileType'];
                $data['manualsTitle'] = $itemFile['name'];
                continue;
            }

            // 2) Nếu chưa có manualsUrl thì fallback: Installation Instructions
            if (
                !isset($data['manualsUrl']) &&
                preg_match('/Installation\s*Instructions/i', $itemFile['name'])
            ) {
                $data['manualsUrl']   = $itemFile['file'];
                // $data['manualLang']   = $itemFile['langCode'];
                $data['fileType']     = 'Installation Instruction';
                $data['manualsTitle'] = $itemFile['name'];
                continue;
            }

            // Các file khác -> otherFiles
            if (!isset($data['otherFiles'])) $data['otherFiles'] = array();
            $data['otherFiles'][] = array(
                'name'     => $itemFile['name'],
                'url'      => $itemFile['file'],
                'lang'     => $itemFile['langCode'],
                'type'     => 'origin',
                'fileType' => $itemFile['fileType']
            );
        }
    }

    // ReDetect manualsUrl.
    $data = extract_recheckManualsUrl($data);
    $spec = array();

    if (preg_match_all(
        '/<div[^>]*class="[^"]*divTableCell-sync[^"]*"[^>]*>\s*(?:<strong>)?(.*?)(?:<\/strong>)?\s*<\/div>\s*<div[^>]*class="[^"]*divTableCell-sync[^"]*"[^>]*>(.*?)<\/div>/is',
        $html,
        $matches,
        PREG_SET_ORDER
    )) {

        $seen = [];
        foreach ($matches as $m) {
            $name  = extract_clearItemFileName($m[1]);
            $value = extract_clearItemFileName($m[2]);

            if ($name != '' && $value != '' && $value != 'No' && $value != 'N/A') {
                if (!in_array($name, $seen)) {
                    $spec[] = array(
                        'name'  => $name,
                        'value' => $value
                    );
                    $seen[] = $name;
                }
            }
        }
    }

    if (count($spec) > 0) {
        $data['specifications'] = $spec;
    }

    return $data;
}
