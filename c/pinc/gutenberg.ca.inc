<?php

error_reporting(E_ALL);

$gutFilename = 'gutenberg.ca.txt';
$gutURL = "http://www.gutenberg.ca/index.html";

//testGutenbergCA();

function testGutenbergCA()
{
    $results = searchGutenbergCA('chequer', 'shute');
    foreach ($results as $row)
        print_r($row);
}

function searchGutenbergCA($qtitle = '', $qauthor = '')
{
    $page = loadGUTCA();
    $t1 = microtime(TRUE);
    $rows = parseGUT($page);
    $t2 = microtime(TRUE);
    //print "Time: " . ($t2-$t1) . "\n";

    $results = array();
    foreach ($rows as $row) {
        $title = $row['title'];
        $author = $row['author'];
	// If neither title nor author is given, return everything
	if ($qtitle != '' || $qauthor != '')
	    // If either is given, then filter by either or both.
	    if (
		($qtitle == ''  || stripos($title, $qtitle) === FALSE)
	    &&  ($qauthor == '' || stripos($author, $qauthor) === FALSE)
	    )
		// Does not match criteria
		continue;
        $results[] = $row;
    }
    return $results;
}

function loadGUTCA()
{
    global $gutFilename;

    @$ft = filemtime($gutFilename);
    $t = gmdate('D, d M Y H:i:s \G\M\T', $ft);
    //echo "Conditional loading from website if mod after $t\n";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "http://www.gutenberg.ca/index.html");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FILETIME, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "If-Modified-Since: $t"
    ]);
    $result = curl_exec($curl);
    $info = curl_getinfo($curl);
    //print_r($info);
    curl_close($curl);
    if ($info['http_code'] == 304) {
        // 304 Not Modified
        return loadGUTCAfromFile($gutFilename);
    }

    $filetime = $info['filetime'];

    saveGUTCA($result, $gutFilename, $filetime);
    return $result;
}

function loadGUTCAfromFile($filename)
{
    $h = fopen($filename, 'r');
    if (!$h)
        return FALSE;
    //echo "Unchanged, reading from $filename\n";
    $page = fread($h, filesize($filename));
    fclose($h);
    return $page;
}

function saveGUTCA($page, $filename, $ft)
{
    //echo "Saving to $filename\n";
    $h = fopen($filename, 'w');
    fwrite($h, $page);
    fclose($h);
    touch($filename, $ft);
}

function parseGUT($result)
{
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($result);

    $byAuthor = array();
    $rows = array();
    foreach ($dom->getElementsByTagName('font') as $font) {
        if (!isAuthorName($font))
            continue;
        $child = $font->firstChild;
        $a = $child->nextSibling;
        if (!is_object($a)) {
            // Used to markup all names; ignore when in text
            echo "NOT AN OBJECT: $a";
            continue;
        }
        $author = trim($a->textContent);

        $books = array();
        getBooks($font->nextSibling, $books);
        //echo "AUTHOR: $author\n";
        //echo print_r($books, TRUE);

        foreach ($books as $book) {
            $title = $book['title'];
            $description = $book['description'];
            $directory = $book['directory'];

            // Some titles will be duplicates, since they are both
            // in the full list, and in the New Releases list.
            // byAuthor is keyed the the author, value is an array
            // keyed by the title.
            if (isset($byAuthor[$author])) {
                $directories = &$byAuthor[$author];
                if (isset($directories[$directory])) {
                    //echo "Ignoring: $author, $title\n";
                    continue;
                }
            } else {
                $byAuthor[$author] = array();
                $directories = &$byAuthor[$author];
            }
            $directories[$directory] = TRUE;

            $rows[] = array(
                'author' => $author,
                'title' => $title,
                'description' => $description,
                'directory' => $directory
            );
        }
    }
    return $rows;
}

/*
 * For now, if it is a <b><i>...</i></b>, we'll treat it as a title.
 */
function isTitle($n)
{
    return $n->nodeName === 'b'
        && $n->firstChild
        && $n->firstChild->nodeName == 'i';
}

/*
 * <font color="darkcyan"><big><b>...</b></big>
 */
function isAuthorName($n)
{
    if ($n->nodeName != 'font')
        return FALSE;
    $color = $n->getAttribute('color');
    if ($color != "darkcyan" && $color != 'crimson')
        return FALSE;
    foreach ($n->childNodes as $child)
        if ($child->nodeName === 'big')
            return TRUE;
    return FALSE;
}

/*
 * How to tell when we reach the end of the description?
 * Usually, it ends with <a href="ebooks/...">
 * but when it doesn't, it ends with a new title...
 */
function isNextBook($n)
{
    if (isAuthorName($n))
        return TRUE;
    $nn = $n->nodeName;
    if ($nn === 'h3')
        return TRUE;

    if ($nn == "small")
        return TRUE;

    if ($nn === 'br'
        && $n->nextSibling
        && $n->nextSibling->nodeName == 'br')
        return TRUE;
    return FALSE;
}

function getBooks($n, &$books)
{
    do {
        if (isTitle($n)) {
            $title = trim($n->textContent);
            // Everything until an href to /ebooks is part of the descr
            $description = '';
            $directory = '';
            while ($n = $n->nextSibling) {
                $nn = $n->nodeName;
                if ($nn == 'a') {
                    $href = $n->getAttribute('href');
                    if (substr($href, 0, 7) === 'ebooks/') {
                        $a = explode('/', $href);
                        $directory = $a[1];
                        break;
                    }
                }

                // Reached the next book without an href, so just stop!
                if (isNextBook($n) || isTitle($n))
                    break;
                $description .= $n->textContent;
            }
            $description = trim($description);

            // If there is no directory, presumably it is a link to PG-US
            // which we don't care about
            if ($directory === '')
                continue;

            $book = array();
            $book['title'] = $title;
            $book['description'] = $description;
            $book['directory'] = $directory;
            $books[] = $book;
            // print "Adding " . print_r($book, TRUE) . "\n";
        }

        if ($n->nodeName === 'font')
            /* End of this author */
            break;
        if ($n->nodeName === 'blockquote') {
            foreach ($n->childNodes as $child) {
                getBooks($child, $books);
            }
        }
    } while ($n = $n->nextSibling);
    return $books;
}
?>
