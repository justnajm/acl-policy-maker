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
        $aRoles = [];
        $aPermissions = [];
        
        $aRoles[] = $this->ask("Please add roles for policy class, add role name:");
        $this->info("You can add permissions based on CRUD alphabets, each alphabet will allow that permission to role");
        $aPermissions[] = $this->ask("Please add permissions for ".$aRoles[count($aRoles)-1].", for example(editor: CR, author: RUD)");

        while($this->confirm("Want to add more roles?",true))
        {
            $aRoles[] = $this->ask("Specify new role name");
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
            $sType = "        '{".strtolower($aRoles[$aLoop])."]}'=>[".implode(",",$aPerm)."]\r\n";
            $aUserType[] = $sType;
        }
        
        $sUserType = $sUserType.implode(",",$aUserType);

        $sModel = $this->argument("ModelName");

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

    private function generateFile(string $sPath, string $sFileName, string $sContent)
    {
        $sPath = ($sPath?:app_path()."/Policies");
        $sFile = $sPath."/".$sFileName.".php";

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
