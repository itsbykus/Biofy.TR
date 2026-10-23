<?php

namespace App\Http\Controllers\Dashboard\Concerns;

use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Editor endpoints answer JSON to the progressive-enhancement script (so the
 * page and live preview update in place) and a normal redirect without JS.
 */
trait RespondsToEditor
{
    protected function editorResponse(Request $request, Profile $profile, string $message, string $tab, array $fragments = []): JsonResponse|RedirectResponse
    {
        $profile->refresh();

        if ($request->expectsJson()) {
            $html = [];

            foreach ($fragments as $target => $view) {
                $html[$target] = view($view, $this->editorFragmentData($profile))->render();
            }

            return response()->json([
                'message' => $message,
                'revision' => $profile->draft_revision,
                'status' => $profile->status,
                'dirty' => $profile->has_unpublished_changes,
                'html' => $html,
            ]);
        }

        return redirect()->route('pages.edit', ['profile' => $profile, 'tab' => $tab])->with('success', $message);
    }

    protected function editorFragmentData(Profile $profile): array
    {
        return app(\App\Http\Controllers\Dashboard\ProfileController::class)->editorData($profile->fresh(['blocks', 'socialLinks', 'user']));
    }
}
