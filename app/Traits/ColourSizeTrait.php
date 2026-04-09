<?php

namespace App\Traits;

use App\Models\PswClientLive\Local\LiveProductCategory;
use App\Models\PswClientLive\Local\LiveProductColor;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductSize;

trait ColourSizeTrait{

    
    public  function saveUpdateColour($name, $colourID){
        $checkColor = LiveProductColor::where('name', trim($name))->first(); 
        if($checkColor){
            if($name != ''){
                LiveProductColor::where("id", $checkColor->id)->update(["name" => trim($name),"colourID" => $colourID, "pendingProcess" => 0 ]);
            }
        }else{
            if($name != ''){
                LiveProductColor::create(["name" => trim($name),"colourID" => $colourID, "pendingProcess" => 1 ]);
            }
        }
    }

    public  function saveUpdateSize($name){
        $checkSize = LiveProductSize::where('name', trim($name))->first();
        if(!$checkSize){
            if(trim($name) != ''){
                LiveProductSize::create(["name" => trim($name), "pendingProcess" => 1 ]);
            }
        }
 
    }

    public  function saveUpdateCategory($name){
       
        //For Category
        $checkCat = LiveProductCategory::where('name', trim($name))->first();
        if(!$checkCat){
            if(trim($name) != ''){
                LiveProductCategory::updateOrcreate(
                    [
                        'name' => trim($name)
                    ],
                    [
                        'name' => trim($name),
                        'pendingProcess' => 1
                    ]
                );
            }
        }
    }

    public  function saveUpdateGroup($schoolID, $name,$store){
       
        $group = LiveProductGroup::where("SchoolID", trim($schoolID))->first();
        $groupPending = 1;
        if($group){
            $old = trim($group->SchoolName)."_";
            $new = trim($name)."_";
            if($old != $new){
                $groupPending = 1;    
            }else{
                $groupPending = 0;    
            }
        } 

        LiveProductGroup::updateOrcreate(
            [
                "SchoolID" => trim($schoolID), 
            ],
            [
                "SchoolID" => $schoolID,
                "SchoolName" => trim($name), 
                // "WebEnabled" => $isActive,
                "parentSchoolGroup" => $store,
                "pendingProcess" => $groupPending
            ]
        );
    }
  
}