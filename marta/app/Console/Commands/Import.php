<?php

namespace App\Console\Commands;

use App\Station;
use Illuminate\Console\Command;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'm:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return mixed
     */
    public function handle()
    {
        $stations = "North Springs	54
Sandy Springs	18
Dunwoody	53
Medical Center	52
Buckhead	51
Lindberg Center	47
Arts Center	46
Midtown	45
North Avenue	44
Civic Center	43
Peachtree Center	42
Five Points	22
Garnett	19
West End	36
Oakland City	37
Lakewood/Ft. McPherson	38
East Point	39
College Park	40
Airport	41";
        $sa = explode("\n", $stations);
        foreach ($sa as $k=>$v) {
            $v = explode("\t", $v);
            $s = new Station();
            $n = $s->find($v[1]);
            if ($n === null) {
                $s->id = $v[1];
                $s->name = $v[0];
            }
        }
        $this->info('hey');
    }
}
