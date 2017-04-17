<!DOCTYPE html>
<html lang="en">
  <head>

    <!-- Load bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
  </head>
  <body>

    <div class="container">

      <div class="page-header">
        <h1>Your Kindle Highlights</h1>
      </div>

<?php

require '../config.inc.php';

// open up a connection to our MySQL database to start storing the books and highlights
$db = new PDO("mysql:host=".$CONFIG['mysql_host'].";dbname=".$CONFIG['mysql_database'], $CONFIG['mysql_username'], $CONFIG['mysql_password']);

// retrieve all books and highlights and display them
$query = "SELECT id, author, title, last_annotation, date_created FROM books ORDER BY last_annotation DESC";
$sth   = $db->query($query);

while($book = $sth->fetch(PDO::FETCH_OBJ))
{
?>
      <div class="page-header">
        <h3><?=$book->title?> <small><?=$book->author?></small></h3>
      </div>
      <p style="border-bottom: 1px solid #f0f0f0;">
        First seen: <?=$book->date_created?><br />
        Last highlight: <?=$book->last_annotation?><br /><br />
      </p>

<?php
  $book_id = $book->id;

  $query  = "SELECT id, date_created, location, highlighted_text, note FROM highlights WHERE book_id = :book_id";
  $sth_hl = $db->prepare($query);
  $sth_hl->bindParam(':book_id', $book_id);
  $sth_hl->execute();
  while($highlight = $sth_hl->fetch(PDO::FETCH_OBJ))
  {
    echo "<div class=\"well\">".$highlight->highlighted_text."</div>";
    echo "<b>Location:</b> ".$highlight->location." <br />";
    if(!empty($highlight->note) && $highlight->note != "none") {
      echo "<b>Note:</b> ".$highlight->note."<br />";
    }
    echo "</p>";
  }
}

?>
    </div>
  </body>
</html>
