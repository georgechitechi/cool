<?php

namespace Georgechitechi\Cool\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class RemovePackageCommand extends Command
{
	use FileHandler;

    protected $vendor;

    public $placeholders = [];

    public $replacements = [];

    protected $package;
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cool:remove {vendor} {name}';

    /**
     * The console command description.
     */
    protected $description = 'Remove a package from composer.json and the providers config then delete it from your app.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		// Defining vendor/package
        $vendor = $this->argument('vendor');
        $name = $this->argument('name');

        if (strstr($vendor, '/')) {
            [$vendor, $name] = explode('/', $vendor);
        }
        $this->vendor($vendor);
        $this->package($name);
		
        // Start the progress bar		
        $this->warn('This will Disable & Remove the package with all it\'s files and folders Data Can\'t be recovered');
        if ($this->confirm('Do you want to proceed?')) {
		
        // Start removing the package
        $this->info('Removing package '.$this->vendor().'\\'.$this->package().'...');
		
        // Uninstalling the package
        $this->info('Uninstalling package...');
        $this->uninstallPackage();
		
        // remove the package directory
        $this->info('Removing packages directory...');
        $this->removeDir($this->packagePath());

        // Finished disabling the package, end of the progress bar
        $this->info('');	
        $this->info('Package Removed successfully!');

        } else {
            $this->info('Operation Aborted...');
        }
    }
	
	
    public function vendor($vendor = null)
    {
        if ($vendor !== null) {
            return $this->vendor = $vendor;
        }
        if ($this->vendor === null) {
            throw new RuntimeException('Please provide a vendor');
        }

        return $this->vendor;
    }

    public function vendorStudly()
    {
        return Str::studly($this->vendor());
    }

    public function package($package = null)
    {
        if ($package !== null) {
            return $this->package = $package;
        }
        if ($this->package === null) {
            throw new RuntimeException('Please provide a package name');
        }

        return $this->package;
    }


    public function dumpAutoloads()
    {
        shell_exec('composer dump-autoload');
    }

    public function installPackage()
    {
        $this->addPathRepository();
        $this->requirePackage();
    }

    public function uninstallPackage()
    {
        $this->removePackage();
        $this->removePathRepository();
    }

    public function addPathRepository()
    {
        $params = json_encode([
            'type' => 'path',
            'url' => $this->packagePath(),
            'options' => [
                'symlink' => true,
            ],
        ]);
        $command = [
            'composer',
            'config',
            'repositories.'.Str::slug($this->vendor).'/'.Str::slug($this->package),
            $params,
            '--file',
            'composer.json',
        ];

        return $this->runProcess($command);
    }

    public function removePathRepository()
    {
        return $this->runProcess([
            'composer',
            'config',
            '--unset',
            'repositories.'.Str::slug($this->vendor).'/'.Str::slug($this->package),
        ]);
    }

    public function requirePackage()
    {
        return $this->runProcess([
            'composer',
            'require',
            $this->vendor.'/'.$this->package.':*',
        ]);
    }

    public function removePackage()
    {
        return $this->runProcess([
            'composer',
            'remove',
            $this->vendor.'/'.$this->package,
        ]);
    }
	
    protected function runProcess(array $command)
    {
        $process = new \Symfony\Component\Process\Process($command, base_path());
        $process->setTimeout(config('packager.timeout'));
        $process->run();
        return $process->getExitCode() === 0;
    }
}