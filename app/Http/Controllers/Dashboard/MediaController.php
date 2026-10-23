<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $media)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $used = $this->media->referencedIds($user);

        return view('dashboard.media.index', [
            'files' => MediaFile::query()->where('user_id', $user->id)->latest('id')->paginate(48),
            'inUse' => array_flip($used),
            'storage' => ['used' => $this->media->usedBytes($user), 'quota' => $this->media->quotaBytes($user)],
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $maxKb = (int) (config('biofy.uploads.max_file_size') / 1024);

        $request->validate([
            // The content type is sniffed from the bytes by MediaService (never from the name or path).
            'file' => ['required', 'file', 'max:'.$maxKb],
            'purpose' => ['required', Rule::in(['avatar', 'banner', 'block', 'gallery', 'seo'])],
        ]);

        $file = $this->media->store($request->file('file'), $request->user(), (string) $request->input('purpose'));

        if ($request->expectsJson()) {
            return response()->json(['id' => $file->id, 'url' => $file->url(), 'width' => $file->width, 'height' => $file->height, 'message' => __('media.uploaded')], 201);
        }

        return back()->with('success', __('media.uploaded'));
    }

    public function destroy(Request $request, MediaFile $media): JsonResponse|RedirectResponse
    {
        abort_unless($media->user_id === $request->user()->id, 404);

        if ($this->media->isInUse($media)) {
            $message = __('media.in_use');

            return $request->expectsJson() ? response()->json(['message' => $message], 422) : back()->with('error', $message);
        }

        $this->media->delete($media);

        return $request->expectsJson() ? response()->json(['message' => __('media.deleted')]) : back()->with('success', __('media.deleted'));
    }
}
