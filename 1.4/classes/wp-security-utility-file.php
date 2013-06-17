<?php

class AIOWPSecurity_Utility_File
{
    
    /* This variable will be an array which will contain all of the files and/or directories we wish to check permissions for */
    public $files_and_dirs_to_check;
    
    function __construct(){
         /* Let's initiliaze our class variable array with all of the files and/or directories we wish to check permissions for.
         * NOTE: we can add to this list in future if we wish
         */
         $this->files_and_dirs_to_check = array(
            array('name'=>'root directory','path'=>ABSPATH,'permissions'=>'0755'),
            array('name'=>'wp-includes/','path'=>ABSPATH."wp-includes",'permissions'=>'0755'),
            array('name'=>'.htaccess','path'=>ABSPATH.".htaccess",'permissions'=>'0644'),
            array('name'=>'wp-admin/index.php','path'=>ABSPATH."wp-admin/index.php",'permissions'=>'0644'),
            array('name'=>'wp-admin/js/','path'=>ABSPATH."wp-admin/js/",'permissions'=>'0755'),
            array('name'=>'wp-content/themes/','path'=>ABSPATH."wp-content/themes",'permissions'=>'0755'),
            array('name'=>'wp-content/plugins/','path'=>ABSPATH."wp-content/plugins",'permissions'=>'0755'),
            array('name'=>'wp-admin/','path'=>ABSPATH."wp-admin",'permissions'=>'0755'),
            array('name'=>'wp-content/','path'=>ABSPATH."wp-content",'permissions'=>'0755'),
            array('name'=>'wp-config.php','path'=>ABSPATH."wp-config.php",'permissions'=>'0644')
            //Add as many files or dirs as needed by following the convention above
        );

    }
    
    static function write_content_to_file($file_path, $new_contents)
    {
        @chmod($file_path, 0777);
        if (is_writeable($file_path))
        {
            $handle = fopen($file_path, 'w');
            foreach( $new_contents as $line ) {
                fwrite($handle, $line);
            }
            fclose($handle);
            @chmod($file_path, 0644); //Let's change the file back to a secure permission setting
            return true;
	} else {
            return false;
	}
    }
    
    static function backup_a_file($src_file_path, $suffix = 'backup')
    {
        $backup_file_path = $src_file_path . '.' . $suffix;
        if (!copy($src_file_path, $backup_file_path)) {
            //Failed to make a backup copy
            return false;
        }
        return true;
    } 
    
    static function recursive_file_search($pattern='*', $flags = 0, $path='')
    {
        $paths=glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
        $files=glob($path.$pattern, $flags);
        foreach ($paths as $path) { $files=array_merge($files,AIOWPSecurity_Utility_File::recursive_file_search($pattern, $flags, $path)); }
        return $files;
    }
    
    /*
     * Useful when wanting to echo file contents to screen with <br /> tags
     */
    static function get_file_contents_with_br($src_file)
    {
        $file_contents = file_get_contents($src_file);        
        return nl2br($file_contents);
    }

    /*
     * Useful when wanting to echo file contents inside textarea
     */
    static function get_file_contents($src_file)
    {
        $file_contents = file_get_contents($src_file);        
        return $file_contents;
    }
    
    /*
     * Returns the file's permission value eg, "0755"
     */
    static function get_file_permission($filepath)
    {
        if (!function_exists('fileperms')) 
        {
            $perms = '-1';
        }
        else 
        {
            clearstatcache();
            $perms = substr(sprintf("%o", @fileperms($filepath)), -4);
        }
        return $perms;
    }

