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
        $minute = 0;
        $hour = 4 + floor($minute/60);
        $minute = $minute % 60;
        if ($hour >= 24) {
            $hour -= 24;
        }

        return $hour.':'.$minute.':00';
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
            $trains = $this->stationOrder->where('line', $line)->orderBy('order')->get();
            $active = false;
            $stations = [];
            $first = false;
            $forward = true;
            foreach ($trains as $t) {
                if (!$active) {
                    if ($t->station == $p->startStation) {
                        $forward = true;
                        $active = true;
                        $first = true;
                    } elseif ($t->station == $p->endStation) {
                        $forward = false;
                        $active = true;
                        $first = true;
                    }
                }
                if ($active) {
                    $stations[] = ['s' => $t->station, 'd' => $first ? 0 : $t->delay];
                }
            }
            if (!$forward) {
                $stations = array_reverse($stations);
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
                $passed = array_shift($this->trains[$k]['stations']);
            }
            $this->trains[$k]['stations']['d'] -= 1; // Move the train forward between stations
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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit','2048M');
        // Time 4am to 3am
        for ($minute = 0; $minute < 1380; $minute++) {
            $time = $this->convertTime($minute);
            $this->advanceTrains();
            $this->placeTrains($time);
            $this->boardPeople();
            $this->offloadPeople($time, $minute);
            $this->peopleIntoStations($time, $minute);
            //$this->report();
        }

        $this->info("Total Delay ". $this->delay." minutes!");
    }
}
