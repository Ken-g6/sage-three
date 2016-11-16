<?php
// Try to load the locally cached file.
$filepath = "rss-cache/".md5(urldecode($_GET['url']));
if (file_exists($filepath)){
  header("Content-type: text/xml");
  $IN = fopen($filepath, 'r');
  fgets($IN);
  fgets($IN);
  fgets($IN);
  $loc = ftell($IN);
  fclose($IN);
  $xml = file_get_contents($filepath, false, NULL, $loc);
  #$xml2 = preg_replace("/<description>.*?<\/description>[^a-zA-Z0-9<>]*<content:encoded>(.*?)<\/content:encoded>/s", "<description>$1</description>", $xml);
  #$xml2 = preg_replace("/<description><!.*<\/description>.*<content:encoded>/Us", "<description>", $xml);
  $xml2 = preg_replace("/(<item[ >].*?)<description>.*?<\/description>(.*?)<content:encoded>(.*?)<\/content:encoded>/s", "$1<description>$3</description>$2", $xml);
  $xml2 = preg_replace("/<category>.*?<\/category>/s", "", $xml2);
  if($xml != $xml2) $xml2 = str_replace("</content:encoded>", "</description>", $xml2);
  if($xml2 == "") $xml2 = $xml;
  $xml2 = preg_replace("/(width|height)=[\"0-9]*/", "", $xml2);
  echo $xml2;
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
