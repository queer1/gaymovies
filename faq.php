<?php

 // Copyright (c) 2006 by Durwood Gafford
 // 

 $gVersion = 1.0;

  print_head("Gay Movies List FAQ", "Durwood Gafford");

  $faq = new CFaq();
  $faq->read_faq("gaymovies.faq");

  $faq->print_toc();
  print("<br/>\n");
  $faq->print_qa();

  print_tail();
  exit(0);

function print_head($title, $author) {

  print "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n";
  print "<html>\n";
  print "<head>\n";
  //include "http://www.berzerker.net/BerZerker/meta.php?author=$author";
  print "<title>$title</title>\n";
  print "</head>\n";
  print "<body>\n";
  print "<div id=\"container\">\n";
  //include "http://www.berzerker.net/BerZerker/menu.php?logo";
  print "\n";
  print "<h1>$title</h1>\n";
  print "\n";
}

function print_tail() {
  print "<font face=\"Arial, Helvetica, sans-serif\" size=-2>\n";
  print "Copyright &#169; Durwood Gafford. All Rights Reserved.\n";
  print "</font>\n";
  print "</div>\n";
  print "</body>\n";
  print "</html>\n";
}

class CFaqEntry {
  public $q;
  public $a;
  public $idx;

  public function print_qlink() {
    print ("$this->idx. <a href=\"#Q$this->idx\">\n");
    print ("<b>$this->q</b>\n");
    print ("</a><br>\n");
    print ("\n");
  }

  public function print_question() {
    print "<font face=\"Arial, Helvetica, sans-serif\">\n";
    print "<a id=\"Q$this->idx\"></a> \n";
    print "<b>$this->idx.  $this->q</b>\n";
    print "</font>\n";
    print "<br>\n";
    print "\n";
  }

  public function print_answer() {
    print "<font face=\"Helvetica\" size=\"+0\"> \n";
    print "<p>\n";
    foreach ($this->a as $line) {
      chop($line);
      if ($line == "")
        print "</p><p>\n";
      print "$line\n";
    }
    print "</p>\n";
    print "</font>\n";
    print "<a href=\"#top\">(top)</a>\n";
    print "<br>\n";
    print "<br>\n";
    print "\n";
  }
}

class CFaq {

  public $entries;

  public function add_entry($question, $answer) {
    $idx = count($this->entries) + 1;
    $this->entries[$idx] = new CFaqEntry();
    $this->entries[$idx]->q = $question;
    $this->entries[$idx]->a = $answer;
    $this->entries[$idx]->idx = $idx;
  }

  public function print_toc() {
    foreach ($this->entries as $entry) {
      $entry->print_qlink();
    }
  }

  public function print_qa() {
    foreach ($this->entries as $entry) {
      $entry->print_question();
      $entry->print_answer();
    }
  }

  public function print_faq() {
    $this->print_toc();
    print ("<hr>\n");
    print ("<p\>\n");
    $this->print_qa();
  }

  public function read_faq($fname) {
    $fp = file($fname);
    $lineno = 0;
    foreach ($fp as $line) {
      $lineno++;
      $line = trim($line);
      if ($line === "Q:") {
        if (isset($q) && isset($a)) {
          $this->add_entry($q, $a);
        }
        $answer = false;
        $question = true;
        unset($q);
        unset($a);
      }
      else if ($line === "A:") {
        $answer = true;
        $question = false;
      }
      else {
        if ($question) {
          $q = $line;
        }
        else if ($answer) {
        $a[] = $line;
        }
        else {
          print ("Invalid FAQ, line $lineno\n");
          exit(0); 
        }
      }
    }
    if (isset($q) && isset($a)) {
      $this->add_entry($q, $a);
    }
  }
}

?>


