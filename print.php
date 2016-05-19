<?php

   require_once "functions.php";
   require_once "dbconfig.php";
   require_once "HTML/Table.php";

   include "header.php";
   include "printheader.php";
   
   $playdb = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   if(isset($_POST['print'])){
      $records=explode("\n",$_POST['macaddress']);
      
      $nextSetNumber=$lastSet+1; //assign mac addresses to the next set
      
      //update database to mark addresses as printed
      foreach($records as $macaddress){
         $macaddress=preg_replace("/\s/","",$macaddress);
         if(!empty($macaddress)){
            $query="UPDATE play_batch SET play_printed=1,play_set_number=$nextSetNumber WHERE play_macaddress='$macaddress'";
            //echo $query;
            $result=$playdb->query($query);
         }
      }
      
      $macaddresses = preg_replace("/:/","",$_POST['macaddress']);//remove colons for printing
      
      //generate form and submit form via javascript;
      echo startForm("http://polyerp01/labels/create.php?label=maclabel","POST","macForm");
      echo genHidden("macaddress",$macaddresses);
      echo endForm();
      
      //auto submit form via javascript
      echo "<script type=\"text/javascript\">\n";
      echo "document.getElementById(\"macForm\").submit();\n";
      echo "</script>\n";
   }else{
      if(isset($_GET['set'])){
         if($currentSet == $lastSet){
             echo "<font size=\"5\">This is the Most Recent Set of </font><font size=\"5\" color=\"green\"><u><b>Printed</b></u></font><font size=\"5\"> Records (Set Number: $currentSet)</font><br>";
         }elseif($currentSet == $firstSet){
           echo "<font size=\"5\">This is the Oldest Set of </font><font size=\"5\" color=\"green\"><u><b>Printed</b></u></font><font size=\"5\"> Records (Set Number: $currentSet)</font><br>";
         }else{
            echo "<font size=\"5\" color=\"green\"><u><b>Printed</b></u></font><font size=\"5\"> Set number: $currentSet</font><br>";
         }
         echo genRecordsTable($playdb,$currentSet);
      }else{
         //import records
         //importFileToDatabase($playdb,"http://192.168.0.18/macaddress.txt");
         //archiveFile("http://192.168.0.18/cgi-bin/command.pl","macaddress.txt");
         importFileToDatabase($playdb,"http://192.168.0.16/macaddress.txt");
         archiveFile("http://192.168.0.16/cgi-bin/command.pl","macaddress.txt");
         
         //display New Records, display RMA records
         $unprintedTable = new HTML_Table();
         
         $currCol=0;
         $colAttrs = array('width'=>'20%','align'=>'left','valign'=>'top');
         
         $query="SELECT play_macaddress FROM play_batch WHERE play_printed=0 AND play_type='NEW' LIMIT 1";
         $newResults=$playdb->query($query);
         if($newResults->num_rows){
            $unprintedTable->setHeaderContents(0,$currCol,"NEW Player Records");
            $unprintedTable->setCellContents(1,$currCol,genRecordsTable($playdb,0,"NEW"));
            $unprintedTable->updateColAttributes($currCol,$colAttrs);
            $currCol++;
         }
         
         $query="SELECT play_macaddress FROM play_batch WHERE play_printed=0 AND play_type='RMA' LIMIT 1";
         $rmaResults=$playdb->query($query);
         if($rmaResults->num_rows){
            $unprintedTable->setHeaderContents(0,$currCol,"RMA Player Records");
            $unprintedTable->setCellContents(1,$currCol,genRecordsTable($playdb,0,"RMA"));
            $unprintedTable->updateColAttributes($currCol,$colAttrs);
            $currCol++;
         }
         
         if($newResults->num_rows==0 && $rmaResults->num_rows==0){
            echo "<font size=\"5\">There Are No Unprinted Records</font><br>";
            if($lastSet){
               echo "<font size=\"5\"><a href=\"print.php?set=$lastSet\">Click Here For The Most Recent Set Of Printed Records</a></font><br>";
            }
         }else{
            echo "<font size=\"5\">The Following </font><font size=\"5\" color=\"red\"><u><b>Unprinted</b></u></font><font size=\"5\"> Records Were Found:</font><br><br>";
            echo $unprintedTable->toHTML();
         }         
      }
   }
   
   function importFileToDatabase($database,$file){   
      $data=file_get_contents($file);
      
      if(!empty($data)){
         $records=explode("\n",$data);
         foreach($records as $record){
            $timestamp=date("Y-m-d-His");
            $type=substr($record,0,1);
            $macaddress=substr($record,1);
         
            if(strcmp($type,"N")==0){
               $type="NEW";
            }else{
               $type="RMA";
            }
         
            //check if record already exists
            $query="SELECT play_macaddress FROM play_batch WHERE play_macaddress='$macaddress'";
            //echo $query;
            $result=$database->query($query);
            list($dbMAC)=$result->fetch_row();
         
            //insert if record is not in batch
            if(strcmp($dbMAC,$macaddress)!=0){
               $query="INSERT INTO play_batch SET play_macaddress='$macaddress',play_load_date='$timestamp',play_type='$type'";
               //echo $query;
               $result=$database->query($query);
            }
         }      
      }
   }
   
   function archiveFile($archiveCGI,$file){
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL,$archiveCGI);
      curl_setopt($ch, CURLOPT_POST, 1);
      $postfields="command=archive&file=".urlencode($file);
      curl_setopt($ch, CURLOPT_POSTFIELDS,$postfields);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $server_output = curl_exec ($ch);
      curl_close ($ch);
   }
   
   function genRecordsTable($database,$setNum=0,$sysType=""){
      if($setNum==0){
         $query = "SELECT play_macaddress,play_load_date FROM play_batch WHERE play_set_number=$setNum AND play_type='$sysType' ORDER BY play_print_order ASC";
      }else{
         $query = "SElECT play_macaddress,play_load_date,play_type FROM play_batch WHERE play_set_number=$setNum ORDER BY play_print_order ASC";
      }
      $results = $database->query($query);
      
      $attrs=array('border' => '1');
      $recordsTable = new HTML_Table($attrs);
      
      $recordsTable->setHeaderContents(0,0,"MAC Address");
      $recordsTable->setHeaderContents(0,1,"Date Entered");
      if($setNum > 0){
         $recordsTable->setHeaderContents(0,2,"System Type");
      }
      
      $macaddresses="";
      
      $row=2;
      while($record = $results->fetch_assoc()){
         $macaddresses.=$record['play_macaddress']."\n";
         $recordsTable->setCellContents($row,0,$record['play_macaddress']);
         $recordsTable->setCellContents($row,1,$record['play_load_date']);
         if($setNum){
            $recordsTable->setCellContents($row,2,$record['play_type']);
         }
         $row++;
      }
      
      if($setNum==0){
         $macFormStr=startForm("print.php","POST","form",TRUE);
      }else{
         $macFormStr=startForm("http://polyerp01/labels/create.php?label=maclabel","POST","form",TRUE);
         $macaddresses=preg_replace("/:/","",$macaddresses);
      }
      
      $macFormStr.=genHidden("macaddress",$macaddresses).genButton("Print MAC Addresses","print").endForm();
      
      $recordsTable->setCellContents(1,0,$macFormStr);
      
      $attrs = array('width'=>'200px','align' => 'center');
      $recordsTable->updateColAttributes(0,$attrs);
      $recordsTable->updateColAttributes(1,$attrs);
      
      $altAttrs=array('class' => 'alt');
      $recordsTable->altRowAttributes(2,null,$altAttrs);
      
      $returnStr = "Number of Records: ".$results->num_rows."<br>";
      $returnStr .= $recordsTable->toHTML();
      
      return $returnStr;
      
   }
?>