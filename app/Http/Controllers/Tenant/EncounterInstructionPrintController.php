<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\EncounterInstruction;
use App\Models\Tenant\FollowUp;
use Illuminate\Support\Facades\Auth;

class EncounterInstructionPrintController extends Controller
{
    public function __invoke(int $encounterId)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            abort(403);
        }

        $instruction = EncounterInstruction::on('tenant')
            ->with(['patient', 'encounter'])
            ->where('encounter_id', $encounterId)
            ->firstOrFail();

        $followUp = FollowUp::on('tenant')
            ->where('encounter_id', $encounterId)
            ->whereIn('status', ['pending', 'booked'])
            ->first();

        return view('print.encounter-instructions', [
            'instruction' => $instruction,
            'followUp'    => $followUp,
        ]);
    }
}
