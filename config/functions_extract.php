<?php
function extract_recheckManualsUrl($data = array(), $keywords = '')
{
    //Function tìm lại manualsUrl nếu chưa có.
    if ($keywords == '') $keywords = 'manual|owner|guide|instruction|operat|installation|Guía|instalación|dueña|instruccion';
    if (!isset($data['manualsUrl']) && isset($data['otherFiles'])) {
        for ($h = 0; $h < count($data['otherFiles']); $h++) {
            if (preg_match("/" . $keywords . "/i", $data['otherFiles'][$h]['name']) && !preg_match("/energy|dimension|warranty|specification|sheet/is", $data['otherFiles'][$h]['name'])) {
                $data['manualsUrl'] = $data['otherFiles'][$h]['url'];
                $data['manualLang'] = $data['otherFiles'][$h]['lang'];
                $data['fileType'] = $data['otherFiles'][$h]['fileType'];
                $data['manualsTitle'] = $data['otherFiles'][$h]['name'];
                unset($data['otherFiles'][$h]);
                $data['otherFiles'] = array_values($data['otherFiles']);
                if (count($data['otherFiles']) == 0) unset($data['otherFiles']);
                break;
            }
        }
    }
    return $data;
}

function extract_checkNameAndBrand($data = array())
{
    //Function xử lý name của product, để sao cho có cú pháp: brand + model +...
    if (isset($data['brand'])) $data['name'] = trim(preg_replace('/' . $data['brand'] . '/i', ' ', $data['name']));
    if (isset($data['model']) && $data['model'] != '') {
        $data['name'] = trim(preg_replace('/' . preg_quote($data['model'], '/') . '/i', ' ', $data['name']));
        if (!preg_match('/' . preg_quote($data['model'], '/') . '/i', $data['name'])) $data['name'] = $data['model'] . ' ' . $data['name'];
    }
    if (isset($data['brand']) && !preg_match('/' . $data['brand'] . '/i', $data['name'])) $data['name'] = $data['brand'] . ' ' . $data['name'];
    $data['name'] = preg_replace('/\s+/', ' ', $data['name']);
    return $data;
}

function extract_clearModel($str)
{
    //trung voi extract_convertModel();
    if (gettype($str) != 'string') return $str;
    $str = strip_tags($str);
    $str = str_replace(array('&nbsp;', '&#160;'), ' ', $str);
    $str = str_replace(array('&nbsp'), ' ', $str); //Some website missing.
    $str = preg_replace('/Series|^SKU|Model/i', '', $str);
    $str = preg_replace('/®|™|\||\(|\)|u2122|u201d/', '', $str);
    $str = preg_replace('/^(|\s)(:|-|#)/', '', $str);
    $str = preg_replace('/\.(|\s)$/', '', $str);
    $str = preg_replace('/\s\s+/', ' ', $str);
    $str = trim(strtoupper($str));
    return $str;
}

function extract_clearName($str)
{
    //trung voi extract_convertName().
    if (gettype($str) != 'string') return $str;
    $str = strip_tags($str);
    $str = preg_replace('/ \|\s+\$([0-9]+)/', ' ', $str);
    $str = str_replace(array('&nbsp;', '&#160;'), ' ', $str);
    $str = str_replace(array('&nbsp'), ' ', $str); //Some website missing.
    $str = str_replace(array('\u0026', '&amp;'), '&', $str);
    $str = str_replace(array('|', '\ ', '/ ', ':', '�', '\xA0', 'DISCONTINUED', '**'), '', $str);
    $str = str_replace(array('&quot;', '&amp;quot;', '&#034;', '″', '&#x27;&#x27;', '&#8243;'), '"', $str);
    $str = str_replace(array('&#039;', '&#8217;', '’', '&#x27;'), "'", $str);
    $str = preg_replace('/®|™|\||\(|\)|u2122|u201d|&#174;/', '', $str);
    $str = preg_replace('/\s\s+/', ' ', $str);
    $str = preg_replace('/(-|\.|_|,)(|\s+)$/', '', $str);
    $str = trim(htmlspecialchars_decode($str));
    return $str;
}

function extract_clearItemFileName($str)
{
    if (gettype($str) != 'string') return $str;
    $str = strip_tags($str);
    $str = str_replace(array('&nbsp;', '&#160;'), ' ', $str);
    $str = preg_replace('/Mannuel/i', 'Manual', $str);
    $str = str_replace(array('View/download', 'Download', '(', ')', '�', '\xA0', 'view'), '', $str);
    $str = str_replace(array('Owner&#039;s'), array('Owner\'s'), $str);
    $str = str_replace(array('\u0026', '&amp;'), '&', $str);
    $str = str_replace(array('&quot;', '&amp;quot;', '&#034;', '″', '&#x27;&#x27;'), '"', $str);
    $str = str_replace(array('&#039;', '&#8217;', '’', '&#39;', '&#x27;'), "'", $str);
    $str = preg_replace('/pdf(|\s)$/i', '', $str);
    $str = preg_replace('/®|™|\||\(|\)|u2122|u201d|&#174;/', '', $str);
    $str = preg_replace('/_/', ' ', $str);
    $str = preg_replace('/\s+/', ' ', $str);
    $str = preg_replace('/-(|\s+)$/', '', $str);
    $str = trim($str);
    $str = ucfirst($str);
    return $str;
}

