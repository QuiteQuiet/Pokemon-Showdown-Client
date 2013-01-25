<?php

// no direct linking to the lobby
if ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/lobby') {
	if (!empty($_SERVER['HTTP_REFERER']) && !preg_match('/^https?:\/\/([a-z0-9-]+.)?(pokemonshowdown\.com|appjs)/', $_SERVER['HTTP_REFERER'])) {
		header('HTTP/1.1 303 See Other'); 
		header('Location: http://pokemonshowdown.com/');
		die();
	}
}

// if (substr($_SERVER['REQUEST_URI'],0,8) === '/battle-') {
// 	$id = substr($_SERVER['REQUEST_URI'],8);
// }

// never cache
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$defaultserver = 'showdown';

include '../pokemonshowdown.com/config/servers.inc.php';

$server = @$_REQUEST['server'];
$serverport = 0;
$serverprotocol = '';
$serverid = '';
$usingdefaultserver = false;

$colonpos = strpos($server, ':');
$protocolpos = strrpos($server, '|');
$serverport = intval(@$_REQUEST['serverport']);

$localflag = false;
if ($protocolpos) {
	$serverprotocol = substr($server, $protocolpos+1);
	if (!strlen($serverprotocol)) $localflag = true;
	$server = substr($server, 0, $protocolpos);
}

if ($colonpos) {
	if (!$serverport) $serverport = substr($server, $colonpos+1);
	$server = substr($server, 0, $colonpos);
}

$serverdata = array();
if (@$PokemonServers[$server]) {
	$serverid = $server;
	$serverdata = $PokemonServers[$server];
	if (!$serverport) $serverport = $PokemonServers[$serverid]['port'];
	if (!$serverprotocol) $serverprotocol = $PokemonServers[$serverid]['protocol'];
	$server = $PokemonServers[$serverid]['server'];
	if ($serverport != $PokemonServers[$serverid]['port']) $serverid .= ':'.$serverport;
}
if ($localflag) $server = 'localhost';

if (!$server) {
	$server = $PokemonServers[$defaultserver]['server'];
	$serverport = $PokemonServers[$defaultserver]['port'];
	$serverprotocol = @$PokemonServers[$defaultserver]['protocol'];
	$serverid = $defaultserver;
}
else if (!$serverid) {
	$serverid = $server.($serverport?':'.$serverport:'');
}
if (!$serverport) {
	$serverport = 8000;
}
if ($serverprotocol !== 'io' && $serverprotocol !== 'eio') {
	$serverprotocol = 'ws';
}

$serverlibs = array(
	'io' => '/js/socket.io.js',
	'eio' => '/js/engine.io.js',
	'ws' => '/js/sockjs-0.3.min.js'
);
$serverlibrary = $serverlibs[$serverprotocol];

?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<title>Showdown!</title>
		<link rel="shortcut icon" href="/favicon.ico" id="dynamic-favicon" />
		<link rel="stylesheet" href="/style/sim.css?v0.7.15" />
		<link rel="stylesheet" href="/style/sim-types.css" />
		<link rel="stylesheet" href="/style/battle.css?v0.8b4" />
		<link rel="stylesheet" href="/style/replayer.css" /><!-- utilichart -->
		<link rel="stylesheet" href="/style/font-awesome.css" />
<?php if (@$serverdata['customcss']) { ?>
		<link rel="stylesheet" href="<?php echo $serverdata['customcss'] ?>" />
<?php } ?>
		<meta id="viewport" name="viewport" content="width=640"/>
		<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1" />
	<script>
		var Config = {
			server: '<?php echo $server ?>',
			serverid: '<?php echo $serverid ?>',
			serverport: <?php echo $serverport ?>,
			serverprotocol: '<?php echo $serverprotocol ?>',
			urlPrefix: '<?php if ($serverid && $serverid !== 'showdown') echo '~~',$serverid,'/'; ?>'
		};
