<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('site.blog.index', [
            'posts' => BlogPost::query()->visible()->with('cover')->latest('published_at')->latest('id')->paginate(9),
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()->visible()->with('cover')->where('slug', $slug)->firstOrFail();

        // Markdown with raw HTML escaped and unsafe link schemes removed (same as CMS pages).
        $body = Str::markdown((string) $post->translate('body'), [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);

        return view('site.blog.show', [
            'post' => $post,
            'body' => $body,
            'related' => BlogPost::query()->visible()->with('cover')->whereKeyNot($post->id)->latest('published_at')->limit(3)->get(),
        ]);
    }
}
