<?php

namespace Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Community\Models\ListingCategory;
use Modules\Community\Models\ListingSubcategory;

class BidController extends Controller
{
    /**
     * Display a listing of bid listings.
     */
    public function index(Request $request)
    {
        // Get the bid category ID
        $bidCategory = DB::table('listing_categories')
            ->where('name', 'bid')
            ->first();
            
        if (!$bidCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Bid category not found'
            ], 404);
        }
        
        $query = DB::table('listings')
            ->join('bid_listings', 'listings.id', '=', 'bid_listings.listing_id')
            ->leftJoin('listing_subcategories', 'listings.subcategory_id', '=', 'listing_subcategories.id')
            ->where('listings.category_id', $bidCategory->id)
            ->select(
                'listings.*', 
                'bid_listings.*',
                'listing_subcategories.name as bid_type_name',
                'listing_subcategories.display_name as bid_type_display_name'
            );
        
        // Apply filters
        if ($request->has('subcategory_id')) {
            $query->where('listings.subcategory_id', $request->subcategory_id);
        }
        
        if ($request->has('bid_type')) {
            $query->where('bid_listings.bid_type', $request->bid_type);
        }
        
        if ($request->has('sector')) {
            $query->where('bid_listings.sector', $request->sector);
        }
        
        if ($request->has('min_investment')) {
            $query->where('bid_listings.investment_amount_min', '>=', $request->min_investment);
        }
        
        if ($request->has('max_investment')) {
            $query->where('bid_listings.investment_amount_max', '<=', $request->max_investment);
        }
        
        // Sort results
        $sortBy = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        $query->orderBy("listings.$sortBy", $sortDirection);
        
        // Paginate results
        $perPage = $request->per_page ?? 10;
        $results = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'pagination' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new bid listing.
     */
    public function create()
    {
        // Get bid category
        $bidCategory = DB::table('listing_categories')
            ->where('name', 'bid')
            ->first();
            
        if (!$bidCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Bid category not found'
            ], 404);
        }
        
        // Get bid types (subcategories)
        $bidTypes = DB::table('listing_subcategories')
            ->where('category_id', $bidCategory->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
        
        return response()->json([
            'success' => true,
            'category' => $bidCategory,
            'bid_types' => $bidTypes
        ]);
    }

    /**
     * Store a newly created bid listing in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'phone_number' => 'required|string|max:20',
            'subcategory_id' => 'required|exists:listing_subcategories,id',
            'bid_type' => 'required|string|in:investment,project,auction,tender',
            'sector' => 'required|string',
            'location' => 'required|json',
        ]);

        try {
            // Get the bid category ID
            $bidCategory = DB::table('listing_categories')
                ->where('name', 'bid')
                ->first();
                
            if (!$bidCategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bid category not found'
                ], 404);
            }
            
            DB::beginTransaction();
            
            // Create the base listing
            $postNumber = 'BI-' . Str::random(8);
            
            $listing = [
                'user_id' => Auth::id(),
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->investment_amount_min ?? 0,
                'price_type' => 'investment',
                'currency' => $request->currency ?? 'USD',
                'post_number' => $postNumber,
                'phone_number' => $request->phone_number,
                'category_id' => $bidCategory->id,
                'subcategory_id' => $request->subcategory_id,
                'listing_type' => 'bid',
                'purpose' => 'offer',
                'status' => 'active',
                'location' => $request->location,
                'features' => $request->features ?? null,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $listingId = DB::table('listings')->insertGetId($listing);
            
            // Create the bid specific listing
            $bidListing = [
                'listing_id' => $listingId,
                'bid_type' => $request->bid_type,
                'bid_code' => $request->bid_code,
                'main_category' => $request->main_category,
                'sector' => $request->sector,
                'contact_phone' => $request->contact_phone,
                'contact_email' => $request->contact_email,
                'application_link' => $request->application_link,
                'submission_start_date' => $request->submission_start_date,
                'submission_end_date' => $request->submission_end_date,
                'is_facility_under_construction' => $request->is_facility_under_construction ?? false,
                'investment_amount_min' => $request->investment_amount_min,
                'investment_amount_max' => $request->investment_amount_max,
                'expected_return' => $request->expected_return,
                'return_period' => $request->return_period,
                'investment_term' => $request->investment_term,
                'risk_level' => $request->risk_level,
                'is_equity' => $request->is_equity ?? false,
                'is_debt' => $request->is_debt ?? false,
                'equity_percentage' => $request->equity_percentage,
                'business_plan' => $request->business_plan ? json_encode($request->business_plan) : null,
                'financial_projections' => $request->financial_projections ? json_encode($request->financial_projections) : null,
                'documents' => $request->documents ? json_encode($request->documents) : null,
                'requirements' => $request->requirements ? json_encode($request->requirements) : null,
                'terms_and_conditions' => $request->terms_and_conditions ? json_encode($request->terms_and_conditions) : null,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            DB::table('bid_listings')->insert($bidListing);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Bid listing created successfully',
                'data' => [
                    'listing_id' => $listingId,
                    'post_number' => $postNumber
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bid listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified bid listing.
     */
    public function show($id)
    {
        $listing = DB::table('listings')
            ->join('bid_listings', 'listings.id', '=', 'bid_listings.listing_id')
            ->leftJoin('listing_categories', 'listings.category_id', '=', 'listing_categories.id')
            ->leftJoin('listing_subcategories', 'listings.subcategory_id', '=', 'listing_subcategories.id')
            ->where('listings.id', $id)
            ->select(
                'listings.*', 
                'bid_listings.*',
                'listing_categories.name as category_name',
                'listing_categories.display_name as category_display_name',
                'listing_subcategories.name as subcategory_name',
                'listing_subcategories.display_name as subcategory_display_name'
            )
            ->first();
            
        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Bid listing not found'
            ], 404);
        }
        
        // Increment view count
        DB::table('listings')
            ->where('id', $id)
            ->increment('views_count');
            
        return response()->json([
            'success' => true,
            'data' => $listing
        ]);
    }

    /**
     * Search for bid listings based on criteria.
     */
    public function search(Request $request)
    {
        // Get the bid category ID
        $bidCategory = DB::table('listing_categories')
            ->where('name', 'bid')
            ->first();
            
        if (!$bidCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Bid category not found'
            ], 404);
        }
        
        $query = DB::table('listings')
            ->join('bid_listings', 'listings.id', '=', 'bid_listings.listing_id')
            ->where('listings.category_id', $bidCategory->id)
            ->select('listings.*', 'bid_listings.*');
            
        // Apply filters
        if ($request->has('subcategory_id')) {
            $query->where('listings.subcategory_id', $request->subcategory_id);
        }
        
        if ($request->has('bid_type')) {
            $query->where('bid_listings.bid_type', $request->bid_type);
        }
        
        if ($request->has('sector')) {
            $query->where('bid_listings.sector', $request->sector);
        }
        
        if ($request->has('min_investment')) {
            $query->where('bid_listings.investment_amount_min', '>=', $request->min_investment);
        }
        
        if ($request->has('max_investment')) {
            $query->where('bid_listings.investment_amount_max', '<=', $request->max_investment);
        }
        
        if ($request->has('risk_level')) {
            $query->where('bid_listings.risk_level', $request->risk_level);
        }
        
        // Sort results
        $sortBy = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        $query->orderBy("listings.$sortBy", $sortDirection);
        
        // Paginate results
        $perPage = $request->per_page ?? 10;
        $results = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'pagination' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ]
        ]);
    }
} 