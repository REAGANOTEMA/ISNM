<?php
    $server = "localhost";
    
    $user = "igangaschoolofl_students_db";
    $password = "hbkKdmMHUfHTHuxWKPRf";
    $db = "igangaschoolofl_students_db";
    
    $conn = mysqli_connect($server, $user, $password, $db);

    if (!$conn) {
        header('Location: ../errors/error.html');
        exit();
    }
    

?>