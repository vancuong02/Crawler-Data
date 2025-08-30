<?php

function curl_get_saveCookie($url, $COOKIEJAR = '', $COOKIEFILE = '')
{
    $parts = parse_url($url);
    $host = $parts['host'];
    $ch = curl_init();
    $header = array(
        'GET /1575051 HTTP/1.1',
        "Host: {$host}",
        'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language:en-US,en;q=0.8',
        'Cache-Control:max-age=0',
        'Connection:keep-alive',
        'Host:adfoc.us',
        'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36',
    );
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    if ($COOKIEJAR != '') {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $COOKIEJAR);
    }
    if ($COOKIEFILE != '') {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $COOKIEFILE);
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function curl_get($url)
{
    $parts = parse_url($url);
    $host = $parts['host'];
    $ch = curl_init();
    $header = array(
        'GET /1575051 HTTP/1.1',
        "Host: {$host}",
        'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language:en-US,en;q=0.8',
        'Cache-Control:max-age=0',
        'Connection:keep-alive',
        'Host:adfoc.us',
        'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36',
    );
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function curl_post($url, $postData, $header = array())
{
    global $ROOT_FOLDER;
    if (!isset($url) || !isset($postData)) return;

    /* data example/
    $post = [
        'username' => 'user1',
        'password' => 'passuser1',
        'gender'   => 1,
    ];  
    */

    $agents = array(
        'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1',
        'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.9) Gecko/20100508 SeaMonkey/2.0.4',
        'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)',
        'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_7; da-dk) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1'
    );
    if (count($header) == 0) {
        $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
        $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 300";
        $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $header[] = "Accept-Language: en-us,en;q=0.5";
        $header[] = "Pragma: ";
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HEADER, 0); //Khong lay header trong response. 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 80);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    $data = curl_exec($ch);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response_code == 200) {
        return $data;
    }
    return;
}

function curl_get_v2($url, $headers = array(), $headerOrigin = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    if (gettype($headers) != 'array' || count($headers) == 0) {
        $headers = array();
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Pragma: no-cache';
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.122 Safari/537.36';
        if ($headerOrigin != '') $headers[] = 'Origin: ' . $headerOrigin; //important.
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
        $headers[] = 'Sec-Fetch-Site: same-origin';
        $headers[] = 'Sec-Fetch-Mode: navigate';
        $headers[] = 'Sec-Fetch-User: ?1';
        $headers[] = 'Sec-Fetch-Dest: document';
        //$headers[] = 'Referer: https://owner.lincoln.com/tools/account/how-tos/owner-manuals.html';
        $headers[] = 'Accept-Language: it,it-IT;q=0.9,en-US;q=0.8,en;q=0.7,ar;q=0.6'; //important
        $headers[] = 'Cookie: ASPSESSIONIDAWDDSADQ=LLOJGMICLOOKHGEHJHPBMBKE'; //important.
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); //Time out for trying to connect.
    curl_setopt($ch, CURLOPT_TIMEOUT, 180); //Time out for all curl.
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function curl_get_v3($url, $headers = array())
{
    $ch = curl_init();
    $headerDf = array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Encoding: gzip, deflate, br, zstd',
        'Accept-Language: en-US,en;q=0.9,vi;q=0.8,la;q=0.7',
        'Connection: keep-alive',
        'Sec-Ch-Ua: "Chromium";v="128", "Not;A=Brand";v="24", "Brave";v="128"',
        'Sec-Ch-Ua-Mobile: ?0',
        'Sec-Ch-Ua-Platform: "Windows"',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'Sec-Gpc: 1',
        'Upgrade-Insecure-Requests: 1',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36'
    );
    if (count($headers) < 1) $headers = $headerDf;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); //Time out for trying to connect.
    curl_setopt($ch, CURLOPT_TIMEOUT, 180); //Time out for all curl.
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function getWebsiteUrl($url = '')
{
    //Function tìm domain Url trong một url: dạng https://domain.com . Không có gạch chéo ở cuối.
    $parseUrl = parse_url($url);
    if (!$parseUrl || !$parseUrl['host']) return false;
    if (!$parseUrl['scheme']) $parseUrl['scheme'] == 'http';
    return $parseUrl['scheme'] . '://' . $parseUrl['host'];
}

