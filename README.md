# Sync Amazon Kindle Highlights to MySQL Database

*A combination of Nodejs and PHP to download your Amazon Kindle Highlights, store them in MySQL and show them on a page. (It has no official name, if you have a suggestion, hit me up!)*

This application is a combination of Nodejs and PHP to synchronise your Amazon Kindle Highlights to a MySQL Database. You can highlight text and create notes inside your books on the Kindle (or Kindle app) from Amazon, but there's no way to easily get them out of Amazon. You can visit the [Your Highlights](https://kindle.amazon.com/your_highlights) page and copy/paste, but there's no public API to get a hold of your data.

You can use this application to programmatically get your highlights and notes from the [Your Highlights](https://kindle.amazon.com/your_highlights) page, put them in a database and show them on a page (where you can take them and copy/paste into your notes manager).

## Installation

Considering this is using a combo of Nodejs and PHP, we need to get some requirements out of the way.
- Install PHP Composer
```
cd composer/ && curl -sS https://getcomposer.org/installer | php
```
- Have Composer download the PHP libraries we need.
```
php composer.phar install
```
- Install Nodejs libraries we need:
```
cd ../ && npm install casperjs fs phantomjs system webpage
```


- Create a MySQL database, username & password on your MySQL server
- Create MySQL structure by loading db/books.sql and db/highlights.sql
- Copy config.inc.php.sample to config.inc.php and configure all options
- Make the public/ directory available via a webserver
- You will be redirected to Evernote to grant permission to your application you created by creating the Evernote token
- That's it! You can now run the app.
- After the first run, you should see the results on the website.

## Usage
If everything is properly set up, you can run the script by simply executing kindle_to_mysql.php

```
php kindle_to_mysql.php
```

When the first run is done, you can put the script into a scheduled task. Don't run it too often though, once a day is plenty. The big Amazon system is always watching and looking for automated bots on its website. If you run this too often, Amazon will start blocking your logins. If you're blocked, it'll reset in about an hour: it's not a permanent block. (phew!)

## Example Outputs
Here are a few example outputs for different scenarios, so you know what you can expect.

When you've got new books and highlights:

```
$ php kindle_to_evernote.php
[2017-01-11 22:41:13] Executing amazon_login.js first, to download the highlights page from Amazon..
All settings loaded, start with execution
Step 1 - Open Amazon home page
Step 2 - Click on the Sign in button
Step 3 - Populate and submit the login form
Step 4 - Wait Amazon to login user. After user is successfully logged in, user is redirected to home page.
Step 5 - Click on the Your Highlights link
Scrolling
...snip...
[2017-01-11 22:41:30]
[2017-01-11 22:41:30] Parsing 'output.html': storing the books and highlights in the database and checking which have changed..
[2017-01-11 22:41:30] Inserted book: B014PT1QYU - Only Humans Need Apply: Winners and Losers in the Age of Smart Machines
[2017-01-11 22:41:30] Inserted highlight for book: Only Humans Need Apply: Winners and Losers in the Age of Smart Machines (B014PT1QYU): the AI spring
[2017-01-11 22:41:30] Inserted book: B00AZRBLHO - The Phoenix Project: A Novel About IT, DevOps, and Helping Your Business Win
[2017-01-11 22:41:30] Inserted highlight for book: The Phoenix Project: A Novel About IT, DevOps, and Helping Your Business Win (B00AZRBLHO): Being able to take needless work out of the system is more important than being able to put more work into the system.
[2017-01-11 23:02:30] Found 2 books to have changed, pushing them to Evernote
[2017-01-11 22:41:30] Translating Notebook name 'Books' into a GUID..
[2017-01-11 22:41:35] Found GUID for Notebook 'Books' - 4dbc2ff3-64e9-4d7f-a0d7-22c60b101913
[2017-01-11 22:41:47] Processing book 'Only Humans Need Apply: Winners and Losers in the Age of Smart Machines'..
[2017-01-11 22:41:47] Checking whether we're creating a new note or updating an existing one..
[2017-01-11 22:41:48] Note found, updating the existing one!
[2017-01-11 22:41:50] Existing note updated! GUID: 142fa3fd-eace-47e6-83db-4cc8cad93a0e
[2017-01-11 22:41:53] Processing book 'The Phoenix Project: A Novel About IT, DevOps, and Helping Your Business Win'..
[2017-01-11 22:41:53] Checking whether we're creating a new note or updating an existing one..
[2017-01-11 22:41:54] Note not found, creating a new one!
$
```

When nothing has changed since the last run:

```
$ php kindle_to_evernote.php
[2017-01-11 23:42:09] Executing amazon_login.js first, to download the highlights page from Amazon..
[2017-01-11 23:42:26] All settings loaded, start with execution
Step 1 - Open Amazon home page
Step 2 - Click on the Sign in button
Step 3 - Populate and submit the login form
Step 4 - Wait Amazon to login user. After user is successfully logged in, user is redirected to home page.
Step 5 - Click on the Your Highlights link
Scrolling
...snip...
[2017-01-11 23:42:26] Parsing 'output.html': storing the books and highlights in the database and checking which have changed..
[2017-01-11 23:42:26] No books and/or highlights seem to have changed, so I'll stop now. Bye.
$
```

When you've created a new highlight for a book that was already in our database and notes:

```
$ php kindle_to_evernote.php
[2017-01-11 23:02:13] Executing amazon_login.js first, to download the highlights page from Amazon..
All settings loaded, start with execution
Step 1 - Open Amazon home page
Step 2 - Click on the Sign in button
Step 3 - Populate and submit the login form
Step 4 - Wait Amazon to login user. After user is successfully logged in, user is redirected to home page.
Step 5 - Click on the Your Highlights link
Scrolling
...snip...
[2017-01-11 23:02:30]
[2017-01-11 23:02:30] Parsing 'output.html': storing the books and highlights in the database and checking which have changed..
[2017-01-11 23:02:30] Inserted book: B00UCL92QA - Purity
[2017-01-11 23:02:30] Found 1 books to have changed, pushing them to Evernote
[2017-01-11 23:02:30] Translating Notebook name 'Books' into a GUID..
[2017-01-11 23:02:35] Found GUID for Notebook 'Books' - 4dbc2ff3-64e9-4d7f-a0d7-22c60b101913
[2017-01-11 23:02:35] Processing book 'Purity'..
[2017-01-11 23:02:35] Checking whether we're creating a new note or updating an existing one..
[2017-01-11 23:02:38] Note found, updating the existing one!
[2017-01-11 23:02:40] Existing note updated! GUID: 6c5f8d02-1fbd-4cd2-ab27-29657887e97e
$
```
