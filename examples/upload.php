<?php

$uplod_path = __DIR__.'/uploads/';
$file_name = $_FILES['file']['name'];
$file_tmp_path = $_FILES['file']['tmp_name'];
$res =  move_uploaded_file($file_tmp_path,$uplod_path . $file_name);
if($res){
    echo json_encode(['code'=>1,'msg'=>$res,'file'=>$uplod_path . $file_name]);
}else{
    echo json_encode(['code'=>0,'msg'=>'error']);
}