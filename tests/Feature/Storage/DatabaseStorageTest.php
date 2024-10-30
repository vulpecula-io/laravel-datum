<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Vulpecula\Datum\Enums\Period;
use Vulpecula\Datum\Facades\Datum;

test('aggregation', function () {
    Datum::record('type', 'key1', 200)->count()->min()->max()->sum()->avg();
    Datum::record('type', 'key1', 100)->count()->min()->max()->sum()->avg();
    Datum::record('type', 'key2', 400)->count()->min()->max()->sum()->avg();
    Datum::ingest();

    $entries = Datum::ignore(fn () => DB::table('datum_entries')->orderBy('id')->get());
    expect($entries)
        ->toHaveCount(3)
        ->and($entries[0])
        ->toHaveProperties(['type' => 'type', 'key' => 'key1', 'value' => 200])
        ->and($entries[1])
        ->toHaveProperties(['type' => 'type', 'key' => 'key1', 'value' => 100])
        ->and($entries[2])
        ->toHaveProperties(['type' => 'type', 'key' => 'key2', 'value' => 400]);

    $aggregates = Datum::ignore(fn () => DB::table('datum_aggregates')->orderBy('period')->orderBy('aggregate')->orderBy('key')->get());
    expect($aggregates)
        ->toHaveCount(2 * 5 * 10)
        ->and($aggregates[0])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 150])
        ->and($aggregates[1])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[2])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'count', 'key' => 'key1', 'value' => 2])
        ->and($aggregates[3])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[4])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'max', 'key' => 'key1', 'value' => 200])
        ->and($aggregates[5])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[6])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[7])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[8])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[9])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[10])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 150])
        ->and($aggregates[11])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[12])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'count', 'key' => 'key1', 'value' => 2])
        ->and($aggregates[13])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[14])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'max', 'key' => 'key1', 'value' => 200])
        ->and($aggregates[15])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[16])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[17])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[18])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[19])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[20])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 150])
        ->and($aggregates[21])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[22])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'count', 'key' => 'key1', 'value' => 2])
        ->and($aggregates[23])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[24])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'max', 'key' => 'key1', 'value' => 200])
        ->and($aggregates[25])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[26])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[27])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[28])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[29])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[30])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 150])
        ->and($aggregates[31])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[32])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'count', 'key' => 'key1', 'value' => 2])
        ->and($aggregates[33])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[34])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'max', 'key' => 'key1', 'value' => 200])
        ->and($aggregates[35])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[36])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[37])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[38])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[39])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[40])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 150])
        ->and($aggregates[41])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[42])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'count', 'key' => 'key1', 'value' => 2])
        ->and($aggregates[43])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[44])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'max', 'key' => 'key1', 'value' => 200])
        ->and($aggregates[45])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[46])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[47])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[48])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[49])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[50])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 150])
        ->and($aggregates[51])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[52])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'count', 'key' => 'key1', 'value' => 2])
        ->and($aggregates[53])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[54])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'max', 'key' => 'key1', 'value' => 200])
        ->and($aggregates[55])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[56])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[57])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[58])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[59])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[60])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 150])
        ->and($aggregates[61])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[62])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'count', 'key' => 'key1', 'value' => 2])
        ->and($aggregates[63])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[64])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'max', 'key' => 'key1', 'value' => 200])
        ->and($aggregates[65])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[66])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[67])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[68])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[69])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[70])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 150])
        ->and($aggregates[71])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[72])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'count', 'key' => 'key1', 'value' => 2])
        ->and($aggregates[73])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[74])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'max', 'key' => 'key1', 'value' => 200])
        ->and($aggregates[75])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[76])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[77])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[78])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[79])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[80])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 150])
        ->and($aggregates[81])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[82])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'count', 'key' => 'key1', 'value' => 2])
        ->and($aggregates[83])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[84])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'max', 'key' => 'key1', 'value' => 200])
        ->and($aggregates[85])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[86])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[87])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[88])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[89])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[90])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 150])
        ->and($aggregates[91])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[92])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'count', 'key' => 'key1', 'value' => 2])
        ->and($aggregates[93])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[94])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'max', 'key' => 'key1', 'value' => 200])
        ->and($aggregates[95])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[96])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[97])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[98])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[99])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400]);

    Datum::record('type', 'key1', 600)->count()->min()->max()->sum()->avg();
    Datum::ingest();

    $entries = Datum::ignore(fn () => DB::table('datum_entries')->orderBy('id')->get());
    expect($entries)
        ->toHaveCount(4)
        ->and($entries[0])
        ->toHaveProperties(['type' => 'type', 'key' => 'key1', 'value' => 200])
        ->and($entries[1])
        ->toHaveProperties(['type' => 'type', 'key' => 'key1', 'value' => 100])
        ->and($entries[2])
        ->toHaveProperties(['type' => 'type', 'key' => 'key2', 'value' => 400])
        ->and($entries[3])
        ->toHaveProperties(['type' => 'type', 'key' => 'key1', 'value' => 600]);

    $aggregates = Datum::ignore(fn () => DB::table('datum_aggregates')->orderBy('period')->orderBy('aggregate')->orderBy('key')->get());
    expect($aggregates)
        ->toHaveCount(2 * 5 * 10)
        ->and($aggregates[0])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[1])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[2])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'count', 'key' => 'key1', 'value' => 3])
        ->and($aggregates[3])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[4])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'max', 'key' => 'key1', 'value' => 600])
        ->and($aggregates[5])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[6])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[7])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[8])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 900])
        ->and($aggregates[9])
        ->toHaveProperties(['type' => 'type', 'period' => 60, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[10])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[11])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[12])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'count', 'key' => 'key1', 'value' => 3])
        ->and($aggregates[13])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[14])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'max', 'key' => 'key1', 'value' => 600])
        ->and($aggregates[15])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[16])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[17])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[18])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 900])
        ->and($aggregates[19])
        ->toHaveProperties(['type' => 'type', 'period' => 360, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[20])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[21])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[22])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'count', 'key' => 'key1', 'value' => 3])
        ->and($aggregates[23])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[24])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'max', 'key' => 'key1', 'value' => 600])
        ->and($aggregates[25])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[26])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[27])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[28])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 900])
        ->and($aggregates[29])
        ->toHaveProperties(['type' => 'type', 'period' => 720, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[30])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[31])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[32])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'count', 'key' => 'key1', 'value' => 3])
        ->and($aggregates[33])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[34])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'max', 'key' => 'key1', 'value' => 600])
        ->and($aggregates[35])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[36])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[37])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[38])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 900])
        ->and($aggregates[39])
        ->toHaveProperties(['type' => 'type', 'period' => 1440, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[40])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[41])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[42])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'count', 'key' => 'key1', 'value' => 3])
        ->and($aggregates[43])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[44])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'max', 'key' => 'key1', 'value' => 600])
        ->and($aggregates[45])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[46])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[47])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[48])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 900])
        ->and($aggregates[49])
        ->toHaveProperties(['type' => 'type', 'period' => 10080, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[50])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[51])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[52])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'count', 'key' => 'key1', 'value' => 3])
        ->and($aggregates[53])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[54])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'max', 'key' => 'key1', 'value' => 600])
        ->and($aggregates[55])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[56])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[57])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[58])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 900])
        ->and($aggregates[59])
        ->toHaveProperties(['type' => 'type', 'period' => 43200, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[60])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[61])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[62])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'count', 'key' => 'key1', 'value' => 3])
        ->and($aggregates[63])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[64])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'max', 'key' => 'key1', 'value' => 600])
        ->and($aggregates[65])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[66])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[67])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[68])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 900])
        ->and($aggregates[69])
        ->toHaveProperties(['type' => 'type', 'period' => 131400, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[70])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[71])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[72])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'count', 'key' => 'key1', 'value' => 3])
        ->and($aggregates[73])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[74])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'max', 'key' => 'key1', 'value' => 600])
        ->and($aggregates[75])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[76])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[77])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[78])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 900])
        ->and($aggregates[79])
        ->toHaveProperties(['type' => 'type', 'period' => 262800, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[80])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[81])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[82])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'count', 'key' => 'key1', 'value' => 3])
        ->and($aggregates[83])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[84])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'max', 'key' => 'key1', 'value' => 600])
        ->and($aggregates[85])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[86])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[87])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[88])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 900])
        ->and($aggregates[89])
        ->toHaveProperties(['type' => 'type', 'period' => 524160, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[90])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'avg', 'key' => 'key1', 'value' => 300])
        ->and($aggregates[91])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'avg', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[92])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'count', 'key' => 'key1', 'value' => 3])
        ->and($aggregates[93])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'count', 'key' => 'key2', 'value' => 1])
        ->and($aggregates[94])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'max', 'key' => 'key1', 'value' => 600])
        ->and($aggregates[95])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'max', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[96])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'min', 'key' => 'key1', 'value' => 100])
        ->and($aggregates[97])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'min', 'key' => 'key2', 'value' => 400])
        ->and($aggregates[98])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'sum', 'key' => 'key1', 'value' => 900])
        ->and($aggregates[99])
        ->toHaveProperties(['type' => 'type', 'period' => 525600, 'aggregate' => 'sum', 'key' => 'key2', 'value' => 400]);

});

