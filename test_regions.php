<?php

use App\Models\Region;

$regions = Region::select('code', 'name')->orderBy('name')->get();
echo 'Regions count: '.count($regions)."\n";
if (count($regions) > 0) {
    echo 'First region: '.json_encode($regions[0]->toArray())."\n";
}
