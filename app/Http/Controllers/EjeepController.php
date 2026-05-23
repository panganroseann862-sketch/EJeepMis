<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEjeepRequest;
use App\Http\Requests\UpdateEjeepRequest;
use App\Models\Ejeep;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class EjeepController extends Controller
{
    public function index(): View
    {
        $ejeeps = Ejeep::withCount(['schedules', 'trips'])
            ->latest()
            ->get();
        
        return view('admin.ejeeps.index', compact('ejeeps'));
    }

    public function create(): View
    {
        return view('admin.ejeeps.create');
    }

    public function store(StoreEjeepRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            
            Ejeep::create($validated);
            
            return redirect()->route('admin.ejeeps.index')
                ->with('success', 'E-Jeep created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create E-Jeep', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create E-Jeep. Please try again.');
        }
    }

    public function show(Ejeep $ejeep): View
    {
        $ejeep->load(['schedules.route', 'schedules.driver', 'trips' => function ($query) {
            $query->latest()->limit(10);
        }]);
        
        return view('admin.ejeeps.show', compact('ejeep'));
    }

    public function edit(Ejeep $ejeep): View
    {
        return view('admin.ejeeps.edit', compact('ejeep'));
    }

    public function update(UpdateEjeepRequest $request, Ejeep $ejeep): RedirectResponse
    {
        try {
            $validated = $request->validated();
            
            $ejeep->update($validated);
            
            return redirect()->route('admin.ejeeps.index')
                ->with('success', 'E-Jeep updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update E-Jeep', [
                'ejeep_id' => $ejeep->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update E-Jeep. Please try again.');
        }
    }

    public function destroy(Ejeep $ejeep): RedirectResponse
    {
        try {
            $ejeep->delete();
            
            return redirect()->route('admin.ejeeps.index')
                ->with('success', 'E-Jeep deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete E-Jeep', [
                'ejeep_id' => $ejeep->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to delete E-Jeep. It may be assigned to active schedules.');
        }
    }
}