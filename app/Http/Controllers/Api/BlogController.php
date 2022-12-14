<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class BlogController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $cachedBlogs = Redis::keys('blog_*');

        $blogs = $cachedBlogs ?? Blog::all();
        return response()->json([
            'status_code' => 201,
            'message' => 'Fetched from redis',
            'data' => $blogs,
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $cachedBlog = Redis::get('blog_' . $id);

        if(isset($cachedBlog)) {
            $blog = json_decode($cachedBlog, FALSE);
        } else {
            $blog = Blog::find($id);
            Redis::set('blog_' . $id, $blog);
        }
        return response()->json([
            'status_code' => 201,
            'message' => 'Fetched from redis',
            'data' => $blog,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse|void
     */
    public function update(Request $request, $id) {

        $update = Blog::findOrFail($id)->update($request->all());

        if($update) {
            // Delete blog_$id from Redis
            Redis::del('blog_' . $id);

            $blog = Blog::find($id);
            // Set a new key with the blog id
            Redis::set('blog_' . $id, $blog);

            return response()->json([
                'status_code' => 201,
                'message' => 'User updated',
                'data' => $blog,
            ]);
        }

    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {

        Blog::findOrFail($id)->delete();
        Redis::del('blog_' . $id);

        return response()->json([
            'status_code' => 201,
            'message' => 'Blog deleted'
        ]);
    }
}
