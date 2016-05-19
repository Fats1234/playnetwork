<?php
   require_once "dbconfig.php";
   require_once "HTML/Table.php";
   require_once "functions.php";
   
   include "header.php";
   
   $playdb = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   if(isset($_POST['modify'])){
      if(!empty($_POST['reference'])){
         $query="UPDATE play_batch_history SET reference='".$_POST['reference']."' WHERE batch_id=".$_POST['batchID'];
         //echo $query;
         $playdb->query($query);
      }
      
      //update start date
      $query="SELECT play_load_date FROM play_archive WHERE play_batch_id=".$_POST['batchID'].
               " ORDER BY play_load_date LIMIT 1";
      $result=$playdb->query($query);
      list($startDate)=$result->fetch_row();
      $query="UPDATE play_batch_history SET start_date='$startDate' WHERE batch_id=".$_POST['batchID'];
      $playdb->query($query);
      
      //update end date
      $query="SELECT play_load_date FROM play_archive WHERE play_batch_id=".$_POST['batchID'].
               " ORDER BY play_load_date DESC LIMIT 1";
      $result=$playdb->query($query);
      list($endDate)=$result->fetch_row();
      $query="UPDATE play_batch_history SET end_date='$endDate' WHERE batch_id=".$_POST['batchID'];
      $playdb->query($query);
      
      //count number of records from archive table in case it has been modified
      $query="SELECT COUNT(*) FROM play_archive WHERE play_batch_id=".$_POST['batchID'];
      $result=$playdb->query($query);
      list($archiveNumRecords)=$result->fetch_row();
      
      //get the current number of records from the batch history table to compare
      $query="SELECT number_of_records FROM play_batch_history WHERE batch_id=".$_POST['batchID'];
      $result=$playdb->query($query);
      list($historyNumRecords)=$result->fetch_row();

      //if the 2 are not the same then we have to update the database and generate a new download link
      if($historyNumRecords != $archiveNumRecords){
         //update number_of_records in batch_history table
         $query="UPDATE play_batch_history SET number_of_records=$archiveNumRecords WHERE batch_id=".$_POST['batchID'];
         $playdb->query($query);
         
         //get the system type of the batch
         $query="SELECT play_type FROM play_archive WHERE play_batch_id=".$_POST['batchID']." LIMIT 1";
         $result=$playdb->query($query);
         list($sysType)=$result->fetch_row();
         
         //generate a new download link
         $query="SELECT play_macaddress FROM play_archive WHERE play_batch_id=".$_POST['batchID'];
         
         $batchResults=$playdb->query($query);
         $timestamp=date("Y-m-d-His");
         $fh=fopen("batch_history/batch".$_POST['batchID']."-$timestamp.csv","wt");
      
         while($record=$batchResults->fetch_assoc()){
            $recStr=preg_replace("/:/","",$record['play_macaddress'])."\n";
            fwrite($fh,$recStr);
         }
         fclose($fh);
      
         $query="UPDATE play_batch_history SET download_link='batch".$_POST['batchID']."-$timestamp.csv' WHERE batch_id=".$_POST['batchID'];
         $playdb->query($query);        
      }
   }
   
   $attrs=array('border' => '1');
   $batchTable = new HTML_Table($attrs);
   
   $batchTable->setHeaderContents(0,0,"Batch ID");
   $batchTable->setHeaderContents(0,1,"Number of Records");
   $batchTable->setHeaderContents(0,2,"Start Date");
   $batchTable->setHeaderContents(0,3,"End Date");
   $batchTable->setHeaderContents(0,4,"CSV Download Link");
   $batchTable->setHeaderContents(0,5,"Reference Note");
   $batchTable->setHeaderContents(0,6,"Modify Reference");
   
   $query="SELECT batch_id,start_date,end_date,reference,download_link,number_of_records 
                  FROM play_batch_history ORDER BY batch_id DESC";
   $batchRecords=$playdb->query($query);
   
   $row=1;
   while($record=$batchRecords->fetch_assoc()){
      $batchTable->setCellContents($row,0,$record['batch_id']);
      $batchTable->setCellContents($row,1,$record['number_of_records']);
      $batchTable->setCellContents($row,2,substr($record['start_date'],0,10));
      $batchTable->setCellContents($row,3,substr($record['end_date'],0,10));
      $batchTable->setCellContents($row,4,"<a href=\"batch_history/".$record['download_link']."\">Batch ".
                                          $record['batch_number_id']." CSV</a>");
      $batchTable->setCellContents($row,5,$record['reference']);
      $batchTable->setCellContents($row,6,startForm("history.php","POST").genTextBox("reference").
                                          genButton("Modify Reference","modify").genHidden("batchID",$record['batch_id']).
                                          endForm());
      $row++;
   }
   
   $attrs20 = array('width'=>'20%','align' => 'center');
   $attrs15 = array('width'=>'15%','align' => 'center');
   $attrs10 = array('width'=>'10%','align' => 'center');
   $batchTable->updateColAttributes(0,$attrs10);
   $batchTable->updateColAttributes(1,$attrs10);
   $batchTable->updateColAttributes(2,$attrs10);
   $batchTable->updateColAttributes(3,$attrs10);
   $batchTable->updateColAttributes(4,$attrs15);
   $batchTable->updateColAttributes(5,$attrs20);
   
   $altAttrs=array('class' => 'alt');
   $batchTable->altRowAttributes(1,null,$altAttrs);
   
   echo $batchTable->toHTML();
   
   include "footer.php";
?>