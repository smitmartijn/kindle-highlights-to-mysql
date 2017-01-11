<?php



function push_book_notes_to_evernote($books)
{
  global $CONFIG;

  //set this to false to use in production
  $SANDBOX       = $CONFIG['evernote_sandbox'];
  $TOKEN         = $CONFIG['evernote_user_token'];
  $NOTEBOOK_NAME = $CONFIG['evernote_notebook_name'];

  // yes, we have to define the fact that we're not in china. The module will redirect API traffic to a chinese URL if this is true
  $china   = false;
  // instantiate a evernote client object
  $client = new \Evernote\Client($TOKEN, $SANDBOX, null, null, $china);

  // we need this for the search query, this does not do much for us
  $scope = \Evernote\Client::SEARCH_SCOPE_BUSINESS;
  $order = \Evernote\Client::SORT_ORDER_REVERSE | \Evernote\Client::SORT_ORDER_RECENTLY_CREATED;
  $maxResult = 1;


  output("Translating Notebook name '".$NOTEBOOK_NAME."' into a GUID..");
  $save_to_notebook = new \Evernote\Model\Notebook();
  $notebook_guid = 0;
  $notebooks = $client->listNotebooks();
  foreach ($notebooks as $notebook)
  {
      if($notebook->name == $NOTEBOOK_NAME)
      {
        $notebook_guid = $notebook->guid;
        $save_to_notebook->guid = $notebook->guid;
        output("Found GUID for Notebook '".$NOTEBOOK_NAME."' - ".$notebook->guid, "success");
      }
  }

  // we can't continue if we don't have the proper notebook GUID
  if(!$notebook_guid)
  {
    output("Could not find GUID for Notebook '".$NOTEBOOK_NAME."' - exiting!", "fail");
    exit;
  }

  // go through each book we need to push to Evernote
  foreach($books as $book_id => $book_info)
  {
    $book_title = $books[$book_id]['title'];

    output("Processing book '".$book_title."'..");

    // assemble the note content
    $note_txt  = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE en-note SYSTEM "http://xml.evernote.com/pub/enml2.dtd">';
    $note_txt .= "<en-note>";
    $note_txt .= "Author: ".$books[$book_id]['author']."<br />";
    $note_txt .= "Amazon ID: ".$book_id."<br />";
    $note_txt .= "Last Highlight: ".$books[$book_id]['last_annotation']."<br /><br /><br />";

    $note_txt .= "<b><font style=\"font-size: 24px;\">Highlights</font></b><br /><br />";

    // go through the highlights and put them into the note content
    foreach($book_info['highlights'] as $highlight_id => $highlight_info)
    {
      $note_txt .= $highlight_info['text']."<br />";
      $note_txt .= "<u>Location</u>: ".$highlight_info['location']."<br />";
      if(!empty($highlight_info['note']))
      {
        $note_txt .= "<b>Note</b><br />";
        $note_txt .= $highlight_info['note']."<br /><br />";
      }
      $note_txt .= "<hr /><br />";

    }
    $note_txt .= "</en-note>";

    // assemble a note object which contains the attribs we'd like to use in this note
    $note           = new \Evernote\Model\Note();
    $note->title    = $book_title;
    $note->content  = new \Evernote\Model\EnmlNoteContent($note_txt);
    $note->tagNames = array('kindle', 'amazon', 'book');

    // are we create a new note or updating an existing one? look for a note with the book title
    output("Checking whether we're creating a new note or updating an existing one..");
    $search = new \Evernote\Model\Search($book_title);
    $results = $client->findNotesWithSearch($search, $save_to_notebook, $scope, $order, $maxResult);
    if(empty($results))
    {
      output("Note not found, creating a new one!");

      try
      {
        $new_note = $client->uploadNote($note, $save_to_notebook);
      }
      catch(Exception $e)
      {
        output("Unable to create new note!", "fail");
        output("Error Code: ".$e->errorCode, "fail");
        output("Message: ".$e->parameter."\n\n".$e->message, "fail");
      }

      output("New note created! GUID: ".$new_note->guid, "success");
    }
    else
    {
      output("Note found, updating the existing one!");

      foreach ($results as $result)
        $existing_note = $client->getNote($result->guid);

      try {
        $new_note = $client->replaceNote($existing_note, $note);
      }
      catch(Exception $e)
      {
        output("Unable to update existing note!", "fail");
        output("Error Code: ".$e->errorCode, "fail");
        output("Message: ".$e->parameter."\n\n".$e->message, "fail");
      }

      output("Existing note updated! GUID: ".$new_note->guid, "success");
    }

  } // end foreach($books as $book_id => $book_info)

} // end function push_book_notes_to_evernote($books)


?>
