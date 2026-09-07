<?php

namespace App\Http\Controllers\Admin;

use App\Actions\MoveEntrantToHeat;
use App\Actions\SwapHeatLanes;
use App\Exceptions\CannotAdjustHeatLaneException;
use App\Http\Controllers\Controller;
use App\Models\Heat;
use App\Models\HeatLane;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HeatLaneController extends Controller
{
    public function swap(Request $request, SwapHeatLanes $swap): RedirectResponse
    {
        $left = HeatLane::query()->with('heat.event.competition')->findOrFail($request->integer('left_lane_id'));
        $right = HeatLane::query()->findOrFail($request->integer('right_lane_id'));
        $competition = $left->heat?->event?->competition;
        abort_unless($competition !== null, 404);
        $this->authorize('seed', $competition);

        try {
            $swap->handle($left, $right, $request->user(), $request->ip());
        } catch (CannotAdjustHeatLaneException $exception) {
            return back()->withErrors(['heat_lane' => $exception->getMessage()]);
        }

        return back()->with('status', 'Lintasan ditukar.');
    }

    public function move(Request $request, HeatLane $heatLane, MoveEntrantToHeat $move): RedirectResponse
    {
        $targetHeat = Heat::query()->findOrFail($request->integer('target_heat_id'));
        $competition = $heatLane->heat?->event?->competition;
        abort_unless($competition !== null, 404);
        $this->authorize('seed', $competition);

        try {
            $move->handle(
                $heatLane,
                $targetHeat,
                $request->integer('target_lane_number'),
                $request->user(),
                $request->ip(),
            );
        } catch (CannotAdjustHeatLaneException $exception) {
            return back()->withErrors(['heat_lane' => $exception->getMessage()]);
        }

        return back()->with('status', 'Peserta dipindahkan.');
    }

    public function withdraw(Request $request, HeatLane $heatLane, MoveEntrantToHeat $move): RedirectResponse
    {
        $competition = $heatLane->heat?->event?->competition;
        abort_unless($competition !== null, 404);
        $this->authorize('seed', $competition);

        $move->withdraw($heatLane, $request->user(), $request->ip());

        return back()->with('status', 'Peserta dikeluarkan dari lintasan.');
    }
}
