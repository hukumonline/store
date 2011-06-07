<?php
class Pandamp_Lib_Formater
{
    static function get_date($tanggal) {
            $id = $tanggal;
            $id = substr($id,8,2).".".substr($id,5,2).".".substr($id,2,2)." ".substr($id,11,2).":".substr($id,14,2);
            return $id;
    }
    function url_exists($url) {
        $a_url = parse_url($url);
        if (!isset($a_url['port'])) $a_url['port'] = 80;
        $errno = 0;
        $errstr = '';
        $timeout = 5;
        if(isset($a_url['host']) && $a_url['host']!=gethostbyname($a_url['host'])){
            $fid = @fsockopen($a_url['host'], $a_url['port'], $errno, $errstr, $timeout);
            if (!$fid) return false;
            $page = isset($a_url['path'])  ?$a_url['path']:'';
            $page .= isset($a_url['query'])?'?'.$a_url['query']:'';
            fputs($fid, 'HEAD '.$page.' HTTP/1.0'."\r\n".'Host: '.$a_url['host']."\r\n\r\n");
            $head = fread($fid, 4096);
            fclose($fid);
            return preg_match('#^HTTP/.*\s+[200|302]+\s#i', $head);
        } else {
            return false;
        }
    }
    static function thumb_exists($thumbnail)
    {
        $pos = strpos($thumbnail,"://");
        if ($pos === false) {
                return file_exists($thumbnail);
        }
        else
        {
            return Pandamp_Lib_Formater::url_exists($thumbnail);
        }
    }
    static function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
        {
          $ip=$_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
          $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
          $ip=$_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
	
}