<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FeatureDefinition;
use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Entitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(): View
    {
        return view('admin.packages.index', [
            'packages' => Package::query()->withCount(['assignments as active_assignments_count' => fn ($q) => $q->current()])
                ->with('customFor:id,name,username')->orderBy('is_archived')->orderBy('sort_order')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.packages.form', [
            'package' => new Package(['currency' => 'TRY', 'is_public' => true]),
            'features' => FeatureDefinition::query()->orderBy('group')->orderBy('sort_order')->get(),
            'values' => [],
            'customUser' => $request->filled('user') ? User::query()->find($request->query('user')) : null,
            'otherPackages' => Package::query()->orderBy('sort_order')->get(),
            'history' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $package = DB::transaction(function () use ($data, $request) {
            $package = Package::query()->create($data['attributes']);
            $this->saveFeatures($package, $data['features']);

            if ($request->filled('copy_from')) {
                $this->copyFeatures(Package::query()->findOrFail($request->input('copy_from')), $package);
            }

            return $package;
        });

        $this->entitlements->flushAll();
        $this->audit->log('package.created', $package, ['key' => $package->key]);

        return redirect()->route('admin.packages.edit', $package)->with('success', __('admin.packages.created'));
    }

    public function edit(Package $package): View
    {
        return view('admin.packages.form', [
            'package' => $package,
            'features' => FeatureDefinition::query()->orderBy('group')->orderBy('sort_order')->get(),
            'values' => $package->features()->pluck('value', 'feature_key')->all(),
            'customUser' => $package->customFor,
            'otherPackages' => Package::query()->whereKeyNot($package->id)->orderBy('sort_order')->get(),
            'history' => AuditLog::query()->where('subject_type', 'Package')->where('subject_id', $package->id)->with('actor:id,name')->latest('id')->limit(30)->get(),
        ]);
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $data = $this->validated($request, $package);
        $before = ['attributes' => $package->only(array_keys($data['attributes'])), 'features' => $package->features()->pluck('value', 'feature_key')->all()];

        DB::transaction(function () use ($package, $data) {
            $package->update($data['attributes']);
            $this->saveFeatures($package, $data['features']);
        });

        $this->entitlements->flushAll();
        $this->audit->log('package.updated', $package, [
            'changes' => array_diff_assoc(array_map(fn ($v) => is_array($v) ? json_encode($v) : (string) $v, $data['attributes']), array_map(fn ($v) => is_array($v) ? json_encode($v) : (string) $v, $before['attributes'])),
            'features' => array_diff_assoc($data['features'], $before['features']),
        ]);

        return back()->with('success', __('admin.packages.updated'));
    }

    public function copy(Request $request, Package $package): RedirectResponse
    {
        $request->validate(['from' => ['required', 'integer', 'exists:packages,id']]);
        $source = Package::query()->findOrFail($request->input('from'));
        $this->copyFeatures($source, $package);
        $this->entitlements->flushAll();
        $this->audit->log('package.features_copied', $package, ['from' => $source->key]);

        return back()->with('success', __('admin.packages.copied', ['from' => $source->translate('name')]));
    }

    public function archive(Package $package): RedirectResponse
    {
        if ($package->is_default) {
            return back()->with('error', __('admin.packages.cannot_archive_default'));
        }

        $package->update(['is_archived' => ! $package->is_archived, 'is_public' => $package->is_archived ? $package->is_public : false]);
        $this->entitlements->flushAll();
        $this->audit->log($package->is_archived ? 'package.archived' : 'package.unarchived', $package);

        return back()->with('success', __('admin.packages.updated'));
    }

    public function destroy(Package $package): RedirectResponse
    {
        if ($package->is_default || $package->assignments()->exists()) {
            return back()->with('error', __('admin.packages.cannot_delete_used'));
        }

        $this->audit->log('package.deleted', $package, ['key' => $package->key]);
        $package->delete();
        $this->entitlements->flushAll();

        return redirect()->route('admin.packages.index')->with('success', __('admin.packages.deleted'));
    }

    private function validated(Request $request, ?Package $package = null): array
    {
        $request->merge(['key' => Str::slug((string) $request->input('key'), '_')]);
        $data = $request->validate([
            'key' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('packages', 'key')->ignore($package?->id)],
            'name.tr' => ['required', 'string', 'max:80'],
            'name.en' => ['nullable', 'string', 'max:80'],
            'description.tr' => ['nullable', 'string', 'max:300'],
            'description.en' => ['nullable', 'string', 'max:300'],
            'marketing_features.tr' => ['nullable', 'string', 'max:3000'],
            'marketing_features.en' => ['nullable', 'string', 'max:3000'],
            'price_monthly' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'price_yearly' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'contact_message.tr' => ['nullable', 'string', 'max:300'],
            'contact_message.en' => ['nullable', 'string', 'max:300'],
            'contact_url' => ['nullable', 'string', 'max:500', 'regex:#^(/[^/]|https://)#'],
            'custom_for_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'string', 'max:20', 'regex:/^-?\d+$/'],
        ]);

        $lines = fn (?string $text) => array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $text))));

        $isDefault = $request->boolean('is_default');

        if ($isDefault) {
            Package::query()->when($package, fn ($q) => $q->whereKeyNot($package->id))->update(['is_default' => false]);
        }

        $attributes = [
            'key' => $data['key'],
            'name' => array_filter($data['name']),
            'description' => array_filter($data['description'] ?? []),
            'marketing_features' => ['tr' => $lines($data['marketing_features']['tr'] ?? ''), 'en' => $lines($data['marketing_features']['en'] ?? '')],
            'price_monthly' => $data['price_monthly'] ?? null,
            'price_yearly' => $data['price_yearly'] ?? null,
            'currency' => $data['currency'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_public' => $request->boolean('is_public'),
            'is_highlighted' => $request->boolean('is_highlighted'),
            'is_contact_only' => $request->boolean('is_contact_only'),
            'is_default' => $isDefault,
            'contact_message' => array_filter($data['contact_message'] ?? []),
            'contact_url' => $data['contact_url'] ?? null,
            'custom_for_user_id' => $data['custom_for_user_id'] ?? null,
        ];

        // Values are normalised per feature type; unknown keys are ignored.
        $definitions = FeatureDefinition::query()->get()->keyBy('key');
        $features = [];

        foreach ($data['features'] ?? [] as $key => $value) {
            if ($value === null || $value === '' || ! $definitions->has($key)) {
                continue;
            }

            $features[$key] = $definitions[$key]->isBoolean() ? ((int) $value === 1 ? '1' : '0') : (string) max(-1, (int) $value);
        }

        foreach ($definitions as $key => $definition) {
            if ($definition->isBoolean() && ! isset($features[$key]) && $request->has('features_present')) {
                $features[$key] = '0';
            }
        }

        return ['attributes' => $attributes, 'features' => $features];
    }

    private function saveFeatures(Package $package, array $features): void
    {
        foreach ($features as $key => $value) {
            PackageFeature::query()->updateOrCreate(['package_id' => $package->id, 'feature_key' => $key], ['value' => $value]);
        }
    }

    private function copyFeatures(Package $from, Package $to): void
    {
        foreach ($from->features as $feature) {
            PackageFeature::query()->updateOrCreate(['package_id' => $to->id, 'feature_key' => $feature->feature_key], ['value' => $feature->value]);
        }
    }
}
