<?php
function get_web_page( $url, $headers ) {
    $res = array();
    $options = array( 
        CURLOPT_RETURNTRANSFER => true,     // return web page 
        CURLOPT_HTTPHEADER     => $headers, // do send certain headers
        CURLOPT_HEADER         => true,     // do return headers 
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects 
        CURLOPT_USERAGENT      => "php-curl", // who am i - used to be "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0"
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect 
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect 
        CURLOPT_TIMEOUT        => 120,      // timeout on response 
        CURLOPT_MAXREDIRS      => 20,       // stop after 20 redirects 
    ); 
    $ch      = curl_init( $url ); 
    curl_setopt_array( $ch, $options ); 
    $response = curl_exec( $ch ); 
    curl_close($ch);
    list($header, $body) = explode("\r\n\r\n", $response, 2);
    $header = explode("\r\n", $header);
    $res['body'] = $body;     
    $res['header'] = $header;
    return $res; 
}
# Remove things starting with $start and ending with $end from a string.
# Doesn't use a regex, so it's faster and guaranteed not to return '' (without reason).
function delete_block($string, $start, $end) {
  $string = explode($start, $string);
  for($i=1; $i < count($string); $i++) {
    $sp = strpos($string[$i], $end);
    if(!$sp) {
      # Start with no end.
      if($i < count($string)-1) {
        # If there was a second start after the first, just merge them.
        $string[$i+1] = $string[$i].$start.$string[$i+1];
        $string[$i] = '';
      }
      continue;
    }
    $string[$i] = substr($string[$i], $sp+strlen($end));
    # Get rid of excess newlines or spaces after a block.
    # Not necessary if everything non-alphanumeric gets deleted anyway.
    #$nextchar = substr($string[$i], 0, 1);
    #while($nextchar == ' ' || $nextchar == '  ' || $nextchar == "\r" || $nextchar == "\n") {
      #$string[$i] = substr($string[$i], 1);
      #$nextchar = substr($string[$i], 0, 1);
    #}
  }
  return implode($string);
}
# Gets rid of things that change in an RSS feed that don't matter.  For testing purposes only; the result is not valid RSS.
function nocolontags($string) {
  #$string = str_replace("\r",'', $string);
  $string = delete_block($string, '<lastBuildDate>', '</lastBuildDate>');
  $string = delete_block($string, '<updated>', '</updated>');
  $string = delete_block($string, '<!--', '-->');
  # Don't go by any of the text in the article at all - just date stamps and such.
  $string = delete_block($string, '<description>', '</description>');
  $string = delete_block($string, '<content:encoded>', '</content:encoded>');
  # Don't go by the title either if there's a date stamp.
  if(strpos($string, '<pubDate')) $string = delete_block($string, '<title>', '</title>');
  #if($string == "") return("");
  # Do not delete <content:encoded> sections.
  #$string = str_replace('content:encoded','contentencoded', $string);
  # Delete any sections with <x:y></x:y>, not having other tags inside it.
  $string1 = preg_replace('/<([^> ]*:[^> ]*)[^>]*>[^<]*<\/\1>/s', '', $string); #[ \r\n]*
  if($string1 != "") { $string = $string1; } else { echo "<p>regex #1 error</p>"; }
  #if(strpos($string, '<enclosure ')) {
    #$string1 = preg_replace('/<enclosure(  *[a-z]*="")* *\/>[ \r\n]*/s','',$string);
    #if($string1 != "") { $string = $string1; } else { echo "<p>regex #2 error</p>"; }
  #}
  #$string = delete_block($string, "gd:etag=\"", '"');
  # Delete feed ads.
  $string = delete_block($string, 'href="http://feedads.', '"');
  # Get rid of all tags.
  $string = delete_block($string, '<', '>');
  # Get rid of all entities.
  $string = delete_block($string, '&', ';');
  # Get rid of anything not alphanumeric.
  $string1 = preg_replace('/[^a-zA-Z0-9]/s','',$string);
  if($string1 != "") { $string = $string1; } #else { echo "<p>regex #2 error</p>"; }
  # Just to make diffing easier for debugging, add newlines after e's.
  $string = str_replace("e","e\n", $string);
  return $string;
}
function getrss($url) {
  if(strlen($url) < 10 || (substr_compare($url, 'http://', 0, 7) != 0 && substr_compare($url, 'https://', 0, 8) != 0)) return(0);
  $cachefile = 'rss-cache/'.md5($url);
  if(file_exists($cachefile)) {
    $INC = fopen($cachefile,"r");
    $last_modified = trim(fgets($INC));
    $etag = trim(fgets($INC));
    $oldbodysum = trim(fgets($INC));
    fclose($INC);
  } else {
    $last_modified = "";
    $etag = "";
    $oldbodysum = "";
    $bodypos = 0;
  }

  # Get the page from the server.
  $headers = Array("Connection: close");
  if($last_modified != "") $headers[] = "If-Modified-Since: $last_modified";
  if($etag != "") $headers[] = "If-None-Match: $etag";
  $dlurl = $url;
  for($i=0; $i < 10; $i++) {
    $newbody = @file_get_contents($dlurl, false, stream_context_create (array ('http'=>array ('method'=>'GET' , 'header'=>implode("\r\n", $headers)."\r\n"))));
    $newbody = substr($newbody, strpos($newbody, '<'));
    $header = $http_response_header;
    #$newbody = get_web_page($dlurl, $headers);
    #$header = $newbody['header'];
    #$newbody = $newbody['body'];
    if(strlen($header[0]) < 12) return("Invalid header ".$header[0]);
    # If the page looks like a redirect...
    if(substr_compare($header[0], "30", 9, 2) == 0) {
      if(substr_compare($header[0], "304", 9, 3) == 0) { break; }
      else {
        # Find the location:
        foreach($header as $headline) {
          $headline = trim($headline);
          if(strlen($headline) > 10 && substr_compare($headline, 'Location: ', 0, 10) == 0) {
            $dlurl = substr($headline, 10);
            break;
          }
        }
        continue;
      }
    } else break;
  }

  # If the page was refreshed,
  if(substr_compare($header[0], "200", 9, 3) == 0) {
    # Get the required headers.
    $new_last_modified = "";
    $new_etag = "";
    foreach($header as $headline) {
      $headline = trim($headline);
      if(strlen($headline) > 15 && substr_compare($headline, 'Last-Modified: ', 0, 15) == 0) {
        $new_last_modified = trim(substr($headline, 15));
      } elseif(strlen($headline) >= 6 && substr_compare($headline, 'ETag: ', 0, 6) == 0) {
        $new_etag = trim(substr($headline, 6));
      }
    }
    # If both required headers are unchanged, assume nothing has changed.
    # (And that the server is stupid.)
    if($new_last_modified != "" && $new_last_modified == $last_modified &&
      $new_etag != "" && $new_etag == $etag) return 0;

    # OK, something was modified.  Let's check it out.
    $newbodync = nocolontags($newbody);
    $newbodysum = md5($newbodync);
    # Debugging to find page differences.
    #if($newbodysum != $oldbodysum) {
      #if(file_exists("$cachefile.bak")) unlink("$cachefile.bak");
      #rename($cachefile,"$cachefile.bak");
      #if(file_exists("$cachefile.nocolontags.bak")) unlink("$cachefile.nocolontags.bak");

      #if(file_exists("$cachefile.nocolontags")) rename("$cachefile.nocolontags","$cachefile.nocolontags.bak");
      #file_put_contents("$cachefile.nocolontags", $newbodync);
    #}
    # Save it,
    file_put_contents($cachefile, "$new_last_modified\n$new_etag\n$newbodysum\n$newbody");
    # Verify the page actually changed.
    if($newbodysum == $oldbodysum) {
      return 0;
      #} else {
      #echo "<p>$newbodysum != $oldbodysum</p>";
    }
    # And report updated.
    return 1;
  } elseif(substr_compare($header[0], "304", 9, 3) == 0) {
    # Else if the page is unmodified,
    # Do not save it,
    # And report not updated.
    return 0;
  } else {
    # Something went wrong!
    #echo "<p>Header was ".$header[0]."</p>";
    return $header[0];
  }
}
?>
