<html><head><meta http-equiv="Content-type" content="text/html;charset=UTF-8">
<!-- This page displays the left sidebar of Sage 3 -->
<link rel="stylesheet" type="text/css" href="sage3list.css" />
<script lang="javascript">
control="";
function markAsRead() {
  control.href=control.href.replace("updaterss", "viewcachedrss");
  control.className = "read";
}
</script>
</head><body>
<table>
<tr><td class="table-header">
Sage-Three
<hr/>
<a href="javascript:location.reload(true)"><img src="refresh.png" border="0" /></a>
<hr/>
</td></tr>
<tr><td class="sageentries">
<?php
flush();
ob_flush();
require('getrss.inc');
$IN = fopen('sage3db.txt','r+');
$fpos = ftell($IN);
while($line = fgets($IN)) {
  # Skip comments and blank lines.
  if(strlen($line) < 10 || substr_compare($line, '#', 0, 1) == 0) {
    $fpos = ftell($IN);
    continue;
  }
  $line = explode(',', trim($line));
  # If the page has no known updates and it's past time to check...
  $updated = 2; # Error
  if($line[0] == '0') {
    if($line[1] <= date('ymdHi')) {
      # Read the page, to see if it's been updated.
      $updated = getrss($line[3]);
      if($updated == 1) {
        # Update the "updated" field.
        $fnewpos = ftell($IN);
        fseek($IN, $fpos);
        fwrite($IN, '1');
        fseek($IN, $fnewpos);
      }
    } else {
      $updated = 0; # Not updated yet.
    }
  } else {
    $updated = 1; # Updated, because the line said it was.
  }
  $urlend = '?url='.$line[3].'">'.$line[2].'</a>';
  if($updated == 1) {
    #echo "<p>Updated is ".$updated."</p>";
    echo '<a class="unread" target="rssfeed" onclick="control=this;setTimeout(markAsRead,100);window.parent.frames[\'rssfeed\'].focus();return true;" href="updaterss.php'.$urlend;
  } elseif(is_int($updated) && $updated == 0) {
    echo '<a class="read" target="rssfeed" onclick="window.parent.frames[\'rssfeed\'].focus();return true;" href="viewcachedrss.php'.$urlend;
  } else {
    echo '<a class="error" target="rssfeed" title="'.$updated.'" onclick="window.parent.frames[\'rssfeed\'].focus();return true;" href="viewcachedrss.php'.$urlend;
  }
  flush();
  ob_flush();

  $fpos = ftell($IN);
}
fclose($IN);
?>
</td></tr></table>
</body></html>
