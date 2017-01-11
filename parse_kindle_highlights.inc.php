<?php

function fix_weird_chars($str)
{
  $search = [                 // www.fileformat.info/info/unicode/<NUM>/ <NUM> = 2018
              "\xC2\xAB",     // « (U+00AB) in UTF-8
              "\xC2\xBB",     // » (U+00BB) in UTF-8
              "\xE2\x80\x98", // ‘ (U+2018) in UTF-8
              "\xE2\x80\x99", // ’ (U+2019) in UTF-8
              "\xE2\x80\x9A", // ‚ (U+201A) in UTF-8
              "\xE2\x80\x9B", // ‛ (U+201B) in UTF-8
              "\xE2\x80\x9C", // “ (U+201C) in UTF-8
              "\xE2\x80\x9D", // ” (U+201D) in UTF-8
              "\xE2\x80\x9E", // „ (U+201E) in UTF-8
              "\xE2\x80\x9F", // ‟ (U+201F) in UTF-8
              "\xE2\x80\xB9", // ‹ (U+2039) in UTF-8
              "\xE2\x80\xBA", // › (U+203A) in UTF-8
              "\xE2\x80\x93", // – (U+2013) in UTF-8
              "\xE2\x80\x94", // — (U+2014) in UTF-8
              "\xE2\x80\xA6"  // … (U+2026) in UTF-8
  ];

  $replacements = [
              "<<",
              ">>",
              "'",
              "'",
              "'",
              "'",
              '"',
              '"',
              '"',
              '"',
              "<",
              ">",
              "-",
              "-",
              "..."
  ];

  $str = str_replace($search, $replacements, $str);
  return trim($str);
}

use Sunra\PhpSimple\HtmlDomParser;

/**
 * This function reads the HTML output of a saved file from https://kindle.amazon.com/your_highlights and uses HtmlDomParser to
 * peruse through the highlight entries in there.
 * Then it inserts the books and highlights into the MySQL database and returns only the books (+info about that book) that
 * were different from the database records, so we only send the modified books back
 **/
