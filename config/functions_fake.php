<?php
function findModelFromString($str)
{
    //Find model in string.
    return false;
}

function findLangcodeFromString($str)
{
    //Function tìm language code của file qua tên filename. Nếu không tìm thấy, trả về 'en' là mặc định.
    //Lang code chi có 2 ký tự.
    return 'en';
}

function findTypeOfFileFromString($str)
{
    /*
    - Function tìm typeOfFile (fileType). Nếu không tìm thấy, trả về 'User Manual'. Bao gồm:
    +/  User Manual
    +/  Installation Instruction
    +/  Specification
    +/  Dimension Guide
    +/  Energy Guide
    +/  Warranty
    +/  User Service
    +/  Other
    */
    return 'User Manual';
}

function findBrandFromString($str)
{
    //Function tìm brand từ string. Nếu không tìm thấy, trả về false;
    return 'TheTestBrand';
}

function findCategoryFromName($str)
{
    //Function tìm category từ string. Nếu không tìm thấy, trả về false;
    return 'TheTestCategory';
}

function extract_getRelatedProducts($html, $functionLinkName, $domainUrl, $priority = 0, $urlPage = '')
{
    //Function tìm url page sản phẩm trong html.
    //Sẽ lọc toàn bộ thẻ <a ...> để lấy href. Từ đó dùng functionLinkName để kiểm tra. 
    $listUrls = array();
    $checkExist = array();
    $resultData = array();
    if (!function_exists($functionLinkName) || gettype($domainUrl) != 'string') return $listUrls;

    preg_match_all('/<a(.*?)<\/a>/s', $html, $m);
    for ($i = 0; $i < count($m[0]); $i++) {
        $item = $m[0][$i];
        $url = get_string_between($item, 'href="', '"');
        if ($url == '') $url = get_string_between($item, "href='", "'");
        if ($url == '') $url = get_string_between($item, "href=", " ");
        if ($url == '') continue;
        if (substr($url, 0, 2) == '//') $url = 'https:' . $url;
        if (substr($url, 0, 1) == '/') $url = $domainUrl . $url;
        if (substr($url, 0, 2) == './' && $urlPage != '') $url = str_replace('./', '/', $urlPage . $url);

        $urlInfo = parseURL($url);
        try {
            $link = call_user_func($functionLinkName, $url, $urlInfo);
        } catch (Exception $e) {
            continue;
        }
        if (!isset($link) || !$link) continue;
        if (isset($checkExist[md5($link)])) continue;

        $checkExist[md5($link)] = 1;
        array_push($listUrls, $link);
        //Save database:
        if (count($listUrls) == 50) {
            /*
            $postData = array(
                'rule' => 'manuals_saveLink',
                'data' => json_encode($listUrls),
                'priority' => $priority
            );
            curl_post('..url',$postData);
            */
            $resultData = array_merge($resultData, $listUrls);
            $listUrls = array();
        }
    }

    if (count($listUrls) > 0) {
        /*
        $postData = array(
            'rule' => 'manuals_saveLink',
            'data' => json_encode($listUrls),
            'priority' => $priority
        );
        curl_post('..url',$postData);
        */
        $resultData = array_merge($resultData, $listUrls);
    }
    //print_r($resultData);
    return $resultData;
    //extract_getRelatedProducts END 
}

function extract_bySitemap($sitemaps = array(), $functionLinkName, $monthsAgo = 3, $priority = 1, $curlFunction = 'curl_get', $domainlReplace = '', $siteMapGz = 'No')
{
    // $domainlReplace is not include http or https.
    //Function tìm url page sản phẩm qua sitemap.
    //sitemap e.g: https://www.bosch-home.com/us/sitemap.xml
    $listUrls = array();
    $checkExist = array();
    $resultData = array();
    if (!function_exists($functionLinkName) || !function_exists($curlFunction)) return $resultData;
    for ($i = 0; $i < count($sitemaps); $i++) {
        $url = $sitemaps[$i];
        if (preg_match("/\.gz$/i", $url) || $siteMapGz == "Yes") {
            $url = "compress.zlib://" . $url; //sitemap is file .gz
            $html = file_get_contents($url);
        } else {
            $html = call_user_func($curlFunction, $url);
        }
        try {
            $xml = new SimpleXMLElement($html);
        } catch (Exception $e) {
            continue;
        }
        foreach ($xml->url as $item) {
            $loc = (string)$item->loc;
            $loc = str_replace(array('<\![CDATA[', ']]>'), '', $loc);
            if (isset($item->lastmod)) {
                $lastmod = (string)$item->lastmod;
                $lastmod = explode('T', $lastmod)[0];
                $diffTime = diffBetweenTwoDate($lastmod, 'now');
                if ($diffTime > ($monthsAgo * 30 * 24 * 60 * 60)) continue; //3 months             
            }
            $urlInfo = parseURL($loc);
            if ($domainlReplace != '' && isset($urlInfo['domain'])) {
                $loc = str_replace($urlInfo['domain'], $domainlReplace, $loc);
                $urlInfo = parseURL($loc);
            }
            try {
                $link = call_user_func($functionLinkName, $loc, $urlInfo);
            } catch (Exception $e) {
                continue;
            }
            if (!$link) continue;
            if (isset($checkExist[md5($link)])) continue;
            $checkExist[md5($link)] = 1;
            array_push($listUrls, $link);
            if (count($listUrls) == 50) {
                /*
                $postData = array(
                    'rule' => 'manuals_saveLink',
                    'data' => json_encode($listUrls),
                    'priority' => $priority
                );
                curl_post('...url',$postData);
                */
                $resultData = array_merge($resultData, $listUrls);
                $listUrls = array();
            }
        }
    }
    if (count($listUrls) > 0) {
        /*
        $postData = array(
            'rule' => 'manuals_saveLink',
            'data' => json_encode($listUrls),
            'priority' => $priority
        );
        curl_post('..url',$postData);
        */
        $resultData = array_merge($resultData, $listUrls);
    }
    return $resultData;
    //extract_bySitemap END.
}

function manuals_DomainFuction($domain)
{
    //Su dung trong crawler_findFuntions_byUrl(). Ko quan tâm function này.
    return false;
}

function save_manuals_saveLink_GetInfo($data)
{
    $postData = array(
        'rule' => 'manuals_saveLink_GetInfo',
        'data' => json_encode($data)
    );
    //curl_post('',$postData);
    return true;
}

function save_manuals_saveLink($data, $priority = 1)
{
    $postData = array(
        'rule' => 'manuals_saveLink',
        'data' => json_encode($data),
        'priority' => $priority
    );
    //curl_post('',$postData);
    return true;
}

function normalize_productName($name)
{
    if (!$name) return '';
    // Nếu chứa Value Pack / Combo / Bundle thì bỏ qua luôn
    if (preg_match('/\b(Value Pack|Combo|Bundle)\b/i', $name)) {
        return '';
    }
    // Xóa các cụm dạng "2-Pack", "3-Packs"...
    $name = preg_replace('/\b\d+\s*-\s*Packs?\b/i', '', $name);
    return $name;
}