function get_string_between($string, $start, $end)
{
    //Funtion tìm string giưa 2 string. Luôn trả về string hoặc '';
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini === false) return '';
    $newString = substr_replace($string, '', $ini, strlen($start));
    if (strpos($newString, $end) === false) return '';

    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    if ($len < 1) return '';
    return substr($string, $ini, $len);
}

function diffBetweenTwoDate($start = '', $until = '')
{
    //$start and $until have formart yyyy-mm-dd.
    if ($until == '' || $until == 'now') $until = date('Y-m-d');
    $startInt = strtotime($start);
    $untilInt = strtotime($until);
    $datediff = $untilInt - $startInt;
    return $datediff; //in seconds.
}

function parseURL($url, $retdata = true)
{
    //Function phân tích url, tương tự parse_url(). Có một số field bổ xung. 
    /**
     * Parse and check the URL Sets the following array parameters
     * scheme, host, port, user, pass, path, query, fragment, dirname, basename, filename, extension, domain, 
     * domainX, absolute address
     * @param string $url of the site
     * @param string $retdata if true then return the parsed URL data otherwise set the $urldata class variable
     * @return array|mixed|boolean
     */
    $url = substr($url, 0, 4) == 'http' ? $url : 'http://' . $url; //assume http if not supplied
    $url = str_replace('&amp;', '&', $url);
    $urldata = parse_url($url);
    if ($urldata === false || !isset($urldata['host'])) return false; //invalid URL

    $path_parts = pathinfo($urldata['host']);
    $urldata['hostConvert'] = preg_replace('/www\./i', '', $urldata['host']);
    $tmp = explode('.', $urldata['hostConvert']);
    $n = count($tmp);
    if ($n >= 2) {
        if ($n == 4 || ($n == 3 && strlen($tmp[($n - 2)]) <= 3)) {
            $urldata['domain'] = $tmp[($n - 3)] . "." . $tmp[($n - 2)] . "." . $tmp[($n - 1)];
            $urldata['tld'] = $tmp[($n - 2)] . "." . $tmp[($n - 1)]; //top-level domain
            $urldata['root'] = $tmp[($n - 3)]; //second-level domain
            $urldata['subdomain'] = $n == 4 ? $tmp[0] : (($n == 3 && strlen($tmp[($n - 2)]) <= 3) ? $tmp[0] : '');
        } else {
            $urldata['domain'] = $tmp[($n - 2)] . "." . $tmp[($n - 1)];
            $urldata['tld'] = $tmp[($n - 1)];
            $urldata['root'] = $tmp[($n - 2)];
            $urldata['subdomain'] = $n == 3 ? $tmp[0] : '';
        }
    }
    $specialDomains = array('hp.com' => array('.hp.com'));
    if (isset($urldata['domain'])) {
        foreach ($specialDomains as $k => $v) {
            foreach ($v as $kwItem) {
                $pattern = "/" . $kwItem . "/i";
                if (!preg_match($pattern, $urldata['domain'])) continue;
                $urldata['domain'] = $k;
                break;
            }
        }
    }

    $urldata['basename'] = $path_parts['basename'];
    $urldata['filename'] = $path_parts['filename'];
    if (isset($path_parts['extension'])) $urldata['extension'] = $path_parts['extension'];
    $urldata['base'] = $urldata['scheme'] . "://" . $urldata['host'];
    $urldata['abs'] = (isset($urldata['path']) && strlen($urldata['path'])) ? $urldata['path'] : '/';
    $urldata['abs'] .= (isset($urldata['query']) && strlen($urldata['query'])) ? '?' . $urldata['query'] : '';

    //Convert and Check Subdomain.
    if (isset($urldata['subdomain'])) {
        $urldata['subdomain'] = strtolower($urldata['subdomain']);
        if ($urldata['subdomain'] == 'www') unset($urldata['subdomain']);
    }

    //convert domain and ldt
    if (isset($urldata['domain'])) $urldata['domain'] = strtolower($urldata['domain']);
    if (isset($urldata['tld'])) $urldata['tld'] = strtolower($urldata['tld']);

    //Set data
    if ($retdata) {
        return $urldata;
    } else {
        $this->urldata = $urldata;
        return true;
    }
}

// Config
function curl_get_v4($url, $headers = array())
{
    $ch = curl_init();
    $headerDf = array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9,vi;q=0.8,la;q=0.7',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36'
    );

    if (empty($headers)) $headers = $headerDf;

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);

    $result = curl_exec($ch);

    if ($result === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error: $error");
    }

    curl_close($ch);
    return $result;
}
