<?php

namespace App\Console\Commands;

use App\Station;
use Illuminate\Console\Command;

class LatLon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'm:lat';

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
        $data = "North Springs	33.9450922	-84.3572501
Sandy Springs	33.8998925	-84.4235349
Dunwoody	33.92053	-84.3432595
Medical Center	33.9106555	-84.351555
Buckhead	33.8475874	-84.3673399
Doraville	33.90061	-84.2830754
Chamblee	33.8877493	-84.3057019
Brookhaven/Oglethorpe	33.8596233	-84.3400687
Lenox	33.8479986	-84.3539139
Lindbergh Center	33.8241058	-84.3686323
Arts Center	33.789477	-84.387041
Midtown	33.7808456	-84.3864791
North Avenue	33.7733667	-84.387348
Civic Center	33.7656678	-84.3870568
Peachtree Center	33.7585482	-84.3876217
Five Points	33.7542567	-84.3913508
Garnett	33.7483756	-84.3950462
West End	33.7358043	-84.4137364
Oakland City	33.7177008	-84.4249923
Lakewood/Ft. McPherson	33.6995905	-84.4293282
East Point	33.6700671	-84.4428104
College Park	33.65076	-84.4482464
Airport	33.6407764	-84.4443259
Indian Creek	33.7698367	-84.2298614
Kensington	33.7725813	-84.2519432
Avondale	33.7761332	-84.2824517
Decatur	33.7746721	-84.2948993
East Lake	33.7648224	-84.3134985
Edgewood/Candler Park	33.76204	-84.3401546
Inman Park	33.7577241	-84.3534238
King Memorial	33.7520955	-84.3781609
Georgia State	33.7503138	-84.3865965
Dome/GWCC	33.756556	-84.398636
Vine City	33.7568609	-84.4038878
Ashby	33.7569342	-84.4175156
Bankhead	33.771742	-84.431603
West Lake	33.752261	-84.44511
H.E. Holmes	33.79626754	-84.24110413";
        $data = explode("\n", $data);
        foreach ($data as $k => $v) {
            $tab = explode("\t", $v);
            $s = new Station();
            $r = $s->where('name', $tab[0]);
            if ($r !== null) {
                $r->lat = $tab[1];
                $r->lon = $tab[2];
            } else {
                $this->info($v);
            }
        }
        $this->info('done');
        return;
    }
}
