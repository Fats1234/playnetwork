<?php
   require_once "dbconfig.php";
   require_once "HTML/Table.php";
   require_once "functions.php";

   include "header.php";
   
   $playdb = new mysqli($dbhost,$dbuser,$dbpass,$database);

   if(isset($_POST['restore'])){
      $deletedID=$_POST['deletedID'];
      $restoreMac=$_POST['macaddress'];
      //check to see if the mac address already exists in batch
      $query="SELECT play_macaddress FROM play_batch WHERE play_macaddress='$restoreMac' LIMIT 1";
      $result=$playdb->query($query);
      if($result->num_rows){
         echo "<font size=\"5\" color=\"red\">ERROR: Could not restore MAC Address because MAC Address \"$restoreMac\" already exists in current Batch!</font><br>";
      }else{
         //get deleted record information
         $query="SELECT play_macaddress,play_load_date,play_type,play_set_number FROM play_deleted_records WHERE play_deleted_id=$deletedID";
         $restoreResult=$playdb->query($query);
         $restoreRecord=$restoreResult->fetch_assoc();
         
         $macaddress=$restoreRecord['play_macaddress'];
         $loadDate=$restoreRecord['play_load_date'];
         $playType=$restoreRecord['play_type'];
         $setNum=$restoreRecord['play_set_number'];
         
         //restore deleted record into play_batch
         $query="INSERT INTO play_batch SET play_macaddress='$macaddress',play_load_date='$loadDate',play_type='$playType',play_set_number=$setNum,play_printed=1";
         if($result=$playdb->query($query)){
            //restore successful so we delete the record in deleted records
            $query="DELETE FROM play_deleted_records WHERE play_deleted_id=$deletedID";
            $playdb->query($query);
            
            echo "<font size=\"5\" color=\"green\">SUCCESSFULLY Restored MAC Address \"$restoreMac\"!</font><br>";
         }        
      }
   }
   
   echo genDeletedRecordsTable($playdb);

   function genDeletedRecordsTable($database){
      $query="SELECT play_deleted_id,play_macaddress,play_load_date,play_delete_date,play_type FROM play_deleted_records ORDER BY play_delete_date DESC LIMIT 100";
      $deletedRecords=$database->query($query);
      
      $attrs=array('border' => '1');
      $recordsTable = new HTML_TABLE($attrs);
      $recordsTable->setHeaderContents(0,0,"MAC Address");
      $recordsTable->setHeaderContents(0,1,"Delete Date");
      $recordsTable->setHeaderContents(0,2,"Player Load Date");
      $recordsTable->setHeaderContents(0,3,"Player Type");
      
      $row=1;
      while($record=$deletedRecords->fetch_assoc()){
         $recordsTable->setCellContents($row,0,$record['play_macaddress']);
         $recordsTable->setCellContents($row,1,substr($record['play_delete_date'],0,10));
         $recordsTable->setCellContents($row,2,substr($record['play_load_date'],0,10));
         $recordsTable->setCellContents($row,3,$record['play_type']);
         
         $restoreStr = startForm("restore.php","POST");
         $restoreStr .= genHidden("deletedID",$record['play_deleted_id']);
         $restoreStr .= genHidden("macaddress",$record['play_macaddress']);
         $restoreStr .= genButton("Restore Record","restore");
         $restoreStr .= endForm();
         $recordsTable->setCellContents($row,4,$restoreStr);
         
         $row++;
      }
      
      $attrs = array('width'=>'200px','height'=>'40px','align' => 'center');
      $recordsTable->updateColAttributes(0,$attrs);
      $recordsTable->updateColAttributes(1,$attrs);
      $recordsTable->updateColAttributes(2,$attrs);
      $recordsTable->updateColAttributes(3,$attrs);
      $recordsTable->updateColAttributes(4,$attrs);
      
      $altAttrs=array('class' => 'alt');
      $recordsTable->altRowAttributes(1,null,$altAttrs);
      
      return $recordsTable->toHTML();
   }
   
   include "footer.php";
?>