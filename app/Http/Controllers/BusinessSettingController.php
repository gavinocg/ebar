<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BusinessSettingController extends Controller
{
    public function index()
    {
        $settings = BusinessSetting::first();
        return view('settings.business', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'rfc' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'ticket_message' => 'nullable|string',
            'charge_tax' => 'nullable|boolean',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $settings = BusinessSetting::firstOrNew();
        
        $settings->business_name = $request->business_name;
        $settings->rfc = $request->rfc;
        $settings->phone = $request->phone;
        $settings->address = $request->address;
        $settings->ticket_message = $request->ticket_message;
        $settings->charge_tax = $request->charge_tax == '1';
        $settings->tax_percentage = $request->tax_percentage ?? 16.00;

        if ($request->hasFile('logo')) {
            if ($settings->logo) {
                Storage::disk('public')->delete($settings->logo);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $settings->logo = $path;
        }

        $settings->save();

        return redirect()->route('settings.business')->with('success', 'Configuración actualizada');
    }
}
