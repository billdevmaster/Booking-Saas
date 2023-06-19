<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Subscribe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscribe:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recurring Subscribe schedule';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $myfile = fopen("newfile.txt", "a") or die("Unable to open file!");
        $txt = date("Y-m-d H:i:s");
        fwrite($myfile, $txt);
        fclose($myfile);
    }
}
