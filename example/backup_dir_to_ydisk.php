<?php

/**
 * This file could be used as an example of uploading the whole directory to YDisk recursively
 * or as a ready-to-use command line script, please find usage below, or just run it in console
 */

error_reporting(E_ALL);

require_once __DIR__.'/../vendor/autoload.php';

class YDiskBackup
{
    protected $auth_token;
    protected $backup_src_dir;
    protected $disk_dest_resrouce;

    protected $is_overwrite_enabled = false;
    protected $is_md5_check_enabled = true;
    protected $is_quiet_mod_enanled = false;

    private $disk;

    /* use \Psr\Log\LoggerTrait; */

    /**
     * YDiskBackup constructor.
     * @param $auth_token               Authorization token
     * @param $backup_src_dir           Directory to backup
     * @param $disk_dest_resource       Destination YD resource to backup to
     * @param bool $force_overwrite     Overwrite files if exist OR md5 is different
     * @param bool $check_md5           Whether MD5 checksums should be verified
     * @param bool $be_quiet            Whether verbose output is enabled
     */
    public function __construct($auth_token, $backup_src_dir, $disk_dest_resource, $force_overwrite = false, $verify_md5 = true, $be_quiet = false)
    {
        $this->auth_token = $auth_token;
        $this->backup_src_dir = $backup_src_dir;
        $this->disk_dest_resrouce = $disk_dest_resource;

        $this->disk = new Arhitector\Yandex\Disk();
        $this->disk->setAccessToken($auth_token);

        $this->is_md5_check_enabled = $verify_md5;
        $this->is_overwrite_enabled = $force_overwrite;
        $this->is_quiet_mod_enanled = $be_quiet;

        return $this;
    }

    public function run()
    {
        $this->iterateDirectory('');
    }

    protected function backupFile($src_path, $dest_resource)
    {
        $this->info("Backing $src_path up to $dest_resource...");
        $res = $this->disk->getResource($dest_resource);

        if ( $res->has() === true
            and $this->is_overwrite_enabled === false
            and $this->is_md5_check_enabled === false )
        {
            $this->info("The resource $dest_resource exists. Please use overwrite flag to force upload");
            return;
        }

        if ( $res->has() === false )
        {
            $result = $res->upload($src_path);
            $this->info("Upload request status: $result");
        }
        elseif ( $this->is_md5_check_enabled )
        {
            $src_file_md5 = md5_file($src_path);
            $dest_file_md5 = $res->get('md5');
            if ( strcmp($src_file_md5, $dest_file_md5) !== 0 )
            {
                $this->warn("Failed md5 check: src [$src_file_md5] dest [$dest_file_md5]");
                if ( $this->is_overwrite_enabled )
                {
                    $result = $res->upload($src_path, true);
                    $this->info("Overwrite upload request status: $result");
                }
                else
                {
                    $this->warn("Please consider using overwrite flag to force upload.");
                }
            }
            else
            {
                $this->info("File is alreay on Disk and md5 is equal, skipping.");
            }
        }
        else
        {
            $this->info("File exists on Disk, md5 check is disabled, skipping.");
        }
    }

    protected function iterateDirectory($cur_rel_path)
    {
        $cur_dir = $this->backup_src_dir.$cur_rel_path;
        $dir_handle = opendir($cur_dir);
        $this->info('Iterating directory: '.$cur_dir);

        while ( $file = readdir($dir_handle) )
        {
            if ($file[0] == '.') continue; // skip '.' '..' and hidden files

            $file_next = $cur_dir.DIRECTORY_SEPARATOR.$file;
            $target_resource_path = $this->disk_dest_resrouce.$cur_rel_path.'/'.$file;

            if ( is_dir($file_next) )
            {

                $res = $this->disk->getResource($target_resource_path);

                if ( $res->has() === false )
                {
                    $this->info("The Dir resource '$target_resource_path' doesn't exist. Going to create one");
                    $res->create();
                }

                $this->iterateDirectory($cur_rel_path.DIRECTORY_SEPARATOR.$file);
            }
            else
            {
                $this->backupFile($file_next, $target_resource_path);
            }
        }
    }

    public function log($level, $message, array $context = array())
    {
        if ($this->is_quiet_mod_enanled) return;

        $pid = 'N/A';
        if (function_exists('getmypid'))
        {
            $pid = getmypid();
        }

        echo date('Y-m-d H:i:s').' ('.$pid.')'.' ['.$level.'] '.$message.(count($context) ? '. Context: '.var_export($context, true) : '')."\n";
    }

    public function info($message, array $context = array())
    {
        return $this->log('INFO', $message, $context);
    }

    public function warn($message, array $context = array())
    {
        return $this->log('WARN', $message, $context);
    }
}

/**
 * Fetch options from the command line arguments
 */

$usage = "Usage: php ".basename(__FILE__)." --token=TOKEN src_backup_dir dest_ydisk_resource";

$longopts = array(
    "--token: YD authorisaztion token, required"    => "token:",
    "--force: force overwrite, default 'no'"        => "force::",
    "--verify: verify MD5 checksums, default 'no'"  => "verify::",
    "--quiet: disable verbose output, default 'no'" => "quiet::",
);

$options = getopt('', $longopts);

if ($argc < 4
    or !array_key_exists('token', $options) or strlen($options['token']) < 32
    or $argv[$argc-2][0] == '-' or $argv[$argc-1][0] == '-')
{
    echo "$usage\nAvailable options: \n";
    foreach ($longopts as $opt_desc => $opt)
    {
        echo "  $opt_desc\n";
    }
    echo "\nExample:\n".basename(__FILE__)." --token=270d7f9b7e23a15a7 /var/local/database_dump/ disk:/Backups/DataBase\n\n";
    exit(1);
}

$backup_src_dir     = $argv[$argc-2];
$disk_dest_resource = $argv[$argc-1];

foreach (array('force', 'verify', 'quiet') as $option)
{
    if (array_key_exists($option, $options))
    {
        if ($options[$option] == 'yes')
        {
            $options[$option] = true;
        }
    }
    else
    {
        $options[$option] = false;
    }
}

/**
 * Do the backup with these options
 */

$ydBackup = new YDiskBackup($options['token'], realpath($backup_src_dir), $disk_dest_resource, $options['force'], $options['verify'], $options['quiet']);
$ydBackup->run();