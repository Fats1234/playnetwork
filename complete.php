<?php

   require_once "dbconfig.php";
   require_once "HTML/Table.php";
   require_once "functions.php";

   include "header.php";
   
   $playdb = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   if(isset($_POST['complete'])){
      $sysType = $_POST['sysType'];
      $batchSize = $_POST['batchSize'];
      $reference = $_POST['reference'];
      
      $query = "SELECT play_load_date FROM play_batch WHERE play_type='$sysType' ORDER BY play_print_order LIMIT 1";
      //echo $query;
      $result = $playdb->query($query);
      list($startDate)=$result->fetch_row();
      
      $query = "INSERT INTO play_batch_history SET start_date='$startDate',reference='$reference',number_of_records=$batchSize";
      if($playdb->query($query)) $batchID=$playdb->insert_id;
      
      $query = "SELECT play_macaddress,play_type,play_load_date FROM play_batch WHERE play_type='$sysType' ORDER BY play_load_date, play_print_order LIMIT $batchSize";
      //echo $query;
      $batchResults=$playdb->query($query);
      $endDate="";
      while(list($macaddress,$type,$date)=$batchResults->fetch_row()){
         $query = "INSERT INTO play_archive SET play_macaddress='$macaddress',
                               play_type='$type',play_load_date='$date',play_batch_id=$batchID";
         //echo $query;
         $playdb->query($query);
         $endDate=$date;
      }

      $query="UPDATE play_batch_history SET end_date='$endDate' WHERE batch_id=$batchID";
      $playdb->query($query);
      
      $query = "DELETE FROM play_batch WHERE play_type='$sysType' ORDER BY play_print_order LIMIT $batchSize";
      $playdb->query($query);
      
      $query="SELECT play_macaddress FROM play_archive WHERE play_batch_id=$batchID";
      $batchResults=$playdb->query($query);
      $timestamp=date("Y-m-d-His");
      $fh=fopen("batch_history/batch$batchID-$timestamp.csv","wt");
      
      while($record=$batchResults->fetch_assoc()){
         $recStr=preg_replace("/:/","",$record['play_macaddress']);
         $recStr.="\n";
         fwrite($fh,$recStr);
      }
      fclose($fh);
      
      $query="UPDATE play_batch_history SET download_link='batch$batchID-$timestamp.csv' WHERE batch_id=$batchID";
      $playdb->query($query);
      
   }
   
   //archive RMA records that are older than 30 days
   //archiveStaleRecords($playdb,"RMA");
   
   $query = "SELECT COUNT(*) FROM play_batch WHERE play_type='NEW' AND play_printed=1";
   $result=$playdb->query($query);
   list($numNewSys)=$result->fetch_row();
   
   $table = new HTML_Table();
   $colAttrs = array('width'=>'20%','align'=>'left','valign'=>'top');
   
   if($numNewSys){
      $table->setHeaderContents(0,0,"Total Number of NEW Records:");
      $table->setHeaderContents(0,1,"$numNewSys");
      $table->setCellContents(2,0,"Enter Number of Systems in this Batch:");
      $table->setCellContents(2,1,genTextBox("batchSize",$numNewSys));
      $table->setCellContents(4,0,"Enter a Reference Number for this Batch:");
      $table->setCellContents(4,1,genTextBox("reference"));
      $table->setCellContents(6,0,genHidden("sysType","NEW"));
      $table->setCellContents(6,1,genButton("Complete Current Batch","complete"));
      $table->setCellContents(8,0,genBatchTable($playdb,"NEW"));
      $table->updateColAttributes($currCol,$colAttrs);

      echo startForm("complete.php","POST");
      echo $table->toHTML();
      echo endForm();
   }else{
      echo "<font size=\"5\">There Are No Completed Systems!</font><br>";
   }
   
   function genBatchTable($database,$sysType){
      
      $query="SELECT play_macaddress,play_load_date FROM play_batch WHERE play_type='$sysType' AND play_printed=1 ORDER BY play_print_order LIMIT 500";     
      $records=$database->query($query);
      
      $attrs=array('border' => '1');
      $recordsTable = new HTML_Table($attrs);
      $recordsTable->setHeaderContents(0,0,"MAC Address");
      $recordsTable->setHeaderContents(0,1,"Load Date");
      
      $row=1;
      
      while($record=$records->fetch_assoc()){
         $recordsTable->setCellContents($row,0,$record['play_macaddress']);
         $recordsTable->setCellContents($row,1,$record['play_load_date']);
         $row++;
      }
      
      $attrs = array('width'=>'150px','align' => 'center');
      $recordsTable->updateColAttributes(0,$attrs);
      $recordsTable->updateColAttributes(1,$attrs);
      
      return $recordsTable->toHTML();
      
   }
   
   //function to automatically archive records that are older than 30 days
   function archiveStaleRecords($database,$systemType){
      $query="SELECT play_print_order,play_macaddress,play_type,play_load_date FROM play_batch WHERE play_type='$systemType'";
      //echo $query;
      $results=$database->query($query);
      
      $today = new DateTime("now");
      while($record=$results->fetch_assoc()){
         $loadDate = new DateTime(substr($record['date_entered'],0,10));
         $interval = $today->diff($loadDate,TRUE);
            
         //archive record if it is older than 30 days
         if($interval->days >= 30){
            $query = "INSERT INTO play_archive SET play_record_macaddress='".$record['mac_address']."',".
                               "play_record_type='".$record['play_type']."',play_date_loaded='".$record['play_load_date']."'";
            $database->query($query);
            
            $query = "DELETE FROM serial_batch WHERE print_order=".$record['print_order'];
            $database->query($query);            
         }
      }
      
   }
   
   include "footer.php";
?>