<?php
/*****************************************************
 * PHP: For batch command to solve the uploaded pdfs
 *  will be called by solve_temp_pdf.py.
 *                                        L_Zealot
 *                                      18 Jan, 2017
 ****************************************************/
   //Read in the warehouse list
   $fdata = fopen("./list.dat","r");
   $i=0;
   while(!feof($fdata)){
      $all_paper[$i] = fgets($fdata);
      $i=$i+1;
   }
   fclose($fdata);

  //Read in the shortname database
   $fdata = fopen("./short.dat","r");
   $i=0;
   while(!feof($fdata)){
      $short[$i] = fgets($fdata);
      $i=$i+1;
   }
   fclose($fdata);

   $new_name=solve_enw($short,$all_paper,$argv[1]);  // solve the enw file
   
   function solve_enw($short,$all_paper, $old_name){
      $file    =  fopen("./warehouse/temp.ris","r");

      //initialize all flags

      $author  =  "";
      $journal =  "";
      $vol     =  "";
      $number  =  "";
      $page    =  "";
      $at      =  "";
      $year0   =  "";
      $enw_flag = true;   // default for enw file style
      $page_flag = false;
      $n_line  = 0;
      $end_line = 0;
      
      //---------Down to Solve the enw file---------------
      
      while (! feof($file)){
         $j_longname="";
         $line = fgets($file);
         $n_line += 1;     // the line that we are processing
         $mid_array=explode(" ",$line);
         $mid_array2=explode("-",$line);
         if(trim($mid_array[0])=="JF"||trim($mid_array[0])=="JO"||trim($mid_array[0])=="T2")
            $mid_array[0]="JA";
         switch(trim($mid_array[0]))
         {
         //------------RIS case in first--------------
        case "A1": //auther
            $enw_flag = false;
            if ($author==""){
               $author=substr(trim($mid_array[3]),0,-1);
            }
            break;
        case "AU": //auther
            $enw_flag = false;
            if ($author==""){
               $author=substr(trim($mid_array[3]),0,-1);
            }
            break;
         case "Y1": //year
            $year0  =  substr(trim($mid_array2[1]),0,4);
            break;
         case "PY": //year
            $year0  =  substr(trim($mid_array2[1]),0,4);
            break;
         case "JO": //journal
            foreach ($short as $item){
               $case_name=explode("@",$item);  //$case_name[0]=Journel of Climate; [1]= JC;
               $j_longname = trim($mid_array2[1]);
               $case_longname = trim($case_name[0]);

               if(strlen($j_longname)==strlen($case_longname)){
                  if(strcasecmp($case_longname,$j_longname)==0){
                     $journal = trim($case_name[1]);
                     break;
                  }
               }elseif(strlen($j_longname)<strlen($case_longname)){
                  if(stristr($case_longname,$j_longname)){
                     $journal = trim($case_name[1]);
                  }
               }else{
                  if(stristr($j_longname,$case_longname)){
                     $journal = trim($case_name[1]);
                  }
               }
            }
            break;
        case "JA": //journal
           if ($j_longname!="")
              break;
           foreach ($short as $item){
               $case_name=explode("@",$item);  //$case_name[0]=Journel of Climate; [1]= JC;
               $j_longname = trim($mid_array2[1]);
               $case_longname = trim($case_name[0]);

               if(strlen($j_longname)==strlen($case_longname)){
                  if(strcasecmp($case_longname,$j_longname)==0){
                     $journal = trim($case_name[1]);
                     break;
                  }
               }elseif(strlen($j_longname)<strlen($case_longname)){
                  if(stristr($case_longname,$j_longname)){
                     $journal = trim($case_name[1]);
                  }
               }else{
                  if(stristr($j_longname,$case_longname)){
                     $journal = trim($case_name[1]);
                  }
               }
            }
            break;
         case "VL":
            $vol  =  trim($mid_array2[1]);
            break;
         case "IS":
            $number  =  trim($mid_array2[1]);
            break;
         case "SP":
            $page0  =  trim($mid_array2[1]);
            if (is_numeric($page0))
               $page = $page0;
            break;
         case "EP":
            $page0  = trim($mid_array2[1]);
            if (is_numeric($page0))
            {
               $page .= "-".$page0;
               $page_flag = true;
            }
            break;
         case "ER":
            $end_line = $n_line;
            break;


         //------------ENW case after--------------
         case "%A":
            if ($author==""){
               $author=substr(trim($mid_array[1]),0,-1);
            }
            break;
         case "%J":
            foreach ($short as $item){
               $case_name=explode("@",$item);  //$case_name[0]=Journel of Climate; [1]= JC;
               $j_longname = trim(substr($line,3));
               $case_longname = trim($case_name[0]);

               if(strlen($j_longname)==strlen($case_longname)){
                  if(strcasecmp($case_longname,$j_longname)==0){
                     $journal = trim($case_name[1]);
                     break;
                  }
               }elseif(strlen($j_longname)<strlen($case_longname)){
                  if(stristr($case_longname,$j_longname)){
                     $journal = trim($case_name[1]);
                  }
               }else{
                  if(stristr($j_longname,$case_longname)){
                     $journal = trim($case_name[1]);
                  }
               }
            }
            break;
         case "%V":
            $vol  =  trim($mid_array[1]);
            break;
         case "%N":
            $number  =  trim($mid_array[1]);
            break;
         case "%P":
            $page  =  trim($mid_array[1]);
            $page_flag = true;
            break;
         case "%@":
            $at  =  trim($mid_array[1]);
            break;
         case "%D":
            $year0  =  trim($mid_array[1]);
            break;
         }
      }
      fclose($file);
      if($journal==""){
         echo("Unknown Journal abreviation for ".$j_longname.", Please set in short.dat first\n");
         exit; 
      } 
      if($page==""){
         $page=$at;
      }
    
    
      if (!is_numeric($vol))
          $vol = "fv".ord($author);
      if (!is_numeric($number))
          $number = "fn".ord($journal);
      if (!$page_flag)
          $page = "fp".ord(strrev($year0));
      //---------Up to Solve the enw file---------------
       $new_name=$author.'-'.$journal.'-'.$year0.'-'.$vol.'_'.$number.'_'.$page;
      //check the old papers
      $r_flag = 0; //flag for repeat check
      foreach ($all_paper as $paper){
         if(strcasecmp(trim($paper),trim($new_name))==0){
            $r_flag = 1;
            $old_paper= $paper;
            break;
         }
      }

      if ($r_flag){
         echo("There is already ".$old_paper.".pdf an old paper in warehouse\n");
         exit;
      }else{
      
         if ($enw_flag)
            echo "Rename:" . $new_name.".ris\n";
         else
            echo "Rename: " . $new_name.".ris\n";
         //write the URL and Label data
         if ($enw_flag){
            $file=fopen("./warehouse/temp.ris","a");
            fwrite($file,"%F ".$new_name.".pdf\n");
            fwrite($file,"%U http://222.200.180.66:1234/L_Zealot/paperhub/warehouse/".$new_name.".pdf\n");
            fwrite($file,"%> internal-pdf://".$new_name.".pdf\n");
            fclose($file);
         }
         else{
            $file=fopen("./warehouse/temp.ris","r");
            $line0 = 0;
            $newfp="";
            while (! feof($file)){
               $line = fgets($file);
               $line0 += 1;
               if(($line0-$end_line)==0)
               {   
                  continue;// get rid of the ending line
               }
               $newfp.=$line; 
            } 
            fclose($file); 
            $file=fopen("./warehouse/temp.ris","w");
            fwrite($file,$newfp);
            fwrite($file,"LB  - ".$new_name.".pdf\n");
            fwrite($file,"UR  - http://222.200.180.66:1234/L_Zealot/paperhub/warehouse/".$new_name.".pdf\n");
            fwrite($file,"L1  - internal-pdf://".$new_name.".pdf\n");
            fwrite($file,"ER  - \n");
            fclose($file);
         }
         if ($enw_flag){
            rename("./warehouse/temp.ris","./warehouse/".$new_name.".ris");
         }
         else{
            rename("./warehouse/temp.ris","./warehouse/".$new_name.".ris");
            rename($old_name, "./warehouse/".$new_name.".pdf");
         }
         //write the paper list
         $file=fopen("./list.dat","a");
         fwrite($file,$new_name."\n");
         fclose($file);
         return $new_name; 
      }
 
   }
?>

