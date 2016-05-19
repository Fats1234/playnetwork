<?php
   require_once "dbconfig.php";
   require_once "HTML/Table.php";
   require_once "functions.php";

   include "header.php";
   
   $playdb = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   if(isset($_POST['submit'])){
      setImage("http://192.168.0.16/cgi-bin/command.pl",$_POST['image']);
      header("Location: select.php");
   }
   
   $attrs = array('border' => '1');
   $selectTable = new HTML_Table($attrs);
   
   $selectTable->setCellContents(0,0,"<font size=\"5\">Current Image On Server:</font>");
   $selectTable->setCellContents(0,1,"<font size=\"5\" color=\"green\">".getImage("http://192.168.0.16/cgi-bin/command.pl")."</font>");
   $selectTable->setCellContents(2,0,"<font size=\"5\">Playnetwork Image</font>");
   $selectTable->setCellContents(2,1,startForm("select.php","POST").genHidden("image","pnimage").genButton("Load Playnetwork Image").endForm());
   $selectTable->setCellContents(3,0,"<font size=\"5\">mc500 BIOS</font>");
   $selectTable->setCellContents(3,1,startForm("select.php","POST").genHidden("image","mc500").genButton("Load mc500 BIOS").endForm());
   $selectTable->setCellContents(4,0,"<font size=\"5\">mc550NEW BIOS</font>");
   $selectTable->setCellContents(4,1,startForm("select.php","POST").genHidden("image","mc550NEW").genButton("Load mc550NEW BIOS").endForm());
   $selectTable->setCellContents(5,0,"<font size=\"5\">PN2600 BIOS</font>");
   $selectTable->setCellContents(5,1,startForm("select.php","POST").genHidden("image","PN2600").genButton("Load PN2600 BIOS").endForm());
   
   $attrs = array('width'=>'300px');
   $selectTable->updateColAttributes(0,$attrs);
   $selectTable->updateColAttributes(1,$attrs);
   
   echo "<font size=\"6\">Choose The Image To Load</font><br>";
   echo $selectTable->toHTML();
      
   function setImage($commandCGI,$image){
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL,$commandCGI);
      curl_setopt($ch, CURLOPT_POST, 1);
      $postfields="command=load&file=".urlencode($image);
      curl_setopt($ch, CURLOPT_POSTFIELDS,$postfields);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $server_output = curl_exec ($ch);
      curl_close ($ch);
   }
   
   function getImage($commandCGI){
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL,$commandCGI);
      curl_setopt($ch, CURLOPT_POST, 1);
      $postfields="command=getimage";
      curl_setopt($ch, CURLOPT_POSTFIELDS,$postfields);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $server_output = curl_exec ($ch);
      curl_close ($ch);
      
      return $server_output;
   }
?>