it('combines duplicate count aggregates before upserting', function () {
    $queries = collect();
    DB::listen(function (QueryExecuted $event) use (&$queries) {
        if (str_starts_with($event->sql, 'insert')) {
            $queries[] = $event;
        }
    });

    Datum::record('type', 'key1')->count();
    Datum::record('type', 'key1')->count();
    Datum::record('type', 'key1')->count();
    Datum::record('type', 'key2')->count();
    Datum::ingest();

    expect($queries)
        ->toHaveCount(2)
        ->and($queries[0]->sql)
        ->toContain('datum_entries')
        ->and($queries[1]->sql)
        ->toContain('datum_aggregates');
    if ('sqlite' === DB::connection()->getDriverName()) {
        expect($queries[0]->bindings)
            ->toHaveCount(4 * 5)
            ->and($queries[1]->bindings)
            ->toHaveCount(2 * 7 * 10); // 4 entries, 5 columns each
        // 2 entries, 7 columns each, 4 periods
    } else {
        expect($queries[0]->bindings)
            ->toHaveCount(4 * 4)
            ->and($queries[1]->bindings)
            ->toHaveCount(2 * 6 * 10); // 4 entries, 4 columns each
        // 2 entries, 6 columns each, 4 periods
    }

    $aggregates = Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 60)->orderBy('key')->pluck('value', 'key'));
    expect($aggregates['key1'])
        ->toEqual(3)
        ->and($aggregates['key2'])
        ->toEqual(1);
});

