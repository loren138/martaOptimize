<?php

namespace App\Console\Commands;

use App\ChunksAll;
use App\Station;
use App\StationOrder;
use App\TrainSched;
use Illuminate\Console\Command;

class Trips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'm:trips';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public $trains = [
       // ['stations' => [], 'dwell5points' => 0]
    ];

    public $stations = [

    ];

    public $trainSched;
    public $delay = 0;
    public $stationOrder;
    public $chunksAll;
    public $report = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $s = new Station();
        $this->trainSched = new TrainSched();
        $this->stationOrder = new StationOrder();
        $this->chunksAll = new ChunksAll();
        foreach ($s->all() as $v) {
            $this->stations[$v->id] = ['name' => $v->name, 'people' => []];
        }
    }

    public function convertTime($minute) {
        $hour = 4;
        $hour = 4 + floor($minute/60);
        $minute = $minute % 60;
        if ($hour >= 24) {
            $hour -= 24;
        }

        return $hour.':'.str_pad($minute, 2, '0',STR_PAD_LEFT).':00';
    }

    public function placeTrains($time) {
        $place = $this->trainSched->where('startTime', $time)->get();
        foreach ($place as $p) {
            $start = $this->stationOrder->where('station', $p->startStation)->get();
            $end = $this->stationOrder->where('station', $p->endStation)->get();
            if (count($start) == 1) {
                $line = $start[0]->line;
            } else {
                $line = $end[0]->line;
            }
            $trains = $this->stationOrder->where('line', $line)->orderBy('ordering')->get();
            $active = false;
            $stations = [];
            $first = false;
            $forward = true;
            $lastDelay = 0;
            foreach ($trains as $t) {
                if (!$active) {
                    if ($t->station == $p->startStation) {
                        $forward = true;
                        $active = true;
                    } elseif ($t->station == $p->endStation) {
                        $forward = false;
                        $active = true;
                    }
                }
                if ($active) {
                    $stations[] = ['s' => $t->station, 'd' => $t->delay];
                    $lastDelay = $t->delay;
                }
            }
            if (!$forward) {
                $stations = array_reverse($stations);
            }
            if ($stations[0]['d'] != 0) {
                $lastDelay = 0;
                foreach ($stations as $k => $v) {
                    $stations[$k]['d'] = $lastDelay;
                    $lastDelay = $v['d'];
                }
            }
            $this->trains[] = [
                'stations' => $stations,
                'direction' => $forward ? 'f' : 'b',
                'riders' => [],
                'dwell5points' => $p->dwell5points,
            ];
        }
    }

    public function advanceTrains() {
        foreach ($this->trains as $k => $v) {
            if ($v['stations'][0]['s'] == 22 && $v['dwell5points'] > 0) {
                $this->trains[$k]['dwell5points']--; // Subtract dwell at 5 points
            } elseif ($v['stations'][0]['d'] == 0) { // Advance a station
                //print_r($this->trains[$k]['stations'][0]);
                $passed = array_shift($this->trains[$k]['stations']);
                if (count($this->trains[$k]['stations']) == 0) {
                    if (count($this->trains[$k]['riders'])) {
                        $this->error('stranded rider!');
                        print_r($this->trains[$k]['riders']);
                    }
                    unset($this->trains[$k]);
                    continue;
                }
                if ($this->trains[$k]['stations'][0]['d'] == 0) {
                    $this->error('0 delay');
                    print_r($this->trains[$k]['stations']);
                }
                //print_r($this->trains[$k]['stations'][0]);
                //die;
            }
            $this->trains[$k]['stations'][0]['d'] -= 1; // Move the train forward between stations
        }
    }

    public function boardPeople() {
        foreach ($this->trains as $k => $v) {
            if ($v['stations'][0]['d'] == 0) {
                // Go through all the trains in stations
                $cur = $v['stations'][0]['s'];
                foreach ($this->stations[$cur]['people'] as $k2 => $p) {
                    // See if the people in that station are headed to a destination on that route
                    // TODO People should be able to board to 5 points
                    $canRide = false;
                    foreach ($v['stations'] as $s) {
                        if ($s['s'] == $p['dest']) {
                            $canRide = true;
                            break;
                        }
                    }
                    if ($canRide) {
                        // Board them on the train and get them off the platform
                        $this->trains[$k]['riders'][] = $p;
                        unset($this->stations[$cur]['people'][$k2]);
                    }
                }
            }
        }
    }

    public function offloadPeople($time, $minute) {
        foreach ($this->trains as $k => $v) {
            if ($v['stations'][0]['d'] == 0) {
                // Go through all the trains in stations
                $cur = $v['stations'][0]['s'];
                foreach ($v['riders'] as $k2 => $p) {
                    if ($cur == $p['dest']) {
                        // Rider has reached
                        $this->delay += $minute - $p['start'];
                        unset($this->trains[$k]['riders'][$k2]);
                    }
                    // TODO Something should send people into 5 points as needed
                }
            }
        }
    }

    public function peopleIntoStations($time, $minute) {

        $people = $this->chunksAll->where('entry_time', '>=', $time)
            ->where('entry_time', '<', $this->convertTime($minute+1))->get();
        foreach ($people as $p) {
            $this->stations[$p->entry_station]['people'][] = ['dest' => $p->exit_station, 'start' => $minute];
        }
    }

    public function report($minute) {
        $stations = [];
        foreach ($this->stations as $k => $v) {
            $stations[$k] = count($v['people']);
        }
        $trains = [];
        foreach ($this->trains as $k => $v) {
            if ($v['stations'][0]['d'] == 0) {
                $loc = $v['stations'][0]['s'];
            } else {
                $loc = $v['stations'][0]['s'].'-'.$v['direction'];
            }
            $trains[] = ['riders' => count($v['riders']), 'location' => $loc];
        }
        $this->report[$minute] = ['s' => $stations, 't' => $trains];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $report = true;
        ini_set('memory_limit','2048M');
        // Time 4am to 3am
        //1380
        for ($minute = 0; $minute < 100; $minute++) {
            $time = $this->convertTime($minute);
            $this->info("Minute: ".$minute." Current time: ".$time." Delay: ".$this->delay." ".count($this->trains));
            $this->advanceTrains();
            $this->placeTrains($time);
            //foreach ($this->trains as $k => $t) {
            //    $this->info($k.' '.$t['stations'][0]['s'].' '.$t['stations'][0]['d']);
            //}
            $this->boardPeople();
            $this->offloadPeople($time, $minute);
            $this->peopleIntoStations($time, $minute);
            if ($report) {
                $this->report($minute);
            }
        }

        if ($report) {
            $r = json_encode($this->report);
            echo $r;
            \Storage::disk('local')->put('report2.json', $r);
            file_put_contents(storage_path('report.json'), $r);
        }

        $this->info("Total Delay ". $this->delay." minutes!");
    }
}