function parse_kindle_highlights($file)
{
  global $CONFIG;

  // Create DOM from URL or file
  $html = HtmlDomParser::file_get_html($file);

  $books = [];

  // first, filter out the HTML noise around <div="allHighlightedBooks"> - our goods are in that central div
  // - this needs to be done in a foreach to return as an object, even though there's only one <div="allHighlightedBooks">
  foreach($html->find('#allHighlightedBooks') as $all_highlights)
  {
    // go through all <div>s inside the <div="allHighlightedBooks"> and make a distinction based on what class
    // the div holds as to what information it holds
    foreach($all_highlights->find('div') as $div)
    {
      // if the div contains the 'yourHighlightsHeader' class, the book information is in there (title, author)
      if(preg_match("/yourHighlightsHeader/", $div->class))
      {
        // save the books title & author!
        $book_title  = fix_weird_chars($div->find("span.title", 0)->children(0)->innertext);
        $book_author = fix_weird_chars($div->find("span.author", 0)->innertext);

        // the book id is the actual ID Amazon uses in their store as well, so it is searchable on the main website,
        // we'll use that to distinct the book in our own database
        $p = explode("_", $div->id);
        $book_id = $p[0];

        // save to global array
        $books[$book_id]['title']  = $book_title;
        $books[$book_id]['author'] = $book_author;
      } // emd if(preg_match("/yourHighlightsHeader/", $div->class))

      // if the div contains the 'lastHighlighted' class, the time of the last highlight/note is in there, save that!
      if(preg_match("/lastHighlighted/", $div->class))
      {
        // save the last time there was a highlight
        $last_highlight = $div->innertext;
        // strip the text that comes with the date, we just want the date, then transform the data into SQL format
        $last_highlight = str_replace("Last annotated on ", "", $last_highlight);
        $last_highlight = date("Y-m-d", strtotime($last_highlight));

        $books[$book_id]['last_annotation'] = $last_highlight;
      } // end if(preg_match("/lastHighlighted/", $div->class))

      // if the div contains the 'yourHighlight' class, the div contains the actual highlight text
      if(preg_match("/yourHighlight/", $div->class))
      {
        // save the highlight text, location coordinates in the book
        $highlight_text = fix_weird_chars($div->find("span.highlight", 0)->innertext);
        $location_coord = trim($div->find("a.readMore", 0)->href);
        // we now have link formed like "kindle://book?action=open&amp;asin=B00UCL92QA&amp;location=2521"
        // save the location coordinates stated after the 'location=' text
        $location_coord = substr($location_coord, strpos($location_coord, "location=") + 9);

        // this is optional, when you create a note in a Kindle book, it'll create a regular highlight, but with this field filled.
        // regular highlights leave this html field empty
        $highlight_note = fix_weird_chars($div->find("span.noteContent", 0)->innertext);

        // use the Amazon highlight ID (which they use to delete/edit the thing), in our database for distinction
        $highlight_id   = $div->find('input[id=annotation_id]', 0)->value;

        // only save highlight if it actually exists of text
        if(!empty($highlight_text))
        {
          $highlight = [];
          $highlight['location'] = $location_coord;
          $highlight['text'] = $highlight_text;
          if(!empty($highlight_note)) $highlight['note']    = $highlight_note;

          $books[$book_id]['highlights'][$highlight_id] = $highlight;
        }
      } // end if(preg_match("/yourHighlight/", $div->class))
    } // end foreach($all_highlights->find('div') as $div)
  } // end foreach($html->find('#allHighlightedBooks') as $all_highlights)


  $books_modified = [];

  // open up a connection to our MySQL database to start storing the books and highlights
  $db = new PDO("mysql:host=".$CONFIG['mysql_host'].";dbname=".$CONFIG['mysql_database'], $CONFIG['mysql_username'], $CONFIG['mysql_password']);

  // go through each found book
  foreach($books as $book_id => $book_info)
  {
    // insert the book first, build the MySQL query and execute it
    $query = "INSERT INTO books (id, author, title, last_annotation, date_created) VALUES (:id, :author, :title, :last_annotation, NOW()) ON DUPLICATE KEY UPDATE last_annotation = :last_annotation";

    $sth   = $db->prepare($query);
    $sth->bindParam(':id', $book_id);
    $sth->bindParam(':author', $book_info['author']);
    $sth->bindParam(':title', $book_info['title']);
    $sth->bindParam(':last_annotation', $book_info['last_annotation']);
    $res = $sth->execute();
    if(!$res)
    {
        output("Query failed for book: ".$book_info['title']." (".$book_id.")", "fail");
        output("Error: ".$sth->errorInfo()[2], "fail");
        continue;
    }

    // if there's no rowCount() - it means that the book already existed in the database
    if($sth->rowCount())
    {
      output("Inserted book: ".$book_id." - ".$book_info['title'], "success");
      // save it to give to the evernote processing farm
      $books_modified[$book_id] = $books[$book_id];
    }

    // highlights assemble!
    foreach($book_info['highlights'] as $highlight_id => $highlight_info)
    {
      // build and execute the query to insert the highights
      $query = "INSERT IGNORE INTO highlights (id, book_id, date_created, location, highlighted_text, note) VALUES (:id, :book_id, NOW(), :location, :highlighted_text, :note)";
      $sth   = $db->prepare($query);
      $sth->bindParam(':id', $highlight_id);
      $sth->bindParam(':book_id', $book_id);
      $sth->bindParam(':location', $highlight_info['location']);
      $sth->bindParam(':highlighted_text', $highlight_info['text']);
      $sth->bindParam(':note', empty($highlight_info['note']) ? 'none' : $highlight_info['note']);

      $res = $sth->execute();
      if(!$res)
      {
          output("Query failed to insert highlight for book: ".$book_info['title']." (".$book_id.")", "fail");
          output("Error: ".$sth->errorInfo()[2], "fail");
          continue;
      }

      // if there's no rowCount() - it means that this highlight already existed
      if($sth->rowCount())
      {
        output("Inserted highlight for book: ".$book_info['title']." (".$book_id."): ".$highlight_info['text'], "success");
        // save it to give to the evernote processing farm
        $books_modified[$book_id] = $books[$book_id];
      }
    }
  }

  return $books_modified;
}

?>