it('combines duplicate min aggregates before upserting', function () {
    $queries = collect();
    DB::listen(function (QueryExecuted $event) use (&$queries) {
        if (str_starts_with($event->sql, 'insert')) {
            $queries[] = $event;
        }
    });

    Datum::record('type', 'key1', 200)->min();
    Datum::record('type', 'key1', 100)->min();
    Datum::record('type', 'key1', 300)->min();
    Datum::record('type', 'key2', 100)->min();
    Datum::ingest();

    expect($queries)
        ->toHaveCount(2)
        ->and($queries[0]->sql)
        ->toContain('datum_entries')
        ->and($queries[1]->sql)
        ->toContain('datum_aggregates');
    if ('sqlite' === DB::connection()->getDriverName()) {
        expect($queries[0]->bindings)
            ->toHaveCount(4 * 5)
            ->and($queries[1]->bindings)
            ->toHaveCount(2 * 7 * 10); // 4 entries, 5 columns each
        // 2 entries, 7 columns each, 4 periods
    } else {
        expect($queries[0]->bindings)
            ->toHaveCount(4 * 4)
            ->and($queries[1]->bindings)
            ->toHaveCount(2 * 6 * 10); // 4 entries, 4 columns each
        // 2 entries, 6 columns each, 4 periods
    }

    $aggregates = Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 60)->orderBy('key')->pluck('value', 'key'));
    expect($aggregates['key1'])
        ->toEqual(100)
        ->and($aggregates['key2'])
        ->toEqual(100);
});

