<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zenith\Http\Request;
use Zenith\Http\Response;
use App\Models\Post;

class PostController
{
    public function index(Request $request): Response
    {
        $posts = Post::all();
        return json(['posts' => $posts]);
    }

    public function show(Request $request, int $id): Response
    {
        $post = Post::find($id);
        
        if ($post === null) {
            return json(['error' => 'Post not found'], 404);
        }
        
        return json(['post' => $post]);
    }

    public function store(Request $request): Response
    {
        $data = $request->body;
        
        $post = Post::create($data);
        
        return json(['post' => $post], 201);
    }

    public function update(Request $request, int $id): Response
    {
        $post = Post::find($id);
        
        if ($post === null) {
            return json(['error' => 'Post not found'], 404);
        }
        
        $post->fill($request->body);
        $post->save();
        
        return json(['post' => $post]);
    }

    public function destroy(Request $request, int $id): Response
    {
        $post = Post::find($id);
        
        if ($post === null) {
            return json(['error' => 'Post not found'], 404);
        }
        
        $post->delete();
        
        return json(['message' => 'Post deleted']);
    }
}
