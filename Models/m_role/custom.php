<?php

namespace App\Models\CustomModels;

class m_role extends \App\Models\BasicModels\m_role
{    
    public function __construct()
    {
        parent::__construct();
    }
    
    public $fileColumns    = [ /*file_column*/ ];

    public $createAdditionalData = ["creator_id"=>"auth:id"];
    public $updateAdditionalData = ["last_editor_id"=>"auth:id"];

    public function custom_getAccess()
    {
        $req =  app()->request;
        // dd(auth()->user()->id);
        $user_id = auth()->user()->id; 

        $roleAccess = m_role_access::where('user_id', $user_id)->get();
        $menu = m_menu::where('endpoint', $req->endpoint)->first();
        // dd($roleAccess, $menu);

        //kalau superadmin bisa semua, harusnya ambil di m_role dulu cek is_superadmin tapi mager euy
        $superAdmin = ($roleAccess->filter(function($role){
            return $role->m_role_id === 1;
        }));


       if($superAdmin->count()){
         $res = [
                    'create' => true,
                    'update' => true,
                    'read' => true,
                    'delete' => true,
                    'verify' => true,
                ];

        return $res;
       }

        if($roleAccess->count() > 0 && $menu)
        {
            foreach($roleAccess as $access)
            {
                $permission = m_role_det::where('m_menu_id', $menu->id)
                ->where('m_role_id', $access->m_role_id)
                ->first();
            }   

            $res = [
                'create' => $permission->can_create ?? false,
                'update' => $permission->can_update ?? false,
                'read' => $permission->can_read ?? false,
                'delete' => $permission->can_delete ?? false,
                'verify' => $permission->can_verify ?? false,
            ];

            return $res;
        }else{
             $res = [
                'create' => false,
                'update' => false,
                'read' => false,
                'delete' => false,
                'verify' => false,
            ];

            return $res;
        }
    }

}