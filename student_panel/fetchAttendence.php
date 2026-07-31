

<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
include('../assets/config.php');
     $response="";
     session_start();
       if($_SERVER['REQUEST_METHOD']=="POST"){
          

            $month = date('m');

           $id = (int)$_SESSION['uid'];
           $stmt = $conn->prepare("select * from attendance where (student_id=?) AND (Month(`date`)=?)");
           $stmt->bind_param("is", $id, $month);
           $stmt->execute();
           $result = $stmt->get_result();
           $stmt->close();
          if($result->num_rows>0){
           
            while($row = $result->fetch_assoc()){


               $status = "";
               if($row['attendence'] == "1"){
                    $status = " <td style='color:green;'>Present</td>";
               }else{
                    $status = " <td style='color:red;'>Absent</td>";
               }

                  $response .=' <tr>
                       <td>'.date("d-m-Y",strtotime($row['date']."")).'</td>
                      '.$status.'
                           </tr>';
             }
              }
              else{
                    
              }

       }
       else {
            $response="something went wrong";
       }
       echo $response;
 ?>