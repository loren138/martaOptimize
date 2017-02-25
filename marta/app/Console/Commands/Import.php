<?php

namespace App\Console\Commands;

use App\Station;
use App\StationOrder;
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
        $stations['red'] = "North Springs	54
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
        $stations['gold'] = "Doraville	50
Chamblee	49
Brookhaven/Oglethorpe	20
Lenox	48
Lindbergh Center	47
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
        $stations['blue'] = 'Indian Creek	35
Kensington	34
Avondale	33
Decatur	32
East Lake	31
Edgewood/Candler Park	30
Inman Park	29
King Memorial	28
Georgia State	27
Five Points	22
Dome/GWCC	24
Vine City	23
Ashby	21
West Lake	25
H.E. Holmes	26';
        $stations['green'] = 'Edgewood/Candler Park	30
Inman Park	29
King Memorial	28
Georgia State	27
Five Points	22
Dome/GWCC	24
Vine City	23
Ashby	21
Bankhead	13';

        $sa = explode("\n", $stations);
        foreach ($stations as $line => $v) {
            $stations[$line] = explode("\n", $v);
            $order = 0;
            foreach ($stations[$line] as $k2 => $v2) {
                $order++;
                $stations[$line][$k2] = explode("\t", $v2);
                $s = new Station();
                $n = $s->find($v[1]);
                if ($n === null) {
                    $s->id = $v[1];
                    $s->name = $v[0];
                    $s->save();
                }
                $so = new StationOrder();
                $so->station = $v[1];
                $so->ordering = $order;
                $so->delay = 0;
                $so->line = $line;
                $so->save();
            }
        }
        $this->info('hey');
    }
}
