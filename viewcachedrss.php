<?php
require_once('rss_fetch.inc');
define('MAGPIE_INPUT_ENCODING', 'UTF-8');
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');
define('MAGPIE_CACHE_ON', false);

// Try to load the locally cached file.
$filepath = "rss-cache/".md5($_GET['url']);
if (file_exists($filepath)){
  $rss = fetch_rss('http://127.0.0.1'.str_replace("viewcachedrss.php", "readcachedrss.php", $_SERVER['REQUEST_URI']));
?>
<html><head><meta http-equiv="Content-type" content="text/html;charset=UTF-8">
<!-- This page displays an RSS feed for Sage 3 -->
<link rel="stylesheet" type="text/css" href="sage3feed.css" />
</head><body>
<div id="feedBody">
<div id="feedTitle">
<div id="feedTitleContainer">
<h1 id="feedTitleText"><?=$rss->channel['title']?></h1>
</div></div>
<div id="feedContent">
<?php
  $dns = $_GET['url'];
  if(strpos($dns, '/', 8)) {
    $dns = substr($dns, 0, strpos($dns, '/', 8));
  }
  foreach ($rss->items as $item) {
    $href = $item['link'];
    $title = $item['title'];
    $pubdate = '';
    if(isset($item['pubdate'])) $pubdate = $item['pubdate'];
    elseif(isset($item['published'])) $pubdate = $item['published'];
    //else print_r($item);
    // Delete duplicate dates: Difficult.
    $pubdate = preg_replace('/T[MTWFS][a-z][a-z], .*/', 'T', $pubdate);
    $dp = strtotime($pubdate,0);
    if(isset($dp) && $dp > 1000) $pubdate = date('m/d/Y h:i A', $dp);
    $author = null;
    if(isset($item['author'])) $author = $item['author'];
    elseif(isset($item['author_name'])) {
      $author = $item['author_name'];
      if(isset($item['author_uri'])) $author = '<a href="'.$item['author_uri']."\">$author</a>";
    }
    elseif(isset($item['dc']['creator'])) $author = $item['dc']['creator'];
    //else print_r($item);
    if(isset($author)) $pubdate = "$pubdate by $author";
    $description = $item['description'];
    $description = str_replace('src="//', 'src=http://', $description);
    $description = str_replace('src="/', 'src="'.$dns.'/', $description);
    $description = str_replace('href="//', 'href=http://', $description);
    $description = str_replace('href="/', 'href="'.$dns.'/', $description);
    if(isset($item['atom_content']) && strlen($item['atom_content']) > strlen($description)) $description = $item['atom_content'];
    echo "<div class=\"entry\"><h3><a target=\"blank\" href=$href>$title</a><div class=\"lastUpdated\">$pubdate</div></h3><div class=\"feedEntryContent\">$description</div></div><div style=\"clear: both;\"></div>";
  }
  echo "</div></div></body></html>";
} else {
  header('HTTP/1.0 404 Not Found');
?>
<html><head><title>RSS not found</title></head>
<body><h1>404 Not Found</h1>
<p>The RSS feed that you have requested could not be found.</p>
<p>You can <a href="updaterss.php?url=<?=$_GET['url']?>">try getting it</a>.</p>
</body></html>
<?php
}
?>
