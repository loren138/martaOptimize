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

    public $ewStations = [13,21,23,24,22,25,26,27,28,29,30,31,32,33,34,35];
    public $nsStations = [18,19,20,22,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54];
    public $redOnly = [54, 18, 53, 52, 51];

    // Lindberg Transfer
    // Bankhead Transfer

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
                    $canRide = false;
                    $dest = $p['dest'];
                    if ($p['fivePoints']) {
                        $dest = 22;
                    }
                    foreach ($v['stations'] as $s) {
                        if ($k2 == 13) {
                            // There is only one train at Bankhead board it
                            $canRide = true;
                            $p['ashby'] = true;
                        }
                        if (in_array($k2, $this->redOnly) && !in_array($p['dest'], $this->redOnly) && $s['s'] == 47) {
                            // If we're at a red only station going to not red only, we need to go to Lindbergh
                            $canRide = true;
                            $p['lindbergh'] = true;
                        }
                        if (!in_array($k2, $this->redOnly) && in_array($p['dest'], $this->redOnly) && $s['s'] == 47) {
                            // If we're going to a red only station and not at one, we need to get to Lindbergh
                            // TODO Get off at Lindbergh if needed
                            $canRide = true;
                            $p['lindbergh'] = true;
                        }
                        if (in_array($k2, $this->ewStations) && $p['dest'] == 13 && $s['s'] == 21) {
                            // We're going to Bankhead and need to get to ashby
                            // TODO Get off at ashby if needed
                            $canRide = true;
                            $p['ashby'] = true;
                        }
                        if ($p['dest'] == 42 && $p['startStation'] == 43 && $canRide) {
                            $this->error('h');
                            var_export($p);
                        }

                        if ($s['s'] == $dest) {
                            $canRide = true;
                            $p['lindbergh'] = false;
                            $p['ashby'] = false;
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
                    $dest = $p['dest'];
                    if ($p['fivePoints']) {
                        $dest = 22;
                    }
                    if ($p['lindbergh']) {
                        $dest = 47;
                    }
                    if ($p['ashby']) {
                        $dest = 21;
                    }
                    if ($cur == $dest) {
                        if ($p['fivePoints'] && $dest == 22) {
                            // Transfer
                            $p['fivePoints'] = false;
                            unset($this->trains[$k]['riders'][$k2]);
                            $this->stations[22]['people'][] = $p;
                        } elseif ($p['lindbergh'] && $dest == 47) {
                            // Transfer
                            $p['lindbergh'] = false;
                            unset($this->trains[$k]['riders'][$k2]);
                            $this->stations[47]['people'][] = $p;
                        } elseif ($p['ashby'] && $dest == 21) {
                            // Transfer
                            $p['ashby'] = false;
                            unset($this->trains[$k]['riders'][$k2]);
                            $this->stations[21]['people'][] = $p;
                        } else {
                            // Rider has reached
                            $this->delay += $minute - $p['start'];
                            if ($this->delay > 200) {
                                print_r($p);
                            }
                            unset($this->trains[$k]['riders'][$k2]);
                        }
                    }
                    // TODO Delay 2 minutes at five points?
                }
                if (count($v['stations']) == 1) {
                    // Offload all they are riding to transfer
                    foreach ($v['riders'] as $k2 => $p) {
                        // Transfer
                        $p['fivePoints'] = false;
                        unset($this->trains[$k]['riders'][$k2]);
                        $this->stations[22]['people'][] = $p;
                    }
                }
            }
        }
    }

    public function peopleIntoStations($time, $minute) {

        $people = $this->chunksAll->where('entry_time', '>=', $time)->where('dayOfWeek', 'weekday')
            ->where('entry_time', '<', $this->convertTime($minute+1))->get();
        foreach ($people as $p) {
            if ($p->entry_station == $p->exit_station) {
                continue;
            }
            $fivePoints = false;
            if (in_array($p->entry_station, $this->nsStations)) {
                if (!in_array($p->exit_station, $this->nsStations)) {
                    $fivePoints = true;
                } else {
                    $fivePoints = false;
                }
            }
            if (in_array($p->entry_station, $this->ewStations)) {
                if (!in_array($p->exit_station, $this->ewStations)) {
                    $fivePoints = true;
                } else {
                    $fivePoints = false;
                }
            }
            if ($p->entry_station == 22 || $p->exit_station == 22) {
                $fivePoints = false;
            }
            $this->stations[$p->entry_station]['people'][] = [
                'dest' => $p->exit_station, 'start' => $minute, 'fivePoints' => $fivePoints,
                'lindbergh' => false, 'ashby' => false, 'startStation' => $p->entry_station
            ]; //32,110,077
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
        for ($minute = 0; $minute < 1380; $minute++) {
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
            file_put_contents(storage_path('report.json'), $r);
        }

        $this->info("Total Delay ". $this->delay." minutes!");
    }
}
