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

class JobController extends Controller
{
    /**
     * Display a listing of job listings.
     */
    public function index(Request $request)
    {
        // Get the job category ID
        $jobCategory = DB::table('listing_categories')
            ->where('name', 'job')
            ->first();
            
        if (!$jobCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Job category not found'
            ], 404);
        }
        
        $query = DB::table('listings')
            ->join('job_listings', 'listings.id', '=', 'job_listings.listing_id')
            ->leftJoin('listing_subcategories', 'listings.subcategory_id', '=', 'listing_subcategories.id')
            ->where('listings.category_id', $jobCategory->id)
            ->select(
                'listings.*', 
                'job_listings.*',
                'listing_subcategories.name as job_category_name',
                'listing_subcategories.display_name as job_category_display_name'
            );
        
        // Apply filters
        if ($request->has('subcategory_id')) {
            $query->where('listings.subcategory_id', $request->subcategory_id);
        }
        
        if ($request->has('job_type')) {
            $query->where('job_listings.job_type', $request->job_type);
        }
        
        if ($request->has('attendance_type')) {
            $query->where('job_listings.attendance_type', $request->attendance_type);
        }
        
        if ($request->has('min_salary')) {
            $query->where('job_listings.salary', '>=', $request->min_salary);
        }
        
        if ($request->has('max_salary')) {
            $query->where('job_listings.salary', '<=', $request->max_salary);
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
     * Show the form for creating a new job listing.
     */
    public function create()
    {
        // Get job category
        $jobCategory = DB::table('listing_categories')
            ->where('name', 'job')
            ->first();
            
        if (!$jobCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Job category not found'
            ], 404);
        }
        
        // Get job types (subcategories)
        $jobTypes = DB::table('listing_subcategories')
            ->where('category_id', $jobCategory->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
        
        return response()->json([
            'success' => true,
            'category' => $jobCategory,
            'job_types' => $jobTypes
        ]);
    }

    /**
     * Store a newly created job listing in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'phone_number' => 'required|string|max:20',
            'subcategory_id' => 'required|exists:listing_subcategories,id',
            'job_title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'job_type' => 'required|string|in:full_time,part_time,contract,temporary,internship',
            'location' => 'required|json',
        ]);

        try {
            // Get the job category ID
            $jobCategory = DB::table('listing_categories')
                ->where('name', 'job')
                ->first();
                
            if (!$jobCategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job category not found'
                ], 404);
            }
            
            DB::beginTransaction();
            
            // Create the base listing
            $postNumber = 'JO-' . Str::random(8);
            
            $listing = [
                'user_id' => Auth::id(),
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->salary ?? 0,
                'price_type' => $request->salary_period ?? 'monthly',
                'currency' => $request->salary_currency ?? 'USD',
                'post_number' => $postNumber,
                'phone_number' => $request->phone_number,
                'category_id' => $jobCategory->id,
                'subcategory_id' => $request->subcategory_id,
                'listing_type' => 'job',
                'purpose' => 'offer',
                'status' => 'active',
                'location' => $request->location,
                'features' => $request->features ?? null,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $listingId = DB::table('listings')->insertGetId($listing);
            
            // Create the job specific listing
            $jobListing = [
                'listing_id' => $listingId,
                'job_title' => $request->job_title,
                'company_name' => $request->company_name,
                'job_type' => $request->job_type,
                'attendance_type' => $request->attendance_type,
                'job_category' => $request->job_category,
                'job_subcategory' => $request->job_subcategory,
                'gender_preference' => $request->gender_preference,
                'salary' => $request->salary,
                'salary_period' => $request->salary_period,
                'salary_currency' => $request->salary_currency ?? 'USD',
                'is_salary_negotiable' => $request->is_salary_negotiable ?? false,
                'experience_years_min' => $request->experience_years_min,
                'education_level' => $request->education_level,
                'required_language' => $request->required_language,
                'company_size' => $request->company_size,
                'benefits' => $request->benefits ? json_encode($request->benefits) : null,
                'application_link' => $request->application_link,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            DB::table('job_listings')->insert($jobListing);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Job listing created successfully',
                'data' => [
                    'listing_id' => $listingId,
                    'post_number' => $postNumber
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create job listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified job listing.
     */
    public function show($id)
    {
        $listing = DB::table('listings')
            ->join('job_listings', 'listings.id', '=', 'job_listings.listing_id')
            ->leftJoin('listing_categories', 'listings.category_id', '=', 'listing_categories.id')
            ->leftJoin('listing_subcategories', 'listings.subcategory_id', '=', 'listing_subcategories.id')
            ->where('listings.id', $id)
            ->select(
                'listings.*', 
                'job_listings.*',
                'listing_categories.name as category_name',
                'listing_categories.display_name as category_display_name',
                'listing_subcategories.name as subcategory_name',
                'listing_subcategories.display_name as subcategory_display_name'
            )
            ->first();
            
        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Job listing not found'
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
     * Search for job listings based on criteria.
     */
    public function search(Request $request)
    {
        // Get the job category ID
        $jobCategory = DB::table('listing_categories')
            ->where('name', 'job')
            ->first();
            
        if (!$jobCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Job category not found'
            ], 404);
        }
        
        $query = DB::table('listings')
            ->join('job_listings', 'listings.id', '=', 'job_listings.listing_id')
            ->where('listings.category_id', $jobCategory->id)
            ->select('listings.*', 'job_listings.*');
            
        // Apply filters
        if ($request->has('subcategory_id')) {
            $query->where('listings.subcategory_id', $request->subcategory_id);
        }
        
        if ($request->has('job_type')) {
            $query->where('job_listings.job_type', $request->job_type);
        }
        
        if ($request->has('attendance_type')) {
            $query->where('job_listings.attendance_type', $request->attendance_type);
        }
        
        if ($request->has('min_salary')) {
            $query->where('job_listings.salary', '>=', $request->min_salary);
        }
        
        if ($request->has('max_salary')) {
            $query->where('job_listings.salary', '<=', $request->max_salary);
        }
        
        if ($request->has('experience_years_min')) {
            $query->where('job_listings.experience_years_min', '<=', $request->experience_years_min);
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