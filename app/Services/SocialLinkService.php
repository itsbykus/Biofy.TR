<?php

namespace App\Services;

use App\Exceptions\UserFacingException;
use App\Models\Profile;
use App\Models\ProfileSocialLink;
use App\Support\Phone;
use App\Support\SafeUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SocialLinkService
{
    public function __construct(private readonly Entitlements $entitlements)
    {
    }

    public function create(Profile $profile, string $platform, string $value): ProfileSocialLink
    {
        $count = $profile->socialLinks()->count();

        if (! $this->entitlements->withinLimit($profile->user, Entitlements::MAX_SOCIAL_LINKS, $count)) {
            throw new UserFacingException(__('social.limit_reached'));
        }

        $link = ProfileSocialLink::query()->create([
            'profile_id' => $profile->id,
            'platform' => $platform,
            'url' => $this->normalize($platform, $value),
            'position' => (int) $profile->socialLinks()->max('position') + 1,
        ]);

        $profile->markDirty();

        return $link;
    }

    public function update(ProfileSocialLink $link, string $value): ProfileSocialLink
    {
        $link->update(['url' => $this->normalize($link->platform, $value)]);
        $link->profile->markDirty();

        return $link;
    }

    public function delete(ProfileSocialLink $link): void
    {
        $profile = $link->profile;
        $link->delete();
        $profile->markDirty();
    }

    public function reorder(Profile $profile, array $orderedIds): void
    {
        $ids = array_map('intval', $orderedIds);
        $current = $profile->socialLinks()->pluck('id')->sort()->values()->all();
        $sorted = $ids;
        sort($sorted);

        if ($sorted !== $current) {
            throw ValidationException::withMessages(['order' => __('blocks.reorder_mismatch')]);
        }

        DB::transaction(function () use ($ids, $profile) {
            foreach ($ids as $position => $id) {
                ProfileSocialLink::query()->whereKey($id)->where('profile_id', $profile->id)->update(['position' => $position]);
            }
        });

        $profile->markDirty();
    }

    /**
     * Accepts a full URL (must be on the platform's domain) or a bare handle which is
     * expanded with the platform's URL template.
     */
    public function normalize(string $platform, string $value): string
    {
        $platforms = config('biofy.social_platforms');

        if (! isset($platforms[$platform])) {
            throw ValidationException::withMessages(['platform' => __('social.unknown_platform')]);
        }

        $config = $platforms[$platform];
        $value = trim($value);

        if ($platform === 'email') {
            $email = strtolower((string) preg_replace('/^mailto:/i', '', $value));

            if (strlen($email) > 254 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['value' => __('social.invalid_email')]);
            }

            return 'mailto:'.$email;
        }

        // A WhatsApp number becomes a click-to-chat link; wa.me / whatsapp.com URLs are checked below.
        if ($platform === 'whatsapp' && ! preg_match('#^https?://#i', $value) && ! str_contains($value, '/')) {
            $phone = Phone::normalize($value);

            if ($phone === null) {
                throw ValidationException::withMessages(['value' => __('social.invalid_phone')]);
            }

            return 'https://wa.me/'.ltrim($phone, '+');
        }
        // Handles may contain dots (instagram "mito.tr"), so only treat input as a URL when it clearly is one.
        $looksLikeUrl = (bool) preg_match('#^https?://#i', $value)
            || str_contains($value, '/')
            || ($config['hosts'] !== [] && SafeUrl::hostMatches('https://'.$value, $config['hosts']));

        if (! $looksLikeUrl && $config['template']) {
            $handle = ltrim($value, '@');

            if (! preg_match('/^[A-Za-z0-9._-]{1,60}$/', $handle)) {
                throw ValidationException::withMessages(['value' => __('social.invalid_handle')]);
            }

            return str_replace('{handle}', $handle, $config['template']);
        }

        $url = SafeUrl::normalize($value);

        if ($url === null) {
            throw ValidationException::withMessages(['value' => __('validation.custom.url.unsafe')]);
        }

        if ($config['hosts'] !== [] && ! SafeUrl::hostMatches($url, $config['hosts'])) {
            throw ValidationException::withMessages(['value' => __('social.wrong_domain', ['platform' => __('social.platforms.'.$platform)])]);
        }

        return $url;
    }
}
