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
        $stations['red'] = "North Springs	54  0
Sandy Springs	18  2
Dunwoody	53  2
Medical Center	52  2
Buckhead	51  5
Lindberg Center	47  4
Arts Center	46  4
Midtown	45  2
North Avenue    44 1
Civic Center	43  1
Peachtree Center	42  1
Five Points	22  2
Garnett	19  2
West End	36  3
Oakland City	37  2
Lakewood/Ft. McPherson	38  2
East Point	39  3
College Park	40  3
Airport	41  2";
        $stations['gold'] = "Doraville	50  0
Chamblee	49  3
Brookhaven/Oglethorpe	20  4
Lenox	48  3
Lindbergh Center	47  3
Arts Center	46  4
Midtown	45  2
North Avenue	44  1
Civic Center	43  1
Peachtree Center	42  1
Five Points	22  2
Garnett	19  2
West End	36  3
Oakland City	37  2
Lakewood/Ft. McPherson	38  2
East Point	39  3
College Park	40  3
Airport	41  2";
        $stations['blue'] = 'Indian Creek	35  0
Kensington	34  2
Avondale	33  3
Decatur	32  4
East Lake	31  3
Edgewood/Candler Park	30  3
Inman Park	29  2
King Memorial	28  3
Georgia State	27  2
Five Points	22  1
Dome/GWCC	24  1
Vine City	23  1
Ashby	21  1
West Lake	25  3
H.E. Holmes	26  3';
        $stations['green'] = 'Edgewood/Candler Park	30  0
Inman Park	29  2
King Memorial	28  3
Georgia State	27  2
Five Points	22  1
Dome/GWCC	24  1
Vine City	23  1
Ashby	21  1
Bankhead	13  4';

        foreach ($stations as $line => $v) {
            $stations[$line] = explode("\n", $v);
            $order = 0;
            foreach ($stations[$line] as $k2 => $v2) {
                $order++;
                $v2 = explode("\t", str_replace("  ", "\t", $v2));
                $stations[$line][$k2] = $v2;
                $s = new Station();
                $n = $s->find($v2[1]);
                if ($n === null) {
                    $s->id = $v2[1];
                    $s->name = $v2[0];
                    $s->save();
                }
                $so = new StationOrder();
                $so->station = $v2[1];
                $so->ordering = $order;
                $so->delay = $v2[2];
                $so->line = $line;
                $so->save();
            }
        }
        $this->info('hey');
    }
}
