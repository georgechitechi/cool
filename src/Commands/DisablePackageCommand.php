<?php

namespace Georgechitechi\Cool\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DisablePackageCommand extends Command
{
	use FileHandler;

    protected $vendor;

    public $placeholders = [];

    public $replacements = [];

    protected $package;
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cool:disable {vendor} {name}';

    /**
     * The console command description.
     */
    protected $description = 'Disable a package by removing it from composer.json and the providers config.';

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
        // Start the progress bar
        $this->info('Your package disabling process started, please wait...');

        // Defining vendor/package
        $this->vendor($this->argument('vendor'));
        $this->package($this->argument('name'));

        // Start disabling the package
        $this->info('Disabling package '.$this->vendor().'/'.$this->package().' ...');

        // Disabling the package
        $this->info('Uninstalling package...');
        $this->uninstallPackage();

        if ($this->confirm('Do you want to delete the package from packages directory?')) {
       
		$this->removeDir($this->packagePath());
		$this->info('Removing Package ...');
		$this->info('Running Composer dump-autoload ...');
		$this->dumpAutoloads();
		$this->info('Package Removed successfully!');        
		} else {
            $this->info('Package still available in the packages directory.');
			$this->info('To reactivate the package run');
			$this->warn('php artisan cool:enable '.$this->vendor().' '.$this->package().'');
			$this->info('Running Composer dump-autoload ...');
			$this->dumpAutoloads();
		$this->info('');
        $this->info('Package Disabled Successfully!');
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
        $process->setTimeout(config('500'));
        $process->run();
        return $process->getExitCode() === 0;
    }
}