<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\UserFacingException;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\MediaFile;
use App\Services\AuditLogger;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Blog posts written by staff with the content permission. */
class BlogController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MediaService $media,
    ) {
    }

    public function index(): View
    {
        return view('admin.blog.index', [
            'posts' => BlogPost::query()->with('author:id,name')->orderByRaw('published_at IS NULL DESC')->latest('published_at')->latest('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.form', ['post' => new BlogPost(['is_published' => true, 'published_at' => now()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $post = new BlogPost;
        $post->author_id = $request->user()->id;

        try {
            $this->save($request, $post);
        } catch (UserFacingException $e) {
            return back()->withInput()->withErrors(['cover' => $e->getMessage()]);
        }

        $this->audit->log('blog.created', $post, ['slug' => $post->slug]);

        return redirect()->route('admin.blog.edit', $post)->with('success', __('admin.saved'));
    }

    public function edit(BlogPost $post): View
    {
        return view('admin.blog.form', ['post' => $post->load('cover')]);
    }

    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        try {
            $this->save($request, $post);
        } catch (UserFacingException $e) {
            return back()->withInput()->withErrors(['cover' => $e->getMessage()]);
        }

        $this->audit->log('blog.updated', $post, ['slug' => $post->slug]);

        return back()->with('success', __('admin.saved'));
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $cover = $post->cover;
        $this->audit->log('blog.deleted', $post, ['slug' => $post->slug]);
        $post->delete();

        if ($cover) {
            $this->media->delete($cover);
        }

        return redirect()->route('admin.blog.index')->with('success', __('admin.deleted'));
    }

    private function save(Request $request, BlogPost $post): void
    {
        $request->merge(['slug' => Str::slug((string) ($request->input('slug') ?: $request->input('title.tr')), '-', 'tr')]);
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('blog_posts', 'slug')->ignore($post->id)],
            'title.tr' => ['required', 'string', 'max:150'],
            'title.en' => ['nullable', 'string', 'max:150'],
            'excerpt.tr' => ['nullable', 'string', 'max:300'],
            'excerpt.en' => ['nullable', 'string', 'max:300'],
            'body.tr' => ['nullable', 'string', 'max:60000'],
            'body.en' => ['nullable', 'string', 'max:60000'],
            'meta_description.tr' => ['nullable', 'string', 'max:300'],
            'meta_description.en' => ['nullable', 'string', 'max:300'],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'file', 'max:4096'],
        ], [], ['slug' => __('admin.blog.slug')]);

        $published = $request->boolean('is_published');
        $oldCover = $post->cover_media_id ? MediaFile::query()->find($post->cover_media_id) : null;
        $coverId = $post->cover_media_id;

        if ($request->hasFile('cover')) {
            $coverId = $this->media->store($request->file('cover'), null, 'blog')->id;
        } elseif ($request->boolean('cover_remove')) {
            $coverId = null;
        }

        $post->fill([
            'slug' => $data['slug'],
            'title' => array_filter($data['title']),
            'excerpt' => array_filter($data['excerpt'] ?? []),
            'body' => array_filter($data['body'] ?? []),
            'meta_description' => array_filter($data['meta_description'] ?? []),
            'cover_media_id' => $coverId,
            'is_published' => $published,
            // Publishing without a date means "now"; a future date schedules the post.
            'published_at' => $data['published_at'] ?? null ? Carbon::parse($data['published_at'], local_timezone())->utc() : ($published ? now() : null),
        ])->save();

        if ($oldCover && $oldCover->id !== $coverId) {
            $this->media->delete($oldCover);
        }
    }
}
