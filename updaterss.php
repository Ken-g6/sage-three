<?php
require("getrss.inc");
// Get the URL
$geturl = urldecode($_GET['url']);
if(substr_compare($geturl, "http://", 0, 7) == 0) {
  // Mark this feed as updated.
  $IN = fopen("sage3db.txt","r+");
  $fpos = ftell($IN);
  $linefound = FALSE;
  while($line = fgets($IN)) {
    $line = trim($line);
    # Skip comments and blank lines.
    if(strlen($line) < 10 || substr_compare($line, "#", 0, 1) == 0) {
      $fpos = ftell($IN);
      continue;
    }
    $line = explode(",", $line);
    if($line[3] == $geturl) {
      // Get the feed from the URL
      // Save the feed to the md5 of the URL
      getrss($geturl);
      $line[0] = "0";
      $nextupdate = time();
      $line[1] = date("ymdHi", $nextupdate);
      fseek($IN, $fpos);
      fwrite($IN, $line[0].','.$line[1]);
      fclose($IN);
      $linefound = TRUE;
      break;
    }
    $fpos = ftell($IN);
  }
  if($linefound) {
    // Redirect to view the feed.
    header('Location: viewcachedrss.php?url='.urlencode($geturl)) ;
    // TODO: Mark the feed as updated in the other pane.
  } else {
    header('HTTP/1.0 403 Forbidden');
?>
<html><head><title>403 Forbidden</title></head>
<body><h1>403 Forbidden</h1>
<p>The URL parameter is not an allowed feed.</p>
</body></html>
<?php
  }
} else {
  header('HTTP/1.0 400 Bad Request');
?>
<html><head><title>400 Bad Request</title></head>
<body><h1>400 Bad Request</h1>
<p>The URL parameter is not valid.  There's nothing I can do to fix it.</p>
</body></html>
<?php
}
?>
