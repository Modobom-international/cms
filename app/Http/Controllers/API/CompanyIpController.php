<?php

namespace App\Http\Controllers\API;

use App\Models\CompanyIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyIpController extends Controller
{
    /**
     * List all company IPs
     */
    public function index()
    {
        $ips = CompanyIp::all();
        return response()->json($ips);
    }

    /**
     * Add a new company IP
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|ip|unique:company_ips,ip_address',
            'branch_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ip = CompanyIp::create($request->all());

        return response()->json([
            'message' => 'Company IP added successfully',
            'data' => $ip
        ], 201);
    }

    /**
     * Update a company IP
     */
    public function update(Request $request, $id)
    {
        $ip = CompanyIp::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|ip|unique:company_ips,ip_address,' . $id,
            'branch_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ip->update($request->all());

        return response()->json([
            'message' => 'Company IP updated successfully',
            'data' => $ip
        ]);
    }

    /**
     * Delete a company IP
     */
    public function destroy($id)
    {
        $ip = CompanyIp::findOrFail($id);
        $ip->delete();

        return response()->json([
            'message' => 'Company IP deleted successfully'
        ]);
    }
}