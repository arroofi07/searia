<?php

use App\Services\AthleteMatcher;

it('detects similar names used in import warning W-03', function () {
    $matcher = new AthleteMatcher(0.78);

    expect($matcher->areSimilar('SEARIA AQUATIC PADANG', 'Searia Aquatic Pdg'))->toBeTrue();
});
