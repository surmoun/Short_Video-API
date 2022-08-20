<?php

class API {
         
    static public function findURL($url) {
                 

        preg_match('/([\w-]+\.)+\w+(\:\d{2,6})?/', $url, $domain);

        switch ($domain[0]) {
            case '':
                return self::result(500, '请传入解析url参数，例：http://www.123.com/?url=https://v.douyin.com/ehHpu7V/');
            break;
            case 'v.douyin.com':
                return self::douyin($url);
            break;
            case 'v.kuaishou.com':
                return self::kuaishou($url);
            break;
            default:
                return self::result(500, '抱歉，此url暂不支持！');
        }
    }
         
    static public function douyin($url) {
                 
        $url = self::httpRequest($url, 'GET');
        $url = $url['location'];

        // echo($url);
        //模拟苹果手机访问
        $UserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1';

        $d= 'https://www.douyin.com/';
        $e = '/';
        $flag =self::GetBetween($url,$d,$e) ;
        // echo($flag);
        if($flag=='video'){
        $b = 'https://www.douyin.com/video/';
        $c = '?p';
        $id = self::GetBetween($url,$b,$c);

        $vidoUrl = 'https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids='.$id;
        // echo($id);
        // echo($vidoUrl);
        $result = self::httpRequest($vidoUrl, 'GET');
        $vid = $result['response']['item_list'][0]['video']['play_addr']['uri'];
        if (isset($vid)) {
            $video_url = 'https://aweme.snssdk.com/aweme/v1/play/?video_id=' . $vid . '&ratio=720p&line=0';
             //获取重定向后的真实地址
            $video_url = self::get_redirect_url($video_url);
            $music = $result['response']['item_list'][0]['music']['play_url']['uri'];
            $nickname = $result['response']['item_list'][0]["share_info"]["share_title"];
             $type = "movie";
            $return = array('nickname' => $nickname, 'video_url' => $video_url, 'music' => $music,'type'=>$type);
            return self::result(200, $return);
        } else {
            return self::result(500, '解析出错！');
        }
            
        }
        else{
        $b = 'https://www.douyin.com/note/';
        $c = '?p';
        $id = self::GetBetween($url,$b,$c);
        
        $arr = json_decode(self::qqxz_http_get('https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids='. $id), true);
    // var_dump($arr['item_list'][0] ["images"][2]["url_list"][0]);
    // var_dump($arr['item_list'][0]["images"]);
        // echo(count($arr['item_list'][0]["images"]));

   
    // echo("<img src='$cover' type='images'");
        // var_dump($arr);
        if ($arr) {
            for($i=1;$i<count($arr['item_list'][0]["images"]);$i++){
                // echo($arr['item_list'][0] ["images"][$i]["url_list"][0]);
                $img[$i]=$arr['item_list'][0] ["images"][$i]["url_list"][0];
                // echo("<br>");
                }
            $url = $arr['item_list'][0]["video"]["play_addr"]["uri"];
            $title = $arr['item_list'][0]["share_info"]["share_title"];
            $cover = $arr['item_list'][0]['video']["origin_cover"]["url_list"][0];
    
        // var_dump($img);
            $img[0]=$cover;
            $type = "photo";
            $return = array('nickname' => $title, 'video_url' => $img, 'music' => $url,'type'=>$type);
            return self::result(200, $return);
        } else {
            return self::result(500, '解析出错！');
        }
        
        }
        
       
    }
         
     static public function kuaishou($url) {
         $locs = get_headers($url, true) ['Location'][1];
        // echo($locs);
        $d= 'video.kuaishou.com/';
        $e = '/';
        $flag =self::GetBetween($locs,$d,$e) ;
        
        // echo($flag);
        preg_match('/photoId=(.*?)\&/', $locs, $matches);
    //   var_dump($matches[1]);

        
        $json = self::get_ks_json($locs,$matches);
        
        //  var_dump($json['atlas']['list']);
        if($flag=='short-video'){
            for($i=0;$i<count($json['atlas']['list']);$i++){
         
                $img[$i] = 'https://p2.a.yximgs.com'.$json['atlas']['list'][$i];
            }
            
            $type = 'photo';
         
        }else{
            // var_dump($json['photo']['mainMvUrls'][0]['url']);
            $img = $json['photo']['mainMvUrls'][0]['url'];
            $type = 'movie';
        }
         
        //  var_dump( $json['shareInfo']['shareTitle']);
        //  var_dump( 'https://p2.a.yximgs.com'.$json['atlas']['music']);
        //  var_dump($img);
        
        if ($json) {

            $url = $img;
            $title = $json['shareInfo']['shareTitle'];
            $cover = 'https://p2.a.yximgs.com'.$json['atlas']['music'];
   

            $return = array('nickname' => $title, 'video_url' => $url, 'music' => $cover,'type'=>$type);
            // var_dump($return);
            return self::result(200, $return);
            
        } else {
            return self::result(500, '解析出错！');
        }
            
   
    }
         
    static public function httpRequest($url, $method = 'POST', $postfields = null, $headers = array()) {
                 
        $method = strtoupper($method);
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
            break;
            default:
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method);
            break;
        }
        $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($ssl) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ci, CURLOPT_MAXREDIRS, 2);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        $response = json_decode(curl_exec($ci), true);
        $requestinfo = curl_getinfo($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $location = curl_getinfo($ci, CURLINFO_EFFECTIVE_URL);
        curl_close($ci);
        return array('location' => $location, 'response' => $response, 'requestinfo' => $requestinfo);
    }
         
    static public function result($errno = 0, $data = '') {
                 
        header("Content-type: application/json;charset=utf-8");
        $errno = intval($errno);
        $result = array('code' => $errno, 'message' => $data);
        return json_encode($result, 320);
    }
    
   // 截取ID
    static public function GetBetween($content,$start,$end) {
            $r = explode($start, $content);
            if (isset($r[1])) {
            $r = explode($end, $r[1]);
            return $r[0];
        }
            return '';
        }
        
        
    static public function qqxz_http_get($url)
    {
        $Header=array( "User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1");
        $con=curl_init((string)$url);
        curl_setopt($con,CURLOPT_HEADER,False);
        curl_setopt($con,CURLOPT_SSL_VERIFYPEER,False);
        curl_setopt($con,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($con,CURLOPT_HTTPHEADER,$Header);
        curl_setopt($con,CURLOPT_TIMEOUT,5000);
        $result = curl_exec($con);
        return $result;
}

    static public function get_redirect_url($url) {
        $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1"));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($ch);
        curl_close($ch);
        preg_match("/Location: (.*?)\r\n/iU",$ret,$location);
        return $location[1];
}
  
 static public function get_ks_json($locs,$matches) {
        $headers = array('Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1','Cookie: did=web_9bceee20fa5d4a968535a27e538bf51b; didv=1655992503000;',
        'Referer: ' . $locs, 'Content-Type: application/json');
        $post_data = '{"photoId": "' . str_replace(['video/', '?'], '', $matches[1]) . '","isLongVideo": false}';
        $vurl ='https://v.m.chenzhongtech.com/rest/wd/photo/info?kpn=KUAISHOU&captchaToken=';
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $vurl);
        curl_setopt($curl, CURLOPT_NOBODY, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        $data = curl_exec($curl);
        
        
        curl_close($curl);
        
        return json_decode($data, true);
}
}
