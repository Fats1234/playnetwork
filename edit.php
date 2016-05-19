<?php

   require_once "dbconfig.php";
   require_once "HTML/Table.php";
   require_once "functions.php";
   
   include "header.php";
   include "editheader.php";
   
   $playdb = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   if(isset($_POST['delete'])){
      $timestamp=date("Y-m-d-His");
      $macaddress = $_POST['macaddress'];
      
      $query = "SELECT play_load_date,play_type,play_set_number FROM play_batch WHERE play_macaddress='$macaddress'";
      //echo $query;
      $result = $playdb->query($query);
      $record = $result->fetch_assoc();
      
      $query = "INSERT INTO play_deleted_records SET play_macaddress='$macaddress',play_load_date='".$record['play_load_date'].
                              "',play_delete_date='$timestamp',play_type='".$record['play_type']."',play_set_number=".$record['play_set_number'];
      
      //echo $query;
      if($playdb->query($query)){
         $query = "DELETE FROM play_batch WHERE play_macaddress='$macaddress'";
         $playdb->query($query);
         echo "<font size=\"5\" color=\"green\">SUCCESSFULLY Deleted MAC Address: $macaddress</font><br><br>";
      }      
   }
   
   if(isset($_POST['add'])){
      $timestamp=date("Y-m-d-His");
      $playerType = $_POST['type'];
      $printed = 0;
      $set=0;
      if(strcmp($_POST['print'],"Do Not Print")==0){
         $printed = 1;
         $set=$lastSet;
         if(!$lastSet){
            $set=1;
         }
      }else{
         $currentSet=0;
      }
      $macaddress = $_POST['macaddress'];
      //check to make sure that the mac address input is correctly formatted
      if (preg_match("/[0-9A-Fa-f]{12}/",$macaddress)){
         $macaddress=strtolower($macaddress);
         $formattedMAC=substr($macaddress,0,2).":".substr($macaddress,2,2).":".substr($macaddress,4,2).":".
                        substr($macaddress,6,2).":".substr($macaddress,8,2).":".substr($macaddress,10,2);
                        
         //query to check to make sure that mac address does not already exist in batch before trying to insert
         $query = "SElECT play_macaddress FROM play_batch WHERE play_macaddress='$formattedMAC' LIMIT 1";
         $result = $playdb->query($query);
         if($result->num_rows){
            echo "<font size=\"5\" color=\"red\">ERROR: MAC Address ($macaddress) is Already In The Batch.</font><br>";
            echo "<font size=\"5\" color=\"red\">Please Check MAC Address and Try Again</font><br><br><br>";
         }else{
                        
            $query  = "INSERT INTO play_batch SET play_macaddress='$formattedMAC',play_load_date='$timestamp',
                                    play_type='$playerType',play_set_number=$set,play_printed=$printed";
            //echo $query;
         
            if($playdb->query($query)){
               echo "<font size=\"5\" color=\"green\">SUCCESSFULLY Inserted MAC Address $formattedMAC Into Batch!</font><br><br><br>";
            }
         }
      }else{
         echo "<font size=\"5\" color=\"red\">ERROR: Incorrect MAC Address Formatting ($macaddress).</font><br>";
         echo "<font size=\"5\" color=\"red\">Please Check MAC Address and Try Again</font><br><br><br>";
      }
   }
   
   if(isset($_POST['search'])){
      $searchString = $_POST['macaddress'];
      if($resultsTable = genRecordsTable($playdb,0,$searchString)){
         echo "<font size=\"5\" color=\"green\">The following Records were found matching \"$searchString\"</font><br><br><br>";
         echo $resultsTable;
         echo "<br><br><br>";
      }else{
         echo "<font size=\"5\" color=\"red\">No Records found that match \"$searchString\"</font><br><br><br>";
      }     
   }
   
   if(isset($_POST['changeType'])){
      $macaddress = $_POST['macaddress'];
      $newType = "NEW";
      
      if(strcmp($_POST['type'],"NEW")==0){
         $newType="RMA";
      }
            
      $query = "UPDATE play_batch SET play_type='$newType' WHERE play_macaddress='$macaddress'";      
      $playdb->query($query);
   }
   
   $addRecordTable = new HTML_Table();
   
   $addRecordTable->setHeaderContents(0,0,"MAC Address:");
   $addRecordTable->setHeaderContents(0,1,"Player Type:");
   $addRecordTable->setHeaderContents(0,2,"Add Label to Unprinted Set:");
   $addRecordTable->setCellContents(1,0,genTextBox("macaddress"));
   $addRecordTable->setCellContents(1,1,genDropBox("type",array("NEW","RMA")));
   $addRecordTable->setCellContents(1,2,genDropBox("print",array("Do Not Print","Print")));  
   $addRecordTable->setCellContents(1,3,genButton("Add Record","add"));
   
   $attrs = array('width'=>'300px','align'=>'left');
   $addRecordTable->updateColAttributes(0,$attrs);
   $addRecordTable->updateColAttributes(1,$attrs);
   $addRecordTable->updateColAttributes(2,$attrs);
   
   $searchTable = new HTML_Table();
   $searchTable->setHeaderContents(0,0,"MAC Address:");
   $searchTable->setCellContents(1,0,genTextBox("macaddress"));
   $searchTable->setCellContents(1,1,genButton("Find Record","search"));
   $searchTable->updateColAttributes(0,$attrs);
   
   echo "<font size=\"4\"><b>Add a MAC Address to the Batch (Example: 00e011223456)</b></font><br>";
   echo startForm("edit.php?set=$lastSet","POST");
   echo $addRecordTable->toHTML();
   echo endForm();   
   echo "<br><br><br>";
   
   echo "<font size=\"4\"><b>Find a MAC Address in the current Batch (Example: 00e066554321)</b></font><br>";
   echo startForm("edit.php?set=$lastSet","POST");
   echo $searchTable->toHTML();
   echo endForm();
   echo "<br><br><br>";
   
   if(isset($_GET['set'])){
      //if set number was set we display the set's mac addresses
      echo "Set Number: $currentSet<br>";
      echo genRecordsTable($playdb,$currentSet);
   }else{
      //otherwise we display all the unprinted mac addresses
      $query = "SELECT play_macaddress FROM play_batch WHERE play_printed=0 LIMIT 1";
      $result=$playdb->query($query);
      
      if($result->num_rows){
         echo "Set Number: Unprinted<br>";
         echo genRecordsTable($playdb);
      }else{
         header("Location: edit.php?set=$lastSet");
      }
   }
   
   function genRecordsTable($database,$set=0,$searchStr=""){
      if(!empty($searchStr)){
         $query="SELECT play_macaddress,play_load_date,play_type FROM play_batch WHERE REPLACE(play_macaddress,':','') LIKE '%$searchStr%'";
      }else{
         $query="SELECT play_macaddress,play_load_date,play_type FROM play_batch WHERE play_set_number=$set ORDER BY play_print_order DESC";
      }
      //echo $query;
      $setResults=$database->query($query);
      
      $attrs = array('border' => '1');
      $setTable = new HTML_Table($attrs);
      
      $setTable->setHeaderContents(0,0,"MAC Address");
      $setTable->setHeaderContents(0,1,"Player Type");
      $setTable->setHeaderContents(0,2,"Player Load Date");
            
      $row=1;
      while($record=$setResults->fetch_assoc()){
         $setTable->setCellContents($row,0,$record['play_macaddress']);
         
         $typeStr = startForm("edit.php?set=$set","POST");
         $typeStr .= genHidden("macaddress",$record['play_macaddress']);
         $typeStr .= genHidden("type",$record['play_type']);
         $typeStr .= genButton("Change Type","changeType");
         $typeStr .= endForm();
         $setTable->setCellContents($row,1,$record['play_type']."&nbsp&nbsp&nbsp&nbsp".$typeStr);
         
         $setTable->setCellContents($row,2,$record['play_load_date']); 
         
         $deleteStr = startForm("edit.php?set=$set","POST");
         $deleteStr .= genHidden("macaddress",$record['play_macaddress']);
         $deleteStr .= genButton("Delete Record","delete");
         $deleteStr .= endForm();
         $setTable->setCellContents($row,3,$deleteStr);
         $row++;
      }
      
      $attrs = array('width'=>'200px','align' => 'center');
      $setTable->updateColAttributes(0,$attrs);
      $setTable->updateColAttributes(1,$attrs);
      $setTable->updateColAttributes(2,$attrs);
      $setTable->updateColAttributes(3,$attrs);
      
      $altAttrs=array('class' => 'alt');
      $setTable->altRowAttributes(1,null,$altAttrs);
      
      if($setResults->num_rows){
         return $setTable->toHTML();
      }else{
         return false;
      }
   }
?>