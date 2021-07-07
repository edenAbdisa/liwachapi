<?php
namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\AddressResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str; 
class HelperClass {

    public static function uploadFile($fileToBeUploaded,$filename,$location){
        return $fileToBeUploaded->move($location,$filename);  
    }
}