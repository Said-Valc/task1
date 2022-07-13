<?php
$file = 'C:\OpenServer\domains\task.test\access.log';
file_exists($file) or die('Файл не существует');

	$fileDescriptor = fopen($file, 'r');
	$_statistic = [
			'views' => 0,
			'urls' => 0,
			'traffic' => 0,
			'crawlers' => [
				'Google' => 0,
				'Bing' => 0,
				'Baidu' => 0,
				'Yandex' => 0,
			],
			'statusCodes' => [],
		];
		$_uniqueUrls = [];
	function textLine($fileDescriptor){
        if (feof($fileDescriptor)){
            return null;
        }
        $char = '';
        $line = '';
        while (!feof($fileDescriptor) && $char != "\n") {
            $char = fread($fileDescriptor, 1);
            $line .= $char;
        }
        return rtrim($line, "\n");
    }

    function logLineToArray($currentLogLine){
        $result = [];
        if($currentLogLine !== ''){
            $pattern = '/(\S+) (\S+) (\S+) \[(.+?)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) \"(.*?)\" \"(.*?)\"/';
            $logLineParameters = splitByPattern($pattern, $currentLogLine);
            if( count($logLineParameters) === 12 ){
                $result['ip']       = $logLineParameters[1];
                $result['identity'] = $logLineParameters[2];
                $result['user']     = $logLineParameters[3];
                $result['date']     = $logLineParameters[4];
                $result['method']   = $logLineParameters[5];
                $result['path']     = $logLineParameters[6];
                $result['protocol'] = $logLineParameters[7];
                $result['status']   = $logLineParameters[8];
                $result['bytes']    = $logLineParameters[9];
                $result['referer']  = $logLineParameters[10];
                $result['agent']    = $logLineParameters[11];
            }
        }

        return $result;
    }
	
	function addUrl(string $url){
        global $_uniqueUrls;
        global $_statistic;
        if( !in_array($url,$_uniqueUrls) ){
            $_uniqueUrls[] = $url;
            $_statistic['urls'] = count($_uniqueUrls);
        }
        return true;
    }
	
    function splitByPattern(string $pattern , string $subject){
        $patternIsMatchesSubject = preg_match ($pattern, $subject, $matches);
        return $patternIsMatchesSubject === 1 ? $matches : [];
    }

	while(isReachedEOF($fileDescriptor)){
		$currentLogEntry = logLineToArray(textLine($fileDescriptor));
		if(count($currentLogEntry) > 0){
			$_statistic['views'] +=1;
			addUrl($currentLogEntry['path']);
			$botName = botName($currentLogEntry['agent']);
			if( $botName !== null && isset($_statistic['crawlers'][$botName])) {
				$_statistic['crawlers'][$botName]++;
			}
			addStatusCode($currentLogEntry['status']);
			 if( (int)$currentLogEntry['status'] < 300 || (int)$currentLogEntry['status'] >= 400 ){
				addTraffic($currentLogEntry['bytes']);
			 }
		}
	}

    function addStatusCode(string $statusCode){
	  global $_statistic;
        if( $statusCode !== ''){
            if( isset($_statistic['statusCodes'][$statusCode]) ){
                $_statistic['statusCodes'][$statusCode]++;
            }else{
                $_statistic['statusCodes'][$statusCode] = 1;
            }
        }

        return true;
    }
	
	function addTraffic(int $traffic){
		global $_statistic;
        $_statistic['traffic'] += $traffic;
        return true;
    }

	function isReachedEOF($fileDescriptor): bool{

        $result = is_resource($fileDescriptor) ? feof($fileDescriptor) : true;
		$res = ($result) ? false: true;

        return (bool)$res;
    }
	
    function botName(string $user_agent){
         $_bots = [
        'Google' => [
            'Googlebot', 'Googlebot-Image', 'Mediapartners-Google', 'AdsBot-Google', 'APIs-Google',
            'AdsBot-Google-Mobile', 'AdsBot-Google-Mobile', 'Googlebot-News', 'Googlebot-Video',
            'AdsBot-Google-Mobile-Apps',
        ],
        'Yandex' => [
            'YandexBot', 'YandexAccessibilityBot', 'YandexMobileBot', 'YandexDirectDyn', 'YandexScreenshotBot',
            'YandexImages', 'YandexVideo', 'YandexVideoParser', 'YandexMedia', 'YandexBlogs', 'YandexFavicons',
            'YandexWebmaster', 'YandexPagechecker', 'YandexImageResizer', 'YandexAdNet', 'YandexDirect',
            'YaDirectFetcher', 'YandexCalendar', 'YandexSitelinks', 'YandexMetrika', 'YandexNews',
            'YandexNewslinks', 'YandexCatalog', 'YandexAntivirus', 'YandexMarket', 'YandexVertis',
            'YandexForDomain', 'YandexSpravBot', 'YandexSearchShop', 'YandexMedianaBot', 'YandexOntoDB',
            'YandexOntoDBAPI', 'YandexTurbo', 'YandexVerticals',
        ],
        'Bing' => [
            'bingbot',
        ],
        'Baidu' => [
            'Baiduspider',
        ],
        'Other' => [
            'Mail.RU_Bot', 'Accoona', 'ia_archiver', 'Ask Jeeves', 'OmniExplorer_Bot', 'W3C_Validator',
            'WebAlta', 'YahooFeedSeeker', 'Yahoo!', 'Ezooms', 'Tourlentabot', 'MJ12bot', 'AhrefsBot',
            'SearchBot', 'SiteStatus', 'Nigma.ru', 'Statsbot', 'SISTRIX', 'AcoonBot', 'findlinks',
            'proximic', 'OpenindexSpider', 'statdom.ru', 'Exabot', 'Spider', 'SeznamBot', 'oBot', 'C-T bot',
            'Updownerbot', 'Snoopy', 'heritrix', 'Yeti', 'DomainVader', 'DCPbot', 'PaperLiBot', 'StackRambler',
            'msnbot', 'msnbot-media', 'msnbot-news',
        ],
    ];
        if($user_agent === ''){
            return null;
        }

        foreach ($_bots as $botName => $botKeys) {
            foreach ($botKeys as $botKey) {
                if (strrposition($user_agent, $botKey) !== null) {
                    return $botName;
                }
            }
        }
        return null;
    }
	
	function strrposition (string $string , string $needle, int $offset = null): ?int{   
        $result = is_null($offset) ? strrpos($string, $needle) : strrpos($string, $needle, $offset);
        return $result === false ? null : $result;
    }

	echo json_encode($_statistic);
?>