<?php if (false) { ?>
		alert('We should be open to the public again in a few minutes.');
		Config.requirelogin = true;
<?php } ?>
		// if (!Config.urlPrefix) Config.down = true;
	</script>
	<!--[if lte IE 8]><script>
		Config.oldie = true;
	</script><![endif]-->
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-26211653-1']);
  _gaq.push(['_setDomainName', 'pokemonshowdown.com']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
	</head>
	<body>

 		<!-- Chrome Frame -->
		<!--[if lte IE 8]>
		<script src="http://ajax.googleapis.com/ajax/libs/chrome-frame/1/CFInstall.min.js"></script>
		<style>
		/* 
		CSS rules to use for styling the overlay:
		.chromeFrameOverlayContent
		.chromeFrameOverlayContent iframe
		.chromeFrameOverlayCloseBar
		.chromeFrameOverlayUnderlay
		*/
		</style> 
		<script>
			CFInstall.check({mode: "overlay", destination: "http://play.pokemonshowdown.com"});
		</script>
		<![endif]-->

		<div id="simheader">
			<h1 style="margin-top:2px;margin-right:10em;padding-top:0;"><img src="/pokemonshowdownbeta.png" alt="Pokemon Showdown! (beta)" /></h1>
		</div>
		<div id="leftbarbg"><div><span></span></div></div>
		<div id="leftbar">
			<div>
				<a id="tabtab-lobby" class="cur" href="#" onclick="return selectTab('lobby');">Lobby</a>
			</div>
		</div>
		<div id="main">
			<div id="loading-message" style="padding:20px">Initializing... <noscript>Surprise, surprise, this requires JavaScript</noscript></div>
		</div>
		<div id="lobbychat" class="lobbychat"></div>
		<div id="userbar">
			<em>Connecting...</em>
		</div>
		<div id="backbutton">
			<div><button onclick="return selectTab('lobby');">&laquo; Lobby</button></div>
		</div>
		<div id="overlay" style="display:none"></div>
		<div id="tooltipwrapper"><div class="tooltipinner"></div></div>
		<div id="foehint"></div>
		<script>
			document.getElementById('loading-message').innerHTML += ' DONE<br />Loading libraries...';
		</script>
		<script src="/js/jquery-1.7.min.js"></script>
		<script src="/js/autoresize.jquery.min.js"></script>
		<script src="/js/jquery-cookie.js"></script>
		<script src="/js/jquery.json-2.3.min.js"></script>
		<script src="/js/soundmanager2.js?v0.6"></script>

		<script>
			document.getElementById('loading-message').innerHTML += ' DONE<br />Loading client...';
			document.getElementById('loading-message').innerHTML = 'If the client is taking a long time to load, try refreshing in a few minutes. If it still doesn\'t work, Pokemon Showdown may be down for maintenance. We apologize for the inconvenience.<br /><br />'+document.getElementById('loading-message').innerHTML;
		</script>

		<script src="/js/battledata.js?v0.8.1"></script>
		<script src="/data/pokedex-mini.js?v0.7.18.8"></script>
		<script src="/js/battle.js?v0.8b14"></script>
		<!--script src="http://'.$server.':'.$serverport.'/socket.io/socket.io.js"></script-->
		<script src="<?php echo $serverlibrary; ?>"></script>
		<script src="/js/teambuilder.js?v0.7.12"></script>
		<script src="/js/ladder.js?v0.8.2"></script>
		<script src="/js/sim.js?v0.8.2b"></script>

		<script>
			document.getElementById('loading-message').innerHTML += ' DONE<br />Connecting to login server...';
			if (Config.down) overlay('down');
		</script>
		
		<script src="/data/learnsets.js?v0.7.18.1"></script>
		
		<script src="/data/graphics.js?v0.7.16"></script>
		<script src="/data/pokedex.js?v0.7.18.8"></script>
		<script src="/data/formats-data.js?v0.8.0"></script>
		<script src="/data/moves.js?v0.7.10"></script>
		<script src="/data/items.js?v0.7rc"></script>
		<script src="/data/abilities.js?v0.7rc"></script>
		<script src="/data/formats.js?v0.7.12"></script>
		<script src="/data/typechart.js?v0.6rc"></script>
		
		<script src="/js/utilichart.js?v0.7rc"></script>
		
		<script src="/data/aliases.js?v0.7.18.8" async="async"></script>
		<script>
			updateResize();
			if (init) init();
		</script>
	</body>
</html>
