<?php

namespace App\Http\Controllers;

use App\Http\Resources\FeedbackResource;
use App\Models\Artwork;
use App\Models\Feedback;
use App\Models\Order;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query =  Feedback::query();
        // Price range filter
        if ($request->has('read') ) {
            $query->where('read', $request->input('read'));
        }
        if ($request->has('story_title') ) {
            $query->where('story_title', $request->input('story_title'));
        }
        $feedbacks = $query->paginate($request->input('per_page', 12));

        return response()->json([
            'feedback' => FeedbackResource::collection($feedbacks)
        ]);
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
                'story_title' => 'string',
            ]);

            Feedback::create([
                'name' => $validatedData['first_name']. ' '.$validatedData['last_name'],
                'email' => $validatedData['email'],
                'message' => $validatedData['message'],
                'story_title' => $validatedData['story_title'],
            ]);

            // Use defer for sending mail
           /* defer(function() use ($request) {
                $mailable = new \App\Mail\ResetPasswordMail($token, $request->email);
                $emailService->send($mailable, $request->email);
            });*/

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
        if (!$feedback->read) {
            $feedback->update(['read' => true]);
        }

        return response()->json([
            'status' => true,
            'message' => "Marked As Read"
           ],200);
    }

    public function getAdminStats()
    {
        $totalArtworks = Artwork::count();

        $pendingOrdersCount = Order::whereIn('status', ['pending', 'shipped', 'processing'])->count();
        $deliveredOrdersCount = Order::where('status', 'delivered')->count();

        return response()->json([
            'total_artworks'    => $totalArtworks,
            'pending_orders'    => $pendingOrdersCount,
            'delivered_orders'  => $deliveredOrdersCount,
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Feedback $feedback)
    {
        //
    }
}