    /*
     * This function will compare the current permission value for a file or dir with the recommended value.
     * It will compare the individual "execute", "write" and "read" bits for the "public", "group" and "owner" permissions.
     * If the permissions for an actual bit value are greater than the recommended value it returns '0' (=less secure)
     * Otherwise it returns '1' which means it is secure
     * Accepts permission value parameters in octal, ie, "0777" or "777"
     */
    static function is_file_permission_secure($recommended, $actual)
    {
        $result = 1; //initialize return result

        //Check "public" permissions
        $public_value_actual = substr($actual,-1,1); //get dec value for actual public permission
        $public_value_rec = substr($recommended,-1,1); //get dec value for recommended public permission

        $pva_bin = decbin($public_value_actual); //Convert value to binary
        $pvr_bin = decbin($public_value_rec); //Convert value to binary
        //Compare the "executable" bit values for the public actual versus the recommended
        if (substr($pva_bin,-1,1)<=substr($pvr_bin,-1,1))
        {
            //The "execute" bit is the same or less as the recommended value
            $result = 1*$result;
        }else
        {
            //The "execute" bit is switched on for the actual value - meaning it is less secure
            $result = 0*$result;
        }
        
        //Compare the "write" bit values for the public actual versus the recommended
        if (substr($pva_bin,-2,1)<=substr($pvr_bin,-2,1))
        {
            //The "write" bit is the same or less as the recommended value
            $result = 1*$result;
        }else
        {
            //The "write" bit is switched on for the actual value - meaning it is less secure
            $result = 0*$result;
        }

        //Compare the "read" bit values for the public actual versus the recommended
        if (substr($pva_bin,-3,1)<=substr($pvr_bin,-3,1))
        {
            //The "read" bit is the same or less as the recommended value
            $result = 1*$result;
        }else
        {
            //The "read" bit is switched on for the actual value - meaning it is less secure
            $result = 0*$result;
        }

        //Check "group" permissions
        $group_value_actual = substr($actual,-2,1);
        $group_value_rec = substr($recommended,-2,1);
        $gva_bin = decbin($group_value_actual); //Convert value to binary
        $gvr_bin = decbin($group_value_rec); //Convert value to binary

        //Compare the "executable" bit values for the group actual versus the recommended
        if (substr($gva_bin,-1,1)<=substr($gvr_bin,-1,1))
        {
            //The "execute" bit is the same or less as the recommended value
            $result = 1*$result;
        }else
        {
            //The "execute" bit is switched on for the actual value - meaning it is less secure
            $result = 0*$result;
        }

        //Compare the "write" bit values for the public actual versus the recommended
        if (substr($gva_bin,-2,1)<=substr($gvr_bin,-2,1))
        {
            //The "write" bit is the same or less as the recommended value
            $result = 1*$result;
        }else
        {
            //The "write" bit is switched on for the actual value - meaning it is less secure
            $result = 0*$result;
        }

        //Compare the "read" bit values for the public actual versus the recommended
        if (substr($gva_bin,-3,1)<=substr($gvr_bin,-3,1))
        {
            //The "read" bit is the same or less as the recommended value
            $result = 1*$result;
        }else
        {
            //The "read" bit is switched on for the actual value - meaning it is less secure
            $result = 0*$result;
        }
        
        //Check "owner" permissions
        $owner_value_actual = substr($actual,-3,1);
        $owner_value_rec = substr($recommended,-3,1);
        $ova_bin = decbin($owner_value_actual); //Convert value to binary
        $ovr_bin = decbin($owner_value_rec); //Convert value to binary

        //Compare the "executable" bit values for the group actual versus the recommended
        if (substr($ova_bin,-1,1)<=substr($ovr_bin,-1,1))
        {
            //The "execute" bit is the same or less as the recommended value
            $result = 1*$result;
        }else
        {
            //The "execute" bit is switched on for the actual value - meaning it is less secure
            $result = 0*$result;
        }

        //Compare the "write" bit values for the public actual versus the recommended
        if (substr($ova_bin,-2,1)<=substr($ovr_bin,-2,1))
        {
            //The "write" bit is the same or less as the recommended value
            $result = 1*$result;
        }else
        {
            //The "write" bit is switched on for the actual value - meaning it is less secure
            $result = 0*$result;
        }

        //Compare the "read" bit values for the public actual versus the recommended
        if (substr($ova_bin,-3,1)<=substr($ovr_bin,-3,1))
        {
            //The "read" bit is the same or less as the recommended value
            $result = 1*$result;
        }else
        {
            //The "read" bit is switched on for the actual value - meaning it is less secure
            $result = 0*$result;
        }

        return $result;
    }

}
