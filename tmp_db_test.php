<?php
function test($host,$user,$pass,$db=''){
    try {
        $conn=new mysqli($host,$user,$pass,$db,3306);
        if($conn->connect_error){
            return 'ERR:'.$conn->connect_error;
        }
        $info=$conn->host_info;
        $conn->close();
        return 'OK('.$info.')';
    } catch (Throwable $e) {
        return 'FAIL:'.$e->getMessage();
    }
}
echo "1: ".test('localhost','root','').PHP_EOL;
echo "2: ".test('localhost','root','hbkKdmMHUfHTHuxWKPRf').PHP_EOL;
echo "3: ".test('localhost','igangaschoolofl_staffs_db','AgKzJjZZnT5q58jCahs8','igangaschoolofl_staffs_db').PHP_EOL;
echo "4: ".test('127.0.0.1','igangaschoolofl_staffs_db','AgKzJjZZnT5q58jCahs8','igangaschoolofl_staffs_db').PHP_EOL;
echo "5: ".test('localhost','root','')->PHP_EOL;
