<?php
/*--------------------------------\
  | loadURL : Loads a remote URL using a socket, returns an array with header and content
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
-*/

if (!defined('PRESCIA_LOADURL_MAX_BYTES')) define('PRESCIA_LOADURL_MAX_BYTES', 2097152);

/** Return true only for globally routable IP addresses. */
function presciaLoadUrlIsPublicIp(string $ip): bool {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

/** @return list<string> */
function presciaLoadUrlResolvePublicIps(string $host): array {
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return presciaLoadUrlIsPublicIp($host) ? array($host) : array();
    }

    $ips = array();
    $records = function_exists('dns_get_record') ? @dns_get_record($host, DNS_A | DNS_AAAA) : false;
    if (is_array($records)) {
        foreach ($records as $record) {
            $ip = isset($record['ip']) ? $record['ip'] : (isset($record['ipv6']) ? $record['ipv6'] : '');
            if ($ip !== '' && !presciaLoadUrlIsPublicIp($ip)) return array();
            if ($ip !== '') $ips[] = $ip;
        }
    }
    if (!$ips) {
        $legacyIps = @gethostbynamel($host);
        if (!is_array($legacyIps)) return array();
        foreach ($legacyIps as $ip) {
            if (!presciaLoadUrlIsPublicIp($ip)) return array();
            $ips[] = $ip;
        }
    }
    return array_values(array_unique($ips));
}

/**
 * Load a remote HTTP(S) URL without following redirects.
 * Every destination is validated independently to prevent SSRF and rebinding.
 *
 * @return array{0: list<string>, 1: string}|false
 */
function loadURL($url, $agent = 'PHP', $method = 'get') {
    $url = is_string($url) ? trim($url) : '';
    if ($url === '' || strpbrk($url, "\r\n") !== false) return false;
    $parts = $url !== '' ? parse_url($url) : false;
    $scheme = is_array($parts) && isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
    $host = is_array($parts) && isset($parts['host']) ? strtolower($parts['host']) : '';
    $method = strtolower((string) $method);
    if (!is_array($parts) || !in_array($scheme, array('http', 'https'), true) || $host === '' ||
        isset($parts['user']) || isset($parts['pass']) || !in_array($method, array('get', 'post'), true)) return false;

    $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
    if (($scheme === 'https' && $port !== 443) || ($scheme === 'http' && $port !== 80)) return false;
    $ips = presciaLoadUrlResolvePublicIps($host);
    if (!$ips) return false;

    $path = isset($parts['path']) && $parts['path'] !== '' ? $parts['path'] : '/';
    $query = isset($parts['query']) ? $parts['query'] : '';
    if ($method === 'get' && $query !== '') $path .= '?' . $query;
    $connectHost = filter_var($ips[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $ips[0] . ']' : $ips[0];
    $target = ($scheme === 'https' ? 'ssl://' : '') . $connectHost;
    $context = stream_context_create(array('ssl' => array(
        'verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host, 'SNI_enabled' => true,
    )));
    $fp = @stream_socket_client($target . ':' . $port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
    if (!is_resource($fp)) return false;
    stream_set_timeout($fp, 10);

    $safeAgent = preg_replace('/[\r\n]+/', ' ', (string) $agent);
    $request = ($method === 'post' && $query !== '' ? 'POST' : 'GET') . " $path HTTP/1.0\r\n";
    $request .= 'Host: ' . $host . "\r\nAccept: text/*\r\nUser-Agent: " . $safeAgent . "\r\nConnection: Close\r\n";
    if ($method === 'post' && $query !== '') {
        $request .= "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($query) . "\r\n\r\n" . $query;
    } else {
        $request .= "\r\n";
    }
    if (@fwrite($fp, $request) === false) { fclose($fp); return false; }

    $response = '';
    while (!feof($fp) && strlen($response) <= PRESCIA_LOADURL_MAX_BYTES + 65536) {
        $chunk = fgets($fp, 8192);
        if ($chunk === false) break;
        $response .= $chunk;
    }
    fclose($fp);
    if (strlen($response) > PRESCIA_LOADURL_MAX_BYTES + 65536) return false;
    $parts = explode("\r\n\r\n", $response, 2);
    $body = isset($parts[1]) ? $parts[1] : '';
    if (strlen($body) > PRESCIA_LOADURL_MAX_BYTES) return false;
    return array(explode("\r\n", $parts[0]), $body);
}

function fget($url,$login,$pass,$file,$tries=1,$tmpfile="",$mode=FTP_ASCII) {
    if ($tmpfile == "") $tmpfile = "tmpdlw.tmp";
    if (is_file($tmpfile)) @unlink($tmpfile);
    while ($tries>0) {
        $fp = ftp_connect($url);
        if ($fp) {
            $login = ftp_login($fp, $login, $pass);
            if ($login) {
                ftp_pasv($fp,true); $handle = fopen($tmpfile, 'w');
                $sucess = ftp_fget($fp,$handle,$file,$mode);
                if ($sucess) { ftp_close($fp); fclose($handle); return cReadFile($tmpfile); }
                fclose($handle); if (is_file($tmpfile)) @unlink($tmpfile);
            }
            ftp_close($fp); unset($fp); $tries--; if ($tries>0) sleep(1);
        }
    }
    return false;
}

function getYoutubeViews($code,$simple=true) {
    $html = loadURL('https://www.youtube.com/watch?v='.$code);
    if ($html !== false) {
        $html = $html[1]; $haswvc = strpos($html,'"watch-view-count"',5000);
        if ($haswvc > 0) {
            $initpos = strpos($html,">",$haswvc); $endpos = strpos($html,"<",$initpos);
            $return = array(str_replace(".","",str_replace(",","",substr($html,$initpos+1,$endpos-$initpos-1))),'');
            if (!$simple) {
                $haswvc = strpos($html,'"datePublished"',100);
                if ($haswvc > 0) { $initpos = strpos($html,"content=",$haswvc)+9; $endpos = strpos($html,'"',$initpos); $return[1] = substr($html,$initpos,$endpos-$initpos); if (strlen($return[1])>10) $return[1] = ""; }
                else return "getYoutubeviews: publish date not found";
            }
            return $return;
        }
        return "getYoutubeviews:counter not found";
    }
    return "getYoutubeviews:loadURL fail";
}
?>
