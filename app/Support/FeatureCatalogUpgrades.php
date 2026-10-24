<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plan features introduced after 1.0. Added by a migration on update, and again after a
 * restore (a backup taken before the feature existed would otherwise remove it). Idempotent:
 * only missing rows are inserted; values an operator changed are never touched.
 */
final class FeatureCatalogUpgrades
{
    /** key => [type, group, basic, pro, premium, [tr, en] name, [tr, en] description] */
    public const FEATURES = [
        'profile.tracking_pixels' => ['boolean', 'analytics', '0', '1', '1',
            ['İzleme pikselleri', 'Tracking pixels'],
            ['Bio sayfasına Google Analytics, Google Ads, Google Tag Manager, Meta ve TikTok pikselleri ekleme (ziyaretçi izniyle yüklenir).', 'Add Google Analytics, Google Ads, Google Tag Manager, Meta and TikTok pixels to a bio page (loaded with visitor consent).']],
        'shortlinks.max' => ['integer', 'content', '10', '200', '-1',
            ['Kısa bağlantı sayısı', 'Short links'],
            ['Oluşturulabilecek kısa bağlantı sayısı.', 'How many short links can be created.']],
    ];

    /** @return bool whether anything was added */
    public static function apply(): bool
    {
        if (! Schema::hasTable('feature_definitions') || ! Schema::hasTable('package_features')) {
            return false;
        }

        $changed = false;
        $order = (int) DB::table('feature_definitions')->max('sort_order');

        foreach (self::FEATURES as $key => [$type, $group, $basic, $pro, $premium, $name, $description]) {
            if (! DB::table('feature_definitions')->where('key', $key)->exists()) {
                DB::table('feature_definitions')->insert([
                    'key' => $key,
                    'type' => $type,
                    'group' => $group,
                    'default_value' => $basic,
                    'name' => json_encode(['tr' => $name[0], 'en' => $name[1]], JSON_UNESCAPED_UNICODE),
                    'description' => json_encode(['tr' => $description[0], 'en' => $description[1]], JSON_UNESCAPED_UNICODE),
                    'platform_enabled' => true,
                    'platform_cap' => null,
                    'sort_order' => ++$order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $changed = true;
            }

            foreach (['basic' => $basic, 'pro' => $pro, 'premium' => $premium] as $packageKey => $value) {
                $packageId = DB::table('packages')->where('key', $packageKey)->value('id');

                if ($packageId && ! DB::table('package_features')->where('package_id', $packageId)->where('feature_key', $key)->exists()) {
                    DB::table('package_features')->insert(['package_id' => $packageId, 'feature_key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
                    $changed = true;
                }
            }
        }

        return $changed;
    }
}
