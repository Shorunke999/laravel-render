<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email',
                'message' => 'required|string|max:10000',
            ]);

            Feedback::create([
                'name' => $validatedData['first_name']. ' '.$validatedData['last_name'],
                'email' => $validatedData['email'],
                'message' => $validatedData['message']
            ]);
            return response()->json([
                'status' => true,
                'message' => "Feedback save successfully. Thanks for reaching out"
               ],200);
        } catch (\Exception $th) {
           return response()->json([
            'status' => false,
            'message' => "Error " . $th->getMessage()
           ],422);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Feedback $feedback)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Feedback $feedback)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Feedback $feedback)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Feedback $feedback)
    {
        //
    }
}