function extract_clearUrl($str)
{
    if (gettype($str) != 'string') return $str;
    $str = trim($str);
    $str = str_replace(array(' ', '&amp;'), array('%20', '&'), $str);
    return $str;
}



function lookup_langCode_fromLangeCode3Chars($str)
{
    if (gettype($str) != 'string') return false;
    $langs3Chars = array("aar" => "aa", "abk" => "ab", "afr" => "af", "aka" => "ak", "amh" => "am", "ara" => "ar", "arg" => "an", "asm" => "as", "ava" => "av", "ave" => "ae", "aym" => "ay", "aze" => "az", "bak" => "ba", "bam" => "bm", "bel" => "be", "ben" => "bn", "bih" => "bh", "bis" => "bi", "bos" => "bs", "bre" => "br", "bul" => "bg", "cat" => "ca", "cha" => "ch", "che" => "ce", "chu" => "cu", "chv" => "cv", "cor" => "kw", "cos" => "co", "cre" => "cr", "dan" => "da", "div" => "dv", "dzo" => "dz", "eng" => "en", "epo" => "eo", "est" => "et", "ewe" => "ee", "fao" => "fo", "fij" => "fj", "fin" => "fi", "fry" => "fy", "ful" => "ff", "gla" => "gd", "gle" => "ga", "glg" => "gl", "glv" => "gv", "grn" => "gn", "guj" => "gu", "hat" => "ht", "hau" => "ha", "heb" => "he", "her" => "hz", "hin" => "hi", "hmo" => "ho", "hrv" => "hr", "hun" => "hu", "ibo" => "ig", "ido" => "io", "iii" => "ii", "iku" => "iu", "ile" => "ie", "ina" => "ia", "ind" => "id", "ipk" => "ik", "ita" => "it", "jav" => "jv", "jpn" => "ja", "kal" => "kl", "kan" => "kn", "kas" => "ks", "kau" => "kr", "kaz" => "kk", "khm" => "km", "kik" => "ki", "kin" => "rw", "kir" => "ky", "kom" => "kv", "kon" => "kg", "kor" => "ko", "kua" => "kj", "kur" => "ku", "lao" => "lo", "lat" => "la", "lav" => "lv", "lim" => "li", "lin" => "ln", "lit" => "lt", "ltz" => "lb", "lub" => "lu", "lug" => "lg", "mah" => "mh", "mal" => "ml", "mar" => "mr", "mlg" => "mg", "mlt" => "mt", "mon" => "mn", "nau" => "na", "nav" => "nv", "nbl" => "nr", "nde" => "nd", "ndo" => "ng", "nep" => "ne", "nno" => "nn", "nob" => "nb", "nor" => "no", "nya" => "ny", "oci" => "oc", "oji" => "oj", "ori" => "or", "orm" => "om", "oss" => "os", "pan" => "pa", "pli" => "pi", "pol" => "pl", "por" => "pt", "pus" => "ps", "que" => "qu", "roh" => "rm", "run" => "rn", "rus" => "ru", "sag" => "sg", "san" => "sa", "sin" => "si", "slv" => "sl", "sme" => "se", "smo" => "sm", "sna" => "sn", "snd" => "sd", "som" => "so", "sot" => "st", "spa" => "es", "srd" => "sc", "srp" => "sr", "ssw" => "ss", "sun" => "su", "swa" => "sw", "swe" => "sv", "tah" => "ty", "tam" => "ta", "tat" => "tt", "tel" => "te", "tgk" => "tg", "tgl" => "tl", "tha" => "th", "tir" => "ti", "ton" => "to", "tsn" => "tn", "tso" => "ts", "tuk" => "tk", "tur" => "tr", "twi" => "tw", "uig" => "ug", "ukr" => "uk", "urd" => "ur", "uzb" => "uz", "ven" => "ve", "vie" => "vi", "vol" => "vo", "wln" => "wa", "wol" => "wo", "xho" => "xh", "yid" => "yi", "yor" => "yo", "zha" => "za", "zul" => "zu", "fra" => "fr");
    $str = strtolower($str);
    if (isset($langs3Chars[$str])) return $langs3Chars[$str];
    return false;
}
