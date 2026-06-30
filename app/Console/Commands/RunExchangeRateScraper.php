<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class RunExchangeRateScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the standalone Node.js scraper to sync exchange rates.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting the Node.js Exchange Rate Scraper...");
        Log::info("RunExchangeRateScraper triggered.");

        // Path to the scraper directory
        $scraperPath = base_path('scraper');

        // Execute the Node script
        // For testing, we ensure npm dependencies are installed (optional here, usually part of deployment)
        $process = new Process(['node', 'index.js']);
        $process->setWorkingDirectory($scraperPath);
        $process->setTimeout(60); // 1 minute timeout
        
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->error($buffer);
                Log::error("Scraper Error: " . $buffer);
            } else {
                $this->line($buffer);
                Log::info("Scraper Output: " . $buffer);
            }
        });

        if ($process->isSuccessful()) {
            $this->info("Scraper execution completed successfully.");
        } else {
            $this->error("Scraper execution failed.");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