it('combines duplicate max aggregates before upserting', function () {
    $queries = collect();
    DB::listen(function (QueryExecuted $event) use (&$queries) {
        if (str_starts_with($event->sql, 'insert')) {
            $queries[] = $event;
        }
    });

    Datum::record('type', 'key1', 100)->max();
    Datum::record('type', 'key1', 300)->max();
    Datum::record('type', 'key1', 200)->max();
    Datum::record('type', 'key2', 100)->max();
    Datum::ingest();

    expect($queries)
        ->toHaveCount(2)
        ->and($queries[0]->sql)
        ->toContain('datum_entries')
        ->and($queries[1]->sql)
        ->toContain('datum_aggregates');
    if ('sqlite' === DB::connection()->getDriverName()) {
        expect($queries[0]->bindings)
            ->toHaveCount(4 * 5)
            ->and($queries[1]->bindings)
            ->toHaveCount(2 * 7 * 10); // 4 entries, 5 columns each
        // 2 entries, 7 columns each, 4 periods
    } else {
        expect($queries[0]->bindings)
            ->toHaveCount(4 * 4)
            ->and($queries[1]->bindings)
            ->toHaveCount(2 * 6 * 10); // 4 entries, 4 columns each
        // 2 entries, 6 columns each, 4 periods
    }

    $aggregates = Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 60)->orderBy('key')->pluck('value', 'key'));
    expect($aggregates['key1'])
        ->toEqual(300)
        ->and($aggregates['key2'])
        ->toEqual(100);
});

it('combines duplicate sum aggregates before upserting', function () {
    $queries = collect();
    DB::listen(function (QueryExecuted $event) use (&$queries) {
        if (str_starts_with($event->sql, 'insert')) {
            $queries[] = $event;
        }
    });

    Datum::record('type', 'key1', 100)->sum();
    Datum::record('type', 'key1', 300)->sum();
    Datum::record('type', 'key1', 200)->sum();
    Datum::record('type', 'key2', 100)->sum();
    Datum::ingest();

    expect($queries)
        ->toHaveCount(2)
        ->and($queries[0]->sql)
        ->toContain('datum_entries')
        ->and($queries[1]->sql)
        ->toContain('datum_aggregates');
    if ('sqlite' === DB::connection()->getDriverName()) {
        expect($queries[0]->bindings)
            ->toHaveCount(4 * 5)
            ->and($queries[1]->bindings)
            ->toHaveCount(2 * 7 * 10); // 4 entries, 5 columns each
        // 2 entries, 7 columns each, 4 periods
    } else {
        expect($queries[0]->bindings)
            ->toHaveCount(4 * 4)
            ->and($queries[1]->bindings)
            ->toHaveCount(2 * 6 * 10); // 4 entries, 4 columns each
        // 2 entries, 6 columns each, 4 periods
    }

    $aggregates = Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 60)->orderBy('key')->pluck('value', 'key'));
    expect($aggregates['key1'])
        ->toEqual(600)
        ->and($aggregates['key2'])
        ->toEqual(100);
});

