<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompositionDetailedResource;
use App\Http\Resources\SimpleCompositionResource;
use Illuminate\Http\Request;
use App\Models\Composition;
use App\Models\User;  
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class CommunityCompController extends Controller
{
    public function index(Request $request)
    {
        try {
            $synergyName = $request->query('synergy');
            $sortBy = $request->query('sort_by');

            $compositions = Composition::getCompositions(null, $synergyName, null, $sortBy, true, false);
    
            if ($compositions->isEmpty()) {
                return response()->json([
                    'message' => 'No published compositions found with the provided filters.'
                ], 404);
            }
    
            return CompositionDetailedResource::collection($compositions);
    
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        try {
            $composition = Composition::where('id', $id)
                ->where('type', 'publish')
                ->firstOrFail();

            $formations = $composition->formations()->with('items')->get();

            $formationsData = $formations->map(function ($formation) {
                return [
                    'champion_id' => $formation->champion_id,
                    'item_ids' => $formation->items->pluck('id')->toArray(),
                ];
            })->toArray();

            $synergies = Composition::calculateSynergyActivation($formationsData);

            return (new CompositionDetailedResource($composition))
                ->additional(['synergies_activation' => $synergies]);
    
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function toggleLike(Request $request, $id)
    {
        try {
            $action = $request->input('action');

            $composition = Composition::findOrFail($id);
            $user = Auth::user();
    
            if (!$user instanceof User) {
                return response()->json([
                    'message' => 'Invalid user instance.'
                ], 400);
            }
    
            $user->toggleAction($composition, $action);
    
            return response()->json([
                'message' => 'Like status updated successfully.',
                'likes' => $composition->likes
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function generateCommunityCompPDF(Request $request, $id)
    {
        try {
            $comp = Composition::getCommunityComposition(null, null)
                ->find($id);
            
            if (!$comp) {
                return response()->json([
                    'error' => 'Composition not found'
                ], 404);
            }
    
            $data = [
                'title' => $comp,
                'date' => date('m/d/Y'),
            ];
    
            $pdf = Pdf::loadView('pdf.communityCompPDF', $data);
    
            return $pdf->download('mi-archivo.pdf');
            
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

}
