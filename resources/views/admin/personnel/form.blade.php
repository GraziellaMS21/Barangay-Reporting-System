@extends('layouts.admin')

@section('title', $personnel->exists ? "Edit Official: {$personnel->full_name}" : 'Add Official')
@section('page-heading', $personnel->exists ? 'Edit Official' : 'Add Official')

@section('content')
<x-ui.page-header :title="$personnel->exists ? 'Edit Official: '.$personnel->full_name : 'Add Official'" subtitle="Record official particulars, term of office, signatory authorization, and optional system login.">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('admin.personnel.index')">
            <i class="fa-solid fa-arrow-left"></i> Back to Personnel
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<form method="POST"
      action="{{ $personnel->exists ? route('admin.personnel.update', $personnel) : route('admin.personnel.store') }}"
      enctype="multipart/form-data"
      class="space-y-6">
    @csrf
    @if($personnel->exists)
        @method('PUT')
    @endif

    <x-ui.card title="1. Basic Particulars">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-ui.form-field label="Full Name" name="full_name" :required="true">
                <input id="full_name" name="full_name" type="text" required maxlength="120"
                       value="{{ old('full_name', $personnel->full_name) }}"
                       placeholder="e.g. Hon. Eduardo V. Alvarez"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </x-ui.form-field>

            <x-ui.form-field label="Position" name="position_id" :required="true">
                <select id="position_id" name="position_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select position</option>
                    @foreach($positions as $pos)
                        <option value="{{ $pos->id }}" @selected((string) old('position_id', $personnel->position_id) === (string) $pos->id)>
                            {{ $pos->name }} ({{ $pos->code }})
                        </option>
                    @endforeach
                </select>
            </x-ui.form-field>

            <x-ui.form-field label="Contact Number" name="contact_number" hint="Philippine format (e.g. 0917-123-4567 or +639171234567)">
                <input id="contact_number" name="contact_number" type="text" maxlength="30"
                       value="{{ old('contact_number', $personnel->contact_number) }}"
                       placeholder="0917-123-4567"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </x-ui.form-field>

            <x-ui.form-field label="Email Address" name="email">
                <input id="email" name="email" type="email" maxlength="150"
                       value="{{ old('email', $personnel->email) }}"
                       placeholder="official@calarian.gov.ph"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </x-ui.form-field>
        </div>
    </x-ui.card>

    <x-ui.card title="2. Term of Office">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.form-field label="Term Start" name="term_start" :required="true">
                <input id="term_start" name="term_start" type="date" required
                       value="{{ old('term_start', $personnel->term_start?->format('Y-m-d') ?? now()->toDateString()) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </x-ui.form-field>

            <x-ui.form-field label="Term End" name="term_end" hint="Leave empty if currently serving.">
                <input id="term_end" name="term_end" type="date"
                       value="{{ old('term_end', $personnel->term_end?->format('Y-m-d')) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </x-ui.form-field>

            <div class="flex items-center pt-6">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $personnel->exists ? $personnel->is_active : true))>
                    <span>Active in current roster</span>
                </label>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="3. Signatory Authorization">
        <div class="space-y-4">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="hidden" name="is_signatory" value="0">
                <input type="checkbox" name="is_signatory" value="1" @checked(old('is_signatory', $personnel->is_signatory))>
                <span>Authorized to sign official documents (Summons, CFA, Endorsements, DILG Reports)</span>
            </label>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                <x-ui.form-field label="Signature Image (PNG, max 1 MB)" name="signature" hint="Stored on the private disk and embedded only in print views.">
                    <input id="signature" name="signature" type="file" accept="image/png"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                </x-ui.form-field>

                @if(!empty($signatureDataUri))
                    <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
                        <p class="text-xs font-semibold text-slate-600 mb-2">Current Stored Signature Preview</p>
                        <img src="{{ $signatureDataUri }}" alt="Stored signature" class="max-h-16 object-contain border border-slate-200 bg-white p-1 rounded">
                        <label class="inline-flex items-center gap-2 text-xs text-red-700 mt-2">
                            <input type="checkbox" name="remove_signature" value="1">
                            <span>Remove stored signature</span>
                        </label>
                    </div>
                @endif
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="4. Login Account Link">
        @if($personnel->user)
            <div class="text-sm text-slate-700 flex items-center justify-between flex-wrap gap-3">
                <div>
                    Linked to user account: <strong>{{ $personnel->user->name }}</strong> ({{ $personnel->user->email }})
                    · <x-ui.badge color="blue">{{ ucfirst($personnel->user->role) }}</x-ui.badge>
                </div>
                @can('users.manage')
                    <x-ui.button size="sm" variant="secondary" :href="route('admin.users.permissions.edit', $personnel->user)">
                        Manage User Permissions
                    </x-ui.button>
                @endcan
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.form-field label="Link Existing User Account" name="user_id" hint="Optional: link an already created account.">
                    <select id="user_id" name="user_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">None</option>
                        @foreach($availableUsers as $u)
                            <option value="{{ $u->id }}" @selected((string) old('user_id', $personnel->user_id) === (string) $u->id)>
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>
                </x-ui.form-field>

                <div class="flex items-center pt-6">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="hidden" name="create_login" value="0">
                        <input type="checkbox" name="create_login" value="1" @checked(old('create_login'))>
                        <span>Generate new login account (uses Email above)</span>
                    </label>
                </div>

                <x-ui.form-field label="Permission Preset (for new login)" name="preset" hint="Lupon members default to Case Encoder.">
                    <select id="preset" name="preset" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Auto by Position</option>
                        @foreach($presets as $presetKey => $presetMeta)
                            <option value="{{ $presetKey }}" @selected(old('preset') === $presetKey)>{{ $presetMeta['label'] }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            </div>
        @endif

        <div class="mt-4">
            <x-ui.form-field label="Notes" name="notes">
                <textarea id="notes" name="notes" rows="2" maxlength="1000"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('notes', $personnel->notes) }}</textarea>
            </x-ui.form-field>
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-3 pt-2">
        <x-ui.button variant="ghost" :href="route('admin.personnel.index')">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary">Save Official</x-ui.button>
    </div>
</form>
@endsection
