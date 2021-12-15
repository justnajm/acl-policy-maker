<?php

namespace Najm\AclPolicyMaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class AclPolicyMaker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aclpolicy:make {ModelName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make policy class having all required roles and their CRUD permissions, can authorize model/controller/views';

    protected $oFile;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(FileSystem $oFile)
    {
        parent::__construct();
        $this->oFile = $oFile;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sModel = $this->argument("ModelName");

        if(!$this->checkModelExist($sModel))
        {
            $this->error("Please make sure model class exist: ".app_path("Models/$sModel.php"));
            return false;
        }
        $aRoles = [];
        $aPermissions = [];
        
        $aRoles[] = $this->ask("Please add roles for policy class, specify 1st role name");
        $this->info("Now can add permissions based on C R U D alphabets, each alphabet will allow that permission to particular role, for example editor role can CR only, author role can RUD except Creat, admin role can CRUD full access");
        $aPermissions[] = $this->ask("Please add permissions for ".$aRoles[count($aRoles)-1].", for example(editor: CR, author: RUD, admin: CRUD)");

        while($this->confirm("Want to add more roles?",true))
        {
            $aRoles[] = $this->ask("Add/Specify ".(count($aRoles)+1)." role name");
            $aPermissions[] = $this->ask("Please add permissions for ".$aRoles[count($aRoles)-1].", for example(editor: CR, author: RUD)");
        }
        
        $this->info(print_r($aRoles,true));
        $this->info(print_r($aPermissions,true));
        $aCRUD = ['C'=>'create','R'=>'read','U'=>'update','D'=>'delete'];
        $sUserType = "";
        $aUserType = [];
        $iLoop = 0;
        for($aLoop=0;$aLoop<count($aRoles);$aLoop++)
        {
            $aPerm = [];
            for($i=0;$i<strlen($aPermissions[$aLoop]);$i++)
            {
                $aPerm[] = "'{$aCRUD[$aPermissions[$aLoop][$i]]}'";
            }
            $sType = "        '".strtolower($aRoles[$aLoop])."'=>[".implode(",",$aPerm)."]\r\n";
            $aUserType[] = $sType;
        }
        
        $sUserType = $sUserType.implode(",",$aUserType);

        $sContent =<<<HC
<?php
namespace App\Policies;

use App\Models\User;
use App\Models\\$sModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class {$sModel}Policy
{
    use HandlesAuthorization;

    protected \$aUserType = [
{$sUserType}
    ];
    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        // die("I loaded");
    }

    public function viewAny(User \$oUser)
    {
        return (\$oUser?in_array("read",\$this->aUserType[\$oUser->role]):true);
    }

    public function view(User \$oUser)
    {
        return (\$oUser?in_array("read",\$this->aUserType[\$oUser->role]):true);
    }

    public function create(User \$oUser)
    {
        return in_array("create",\$this->aUserType[\$oUser->role]);
    }

    public function update(User \$oUser)
    {
        return in_array("update",\$this->aUserType[\$oUser->role]);
    }

    public function delete(User \$oUser,$sModel \$oPost)
    {
        return in_array("delete",\$this->aUserType[\$oUser->role]);
    }
}
HC;

        $this->generateFile(app_path()."/Policies",ucwords($sModel)."Policy",$sContent);

        return Command::SUCCESS;
    }

    private function checkModelExist(string $sModelName)
    {
        $sPathModel = app_path("Models/$sModelName.php");
        
        if(!$this->oFile->isFile($sPathModel))
        {
            return false;
        }

        return true;
    }

    private function generateFile(string $sPath, string $sFileName, string $sContent)
    {
        $sPath = ($sPath?:app_path()."/Policies");
        $sFile = $sPath."/".$sFileName.".php";

        if(!$this->oFile->isDirectory($sPath))
        {
            $this->oFile->makeDirectory($sPath,0644,true,true);
        }

        if($this->oFile->isFile($sFile))
        {
            if($this->confirm("File exist, overwrite ?",true))
            {
                //
            } else {
                $this->error("File already exist");
                return false;
            }
        }

        if($this->oFile->put($sFile,$sContent))
            $this->info("Policy generated");
        else
            $this->error("Unable to write file");
    }
}