it('combines duplicate average aggregates before upserting', function () {
    $queries = collect();
    DB::listen(function (QueryExecuted $event) use (&$queries) {
        if (str_starts_with($event->sql, 'insert')) {
            $queries[] = $event;
        }
    });

    Datum::record('type', 'key1', 100)->avg();
    Datum::record('type', 'key1', 300)->avg();
    Datum::record('type', 'key1', 200)->avg();
    Datum::record('type', 'key2', 100)->avg();
    Datum::ingest();

    expect($queries)
        ->toHaveCount(2)
        ->and($queries[0]->sql)
        ->toContain('datum_entries')
        ->and($queries[1]->sql)
        ->toContain('datum_aggregates');
    if ('sqlite' === DB::connection()->getDriverName()) {
        expect($queries[0]->bindings)
            ->toHaveCount(4 * 5)
            ->and($queries[1]->bindings)
            ->toHaveCount(2 * 8 * 10); // 4 entries, 5 columns each
        // 2 entries, 8 columns each, 4 periods
    } else {
        expect($queries[0]->bindings)
            ->toHaveCount(4 * 4)
            ->and($queries[1]->bindings)
            ->toHaveCount(2 * 7 * 10); // 4 entries, 4 columns each
        // 2 entries, 7 columns each, 4 periods
    }

    $aggregates = Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 60)->orderBy('key')->get())->keyBy('key');
    expect($aggregates['key1']->value)
        ->toEqual(200)
        ->and($aggregates['key2']->value)
        ->toEqual(100)
        ->and($aggregates['key1']->count)
        ->toEqual(3)
        ->and($aggregates['key2']->count)
        ->toEqual(1);

    Datum::record('type', 'key1', 400)->avg();
    Datum::record('type', 'key1', 400)->avg();
    Datum::record('type', 'key1', 400)->avg();
    Datum::ingest();
    $aggregate = Datum::ignore(fn () => DB::table('datum_aggregates')->where('period', 60)->where('key', 'key1')->first());
    expect($aggregate->count)
        ->toEqual(6)
        ->and($aggregate->value)
        ->toEqual(300);
});

test('one or more aggregates for a single type', function () {
    /*
    | key      | min | max | sum  | avg | count |
    |----------|-----|-----|------|-----|-------|
    | GET /bar | 200 | 600 | 2400 | 400 | 6     |
    | GET /foo | 100 | 300 | 2000 | 200 | 6     |
    */

    // Add entries outside of the window
    Carbon::setTestNow('2000-01-01 11:59:59');
    Datum::record('slow_request', 'GET /foo', 100)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /bar', 200)->min()->max()->sum()->avg()->count();

    // Add entries to the "tail"
    Carbon::setTestNow('2000-01-01 12:00:01');
    Datum::record('slow_request', 'GET /foo', 100)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /foo', 200)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /foo', 300)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /foo', 400)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /bar', 200)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /bar', 400)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /bar', 600)->min()->max()->sum()->avg()->count();

    // Add entries to the current buckets.
    Carbon::setTestNow('2000-01-01 12:59:59');
    Datum::record('slow_request', 'GET /foo', 100)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /foo', 200)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /foo', 300)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /foo', 400)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /bar', 200)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /bar', 400)->min()->max()->sum()->avg()->count();
    Datum::record('slow_request', 'GET /bar', 600)->min()->max()->sum()->avg()->count();

    ray(Datum::ingest());

    Carbon::setTestNow('2000-01-01 13:00:00');

    $results = Datum::aggregate('slow_request', 'count', Period::HOUR);

    expect($results->all())->toEqual([
        (object) ['key' => 'GET /foo', 'count' => 8],
        (object) ['key' => 'GET /bar', 'count' => 6],
    ]);

    $results = Datum::aggregate('slow_request', ['min', 'max', 'sum', 'avg', 'count'], Period::HOUR);

    expect($results->all())->toEqual([
        (object) ['key' => 'GET /bar', 'min' => 200, 'max' => 600, 'sum' => 2400, 'avg' => 400, 'count' => 6],
        (object) ['key' => 'GET /foo', 'min' => 100, 'max' => 400, 'sum' => 2000, 'avg' => 250, 'count' => 8],
    ]);
});

