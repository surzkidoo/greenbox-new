<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GenericController extends Controller
{
    /**
     * Get all states.
     */
    public function getStates()
    {
        $states = DB::table('states')->select('id', 'state_name')->get();

        if ($states->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No states found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $states]);
    }

    /**
     * Get LGAs based on state ID or name.
     */
    public function getLgasByState($state)
    {
        // Check if $state is numeric (ID) or a string (state name)

        $stateData = is_numeric($state)
            ? DB::table('states')->where('id', $state)->first()
            : DB::table('states')->where('state_name', $state)->first();

        if (!$stateData) {
            return response()->json(['status' => 'error', 'message' => 'State not found'], 404);
        }

        $lgas = DB::table('local_government_areas')
            ->where('state', $stateData->state_name)
            ->select('id', 'lga_name')
            ->get();

        if ($lgas->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No LGAs found for this state'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $lgas]);
    }
}
