<?php

namespace App\Http\Controllers;

use App\Models\blog;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        // Fetch blogs with user relationship, filtered by status if provided
        $blogs = Blog::with('user')
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json(['status' => 'success', 'data' => $blogs], 200);
    }

    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_date' => 'nullable|date',
            'feature_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];

           // Create the validator instance
          $validator = Validator::make($request->all(), $rules);

          if ($validator->fails()) {
              // Customize the error response for API requests
              return response()->json([
                  'status' => 'error',
                  'message' => 'Validation failed',
                  'errors' => $validator->errors(),
              ], 422);
          }

          $validated = $validator->validated();

        // Generate slug from title
        $validated['slug'] = Str::slug($validated['title'], '-') . "-" .Str::random(4);
        $validated['status'] = 'draft';
        // Upload feature image if present
        if ($request->hasFile('feature_image')) {
            $image = $request->file('feature_image');
            $imageName = time().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('images/blogs'), $imageName);
            $validated['feature_image'] = 'images/blogs/'.$imageName;
        }

        $validated['user_id'] = Auth::id(); // Automatically set user ID
        $blog = blog::create($validated);

        return response()->json(['status' => 'success', 'message'=>'Blog Created Succesfully', 'data' => $blog], 201);
    }

    public function show($slug)
    {
        $blog = blog::with('user')->where('slug', $slug)->firstOrFail();
        return response()->json(['status' => 'success', 'data' => $blog], 200);
    }

    public function update(Request $request, $Id)
    {

        $blog = blog::where('id', $Id)->first();


        if(!$blog){
            return response()->json(['status' => 'error', 'message'=>'Blog Not Found'], 401);
        }

        $rules = [
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'status' => 'nullable|string',
            'publish_date' => 'nullable|date',
            'feature_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];

           // Create the validator instance
           $validator = Validator::make($request->all(), $rules);

           if ($validator->fails()) {
               // Customize the error response for API requests
               return response()->json([
                   'status' => 'error',
                   'message' => 'Validation failed',
                   'errors' => $validator->errors(),
               ], 422);
           }

           $validated = $validator->validated();


        // Generate slug from title
        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title'], '-') . "-" .Str::random(4);
        }

        // Upload new feature image if present
        if ($request->hasFile('feature_image')) {
            // Delete old image if it exists
            if ($blog->feature_image && file_exists(public_path($blog->feature_image))) {
                unlink(public_path($blog->feature_image));
            }

            $image = $request->file('feature_image');
            $imageName = time().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('images/blogs'), $imageName);
            $validated['feature_image'] = 'images/blogs/'.$imageName;
        }

        $blog->update($validated);


        return response()->json(['status' => 'success', 'data' => $blog], 200);
    }

    public function destroy($slug)
    {
        $blog = blog::where('slug', $slug)->firstOrFail();

        // Delete the feature image if it exists
        if ($blog->feature_image && file_exists(public_path($blog->feature_image))) {
            unlink(public_path($blog->feature_image));
        }

        $blog->delete();

        return response()->json(['status' => 'success', 'message' => 'Blog deleted successfully.'], 200);
    }
}