test('one aggregate for multiple types, per key', function () {
    /*
    | key      | cache_hit | cache_miss |
    |----------|-----------|------------|
    | flight:* | 16        | 8          |
    | user:*   | 4         | 2          |
    */

    // Add entries outside of the window
    Carbon::setTestNow('2000-01-01 11:59:59');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'user:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();
    Datum::record('cache_miss', 'user:*')->count();

    // Add entries to the "tail"
    Carbon::setTestNow('2000-01-01 12:00:00');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();
    Datum::record('cache_hit', 'user:*')->count();
    Datum::record('cache_hit', 'user:*')->count();
    Datum::record('cache_miss', 'user:*')->count();

    // Add entries to the current buckets.
    Carbon::setTestNow('2000-01-01 12:59:59');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();
    Datum::record('cache_hit', 'user:*')->count();
    Datum::record('cache_hit', 'user:*')->count();
    Datum::record('cache_miss', 'user:*')->count();

    Datum::ingest();

    Carbon::setTestNow('2000-01-01 13:00:00');

    $results = Datum::aggregateTypes(['cache_hit', 'cache_miss'], 'count', Period::HOUR);

    expect($results->all())->toEqual([
        (object) ['key' => 'flight:*', 'cache_hit' => 8, 'cache_miss' => 6],
        (object) ['key' => 'user:*', 'cache_hit' => 4, 'cache_miss' => 2],
    ]);
});

test('total aggregate for a single type', function () {
    // Add entries outside of the window
    Carbon::setTestNow('2000-01-01 11:59:59');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();

    // Add entries to the "tail"
    Carbon::setTestNow('2000-01-01 12:00:00');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Carbon::setTestNow('2000-01-01 12:00:02');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Carbon::setTestNow('2000-01-01 12:00:03');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();

    // Add entries to the current buckets.
    Carbon::setTestNow('2000-01-01 12:59:00');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Carbon::setTestNow('2000-01-01 12:59:10');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Carbon::setTestNow('2000-01-01 12:59:20');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();

    Datum::ingest();

    Carbon::setTestNow('2000-01-01 13:00:00');

    $total = Datum::aggregateTotal('cache_hit', 'count', Period::HOUR);

    expect($total)->toEqual(12);
});

test('total aggregate for multiple types', function () {
    /*
    | type       | count |
    |------------|-------|
    | cache_hit  | 12    |
    | cache_miss | 6     |
    */

    // Add entries outside of the window
    Carbon::setTestNow('2000-01-01 11:59:59');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();

    // Add entries to the "tail"
    Carbon::setTestNow('2000-01-01 12:00:00');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();
    Carbon::setTestNow('2000-01-01 12:00:02');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();
    Carbon::setTestNow('2000-01-01 12:00:03');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();

    // Add entries to the current buckets.
    Carbon::setTestNow('2000-01-01 12:59:00');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();
    Carbon::setTestNow('2000-01-01 12:59:10');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();
    Carbon::setTestNow('2000-01-01 12:59:20');
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_hit', 'flight:*')->count();
    Datum::record('cache_miss', 'flight:*')->count();

    Datum::ingest();

    Carbon::setTestNow('2000-01-01 13:00:00');

    $results = Datum::aggregateTotal(['cache_hit', 'cache_miss'], 'count', Period::HOUR);

    expect($results->all())->toEqual([
        'cache_hit' => 12,
        'cache_miss' => 6,
    ]);
});

it('collapses values with the same key into a single upsert', function () {
    $bindings = [];
    DB::listen(function (QueryExecuted $event) use (&$bindings) {
        if (str_starts_with($event->sql, 'insert')) {
            $bindings = $event->bindings;
        }
    });

    Datum::set('read_counter', 'post:321', 123);
    Datum::set('read_counter', 'post:321', 234);
    Datum::set('read_counter', 'post:321', 345);
    Datum::ingest();

    expect($bindings)->not->toContain(123)
        ->and($bindings)->not->toContain(234)
        ->and($bindings)
        ->toContain('345');
    $values = Datum::ignore(fn () => DB::table('datum_values')->get());
    expect($values)
        ->toHaveCount(1)
        ->and($values[0]->value)
        ->toBe('345');
});
