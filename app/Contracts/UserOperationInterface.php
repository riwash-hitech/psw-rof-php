<?php
namespace App\Contracts;

interface UserOperationInterface{
     
    public function deleteRecords($res, $clientCode);
    
}