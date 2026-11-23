@extends('layouts.admin')

@section('title', 'Docket New Blotter Case')

@section('content')
<div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Breadcrumb & Header -->
    <div class="border-b border-slate-200 pb-4">
        <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('admin.cases.index') }}" class="hover:text-slate-800">Cases</a></li>
                <li><span class="text-slate-400">/</span></li>
                <li class="font-semibold text-slate-800">New Docket</li>
            </ol>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900">File Barangay Blotter Case</h1>
        <p class="text-sm text-slate-600 mt-1">Docket an amicable dispute complaint under Katarungang Pambarangay</p>
    </div>

    @if($sourceReport)
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-lg text-sm text-amber-900 flex items-start justify-between">
            <div>
                <p class="font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-link"></i>
                    <span>Converting from Resident Report {{ $sourceReport->tracking_code }}</span>
                </p>
                <p class="text-xs text-amber-800 mt-0.5">Narrative and incident location have been prefilled. Personal reporter information is kept confidential per RA 10173.</p>
            </div>
            <span class="text-xs font-mono font-medium bg-amber-100 px-2 py-0.5 rounded">{{ $sourceReport->category?->name ?? 'Report' }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-sm text-red-800">
            <p class="font-semibold mb-1">Please correct the following errors:</p>
            <ul class="list-disc pl-5 text-xs space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="case-docket-form" method="POST" action="{{ route('admin.cases.store') }}" class="space-y-6 pb-20">
        @csrf
        @if($sourceReport)
            <input type="hidden" name="source_report_id" value="{{ $sourceReport->id }}">
            <input type="hidden" name="source" value="online_report">
        @endif

        <!-- Card A: Case Particulars -->
        <x-ui.card class="p-6 border border-slate-200 space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-semibold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-file-lines text-blue-600"></i>
                    <span>Card A: Case Particulars</span>
                </h2>
                <p class="text-xs text-slate-500">Legal classification, dispute timelines, and grievance narrative</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Case Type -->
                <div>
                    <label for="case_type_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Case Classification <span class="text-red-500">*</span>
                    </label>
                    <select name="case_type_id" id="case_type_id" required class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">Select Dispute Type...</option>
                        @foreach($caseTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('case_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filing Date -->
                <div>
                    <label for="filed_on" class="block text-xs font-semibold text-slate-700 mb-1">
                        Filing Date <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="date"
                        name="filed_on"
                        id="filed_on"
                        value="{{ old('filed_on', now()->toDateString()) }}"
                        required
                        max="{{ now()->toDateString() }}"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <!-- Incident Date -->
                <div>
                    <label for="incident_date" class="block text-xs font-semibold text-slate-700 mb-1">
                        Incident Date
                    </label>
                    <input
                        type="date"
                        name="incident_date"
                        id="incident_date"
                        value="{{ old('incident_date') }}"
                        max="{{ now()->toDateString() }}"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Purok -->
                <div>
                    <label for="purok_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Incident Purok
                    </label>
                    <select name="purok_id" id="purok_id" class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">Select Purok (Optional)...</option>
                        @foreach($puroks as $pk)
                            <option value="{{ $pk->id }}" @selected(old('purok_id', $sourceReport?->purok_id) == $pk->id)>{{ $pk->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Incident Location -->
                <div>
                    <label for="incident_location" class="block text-xs font-semibold text-slate-700 mb-1">
                        Specific Location / Landmark
                    </label>
                    <input
                        type="text"
                        name="incident_location"
                        id="incident_location"
                        value="{{ old('incident_location', $sourceReport?->location) }}"
                        placeholder="e.g. Near Purok 3 Basketball Court"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>
            </div>

            <!-- Case Title -->
            <div>
                <label for="title" class="block text-xs font-semibold text-slate-700 mb-1">
                    Case Title / Caption
                </label>
                <input
                    type="text"
                    name="title"
                    id="title"
                    value="{{ old('title') }}"
                    placeholder="Leave empty to auto-generate (e.g. Boundary Dispute: Dela Cruz vs. Penduko)"
                    class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                />
            </div>

            <!-- Complaint Narrative -->
            <div>
                <label for="narrative" class="block text-xs font-semibold text-slate-700 mb-1">
                    Complaint Narrative <span class="text-red-500">*</span>
                </label>
                <textarea
                    name="narrative"
                    id="narrative"
                    rows="4"
                    required
                    minlength="20"
                    placeholder="Enter detailed facts of the dispute as narrated by the complainant (minimum 20 characters)..."
                    class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                >{{ old('narrative', $sourceReport?->description) }}</textarea>
                <p class="text-[11px] text-slate-500 mt-1">Minimum 20 characters required.</p>
            </div>

            <!-- Confidentiality Checkbox -->
            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_confidential" value="1" @checked(old('is_confidential')) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-xs font-medium text-slate-700">Mark as confidential case (restricted access per RA 10173)</span>
                </label>
            </div>
        </x-ui.card>

        <!-- Card B: Complainant(s) -->
        <x-ui.card class="p-6 border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-user text-emerald-600"></i>
                        <span>Card B: Complainant(s)</span>
                        <span class="text-red-500 text-xs">*</span>
                    </h2>
                    <p class="text-xs text-slate-500">Aggrieved party filing the blotter complaint</p>
                </div>
                <button type="button" onclick="openNewPartyModal('complainant')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md hover:bg-emerald-100">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add New Person</span>
                </button>
            </div>

            <!-- Autocomplete Party Finder -->
            <div class="relative">
                <input
                    type="text"
                    id="search-complainant"
                    placeholder="Type to search existing directory by name or contact..."
                    class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    oninput="handlePartySearch(this.value, 'complainant')"
                />
                <div id="results-complainant" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-60 overflow-y-auto divide-y divide-slate-100"></div>
            </div>

            <!-- Selected Complainants Container -->
            <div id="selected-complainants" class="space-y-2">
                <p id="empty-complainants-hint" class="text-xs text-slate-400 italic">No complainants added yet. Search directory or add a new person above.</p>
            </div>
        </x-ui.card>

        <!-- Card C: Respondent(s) -->
        <x-ui.card class="p-6 border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-user-shield text-amber-600"></i>
                        <span>Card C: Respondent(s)</span>
                        <span class="text-red-500 text-xs">*</span>
                    </h2>
                    <p class="text-xs text-slate-500">Opposing party subject to conciliation and summons</p>
                </div>
                <button type="button" onclick="openNewPartyModal('respondent')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-md hover:bg-amber-100">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add New Person</span>
                </button>
            </div>

            <!-- Autocomplete Party Finder -->
            <div class="relative">
                <input
                    type="text"
                    id="search-respondent"
                    placeholder="Type to search existing directory by name or contact..."
                    class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    oninput="handlePartySearch(this.value, 'respondent')"
                />
                <div id="results-respondent" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-60 overflow-y-auto divide-y divide-slate-100"></div>
            </div>

            <!-- Selected Respondents Container -->
            <div id="selected-respondents" class="space-y-2">
                <p id="empty-respondents-hint" class="text-xs text-slate-400 italic">No respondents added yet. Search directory or add a new person above.</p>
            </div>
        </x-ui.card>

        <!-- Card D: Witnesses (Optional) -->
        <x-ui.card class="p-6 border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-users text-purple-600"></i>
                        <span>Card D: Witnesses (Optional)</span>
                    </h2>
                    <p class="text-xs text-slate-500">Supporting witnesses or related parties</p>
                </div>
                <button type="button" onclick="openNewPartyModal('witness')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add New Person</span>
                </button>
            </div>

            <!-- Autocomplete Party Finder -->
            <div class="relative">
                <input
                    type="text"
                    id="search-witness"
                    placeholder="Type to search existing directory..."
                    class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    oninput="handlePartySearch(this.value, 'witness')"
                />
                <div id="results-witness" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-60 overflow-y-auto divide-y divide-slate-100"></div>
            </div>

            <!-- Selected Witnesses Container -->
            <div id="selected-witnesses" class="space-y-2">
                <p id="empty-witnesses-hint" class="text-xs text-slate-400 italic">No witnesses added yet (optional).</p>
            </div>
        </x-ui.card>

        <!-- Sticky Footer Bar -->
        <div class="fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur border-t border-slate-200 py-3.5 px-6 shadow-lg z-30">
            <div class="max-w-5xl mx-auto flex items-center justify-between">
                <a href="{{ route('admin.cases.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">
                    Cancel
                </a>
                <div class="flex items-center gap-3">
                    <button type="submit" name="action" value="issue_summons" class="px-4 py-2 text-sm font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                        Save & Issue Summons
                    </button>
                    <button type="submit" name="action" value="docket" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                        Save & Docket Case
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal: Add New Person Inline -->
<div id="new-party-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900" id="modal-party-title">Add Person to Case</h3>
            <button type="button" onclick="closeNewPartyModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="space-y-3 text-sm">
            <input type="hidden" id="modal-role" value="complainant">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Full Legal Name <span class="text-red-500">*</span></label>
                <input type="text" id="modal-fullname" class="w-full text-sm border border-slate-300 rounded p-2" placeholder="e.g. Maria Santos Dela Cruz">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Residency Status <span class="text-red-500">*</span></label>
                    <select id="modal-residency" class="w-full text-sm border border-slate-300 rounded p-2" onchange="toggleNonResidentFields(this.value)">
                        <option value="resident">Resident</option>
                        <option value="non_resident">Non-Resident</option>
                        <option value="unverified">Unverified</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Purok</label>
                    <select id="modal-purok" class="w-full text-sm border border-slate-300 rounded p-2">
                        <option value="">Select Purok...</option>
                        @foreach($puroks as $pk)
                            <option value="{{ $pk->id }}">{{ $pk->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="modal-nonresident-block" class="hidden grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded border border-slate-200">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Home Barangay</label>
                    <input type="text" id="modal-home-barangay" class="w-full text-sm border border-slate-300 rounded p-2" placeholder="e.g. Baliwasan">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Home City</label>
                    <input type="text" id="modal-home-city" class="w-full text-sm border border-slate-300 rounded p-2" value="Zamboanga City">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Number</label>
                    <input type="text" id="modal-contact" class="w-full text-sm border border-slate-300 rounded p-2" placeholder="0917-000-0000">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Residency Verification Basis</label>
                    <select id="modal-basis" class="w-full text-sm border border-slate-300 rounded p-2">
                        <option value="Barangay ID">Barangay ID</option>
                        <option value="Household List">Household List</option>
                        <option value="Purok Leader Confirms">Purok Leader Confirms</option>
                        <option value="Other">Other Document</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Address Line</label>
                <input type="text" id="modal-address" class="w-full text-sm border border-slate-300 rounded p-2" placeholder="Street name / House number">
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" onclick="closeNewPartyModal()" class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </button>
            <button type="button" onclick="saveNewPartyFromModal()" class="px-4 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded hover:bg-blue-700">
                Add to Case
            </button>
        </div>
    </div>
</div>

<script>
    let partyIndex = 0;

    function handlePartySearch(query, role) {
        const resultsContainer = document.getElementById(`results-${role}`);
        if (!query || query.trim().length < 2) {
            resultsContainer.classList.add('hidden');
            resultsContainer.innerHTML = '';
            return;
        }

        fetch(`{{ route('admin.parties.search') }}?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                resultsContainer.innerHTML = '';
                if (!data.parties || data.parties.length === 0) {
                    resultsContainer.innerHTML = '<div class="p-3 text-xs text-slate-500">No matching parties found. Use "Add New Person".</div>';
                    resultsContainer.classList.remove('hidden');
                    return;
                }

                data.parties.forEach(party => {
                    const item = document.createElement('div');
                    item.className = 'p-2.5 hover:bg-blue-50 cursor-pointer flex items-center justify-between text-xs transition-colors';
                    item.innerHTML = `
                        <div>
                            <span class="font-bold text-slate-800">${party.full_name}</span>
                            <span class="text-slate-500">(${party.purok_name} · ${party.contact_number || 'No phone'})</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold ${party.residency_status === 'resident' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'}">${party.residency_status}</span>
                    `;
                    item.onclick = () => {
                        addExistingParty(role, party);
                        resultsContainer.classList.add('hidden');
                        document.getElementById(`search-${role}`).value = '';
                    };
                    resultsContainer.appendChild(item);
                });
                resultsContainer.classList.remove('hidden');
            });
    }

    function addExistingParty(role, party) {
        const container = document.getElementById(`selected-${role}s`);
        document.getElementById(`empty-${role}s-hint`).classList.add('hidden');

        const card = document.createElement('div');
        card.id = `party-row-${partyIndex}`;
        card.className = 'flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200 rounded-md text-xs';
        card.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-blue-600"></i>
                <span class="font-bold text-slate-800">${party.full_name}</span>
                <span class="text-slate-500">(${party.purok_name} · ${party.residency_status})</span>
                <input type="hidden" name="parties[${partyIndex}][role]" value="${role}">
                <input type="hidden" name="parties[${partyIndex}][party_id]" value="${party.id}">
            </div>
            <button type="button" onclick="removePartyRow(${partyIndex}, '${role}')" class="text-red-500 hover:text-red-700 font-bold px-2 py-0.5">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;
        container.appendChild(card);
        partyIndex++;
    }

    function openNewPartyModal(role) {
        document.getElementById('modal-role').value = role;
        document.getElementById('modal-party-title').innerText = `Add New ${role.charAt(0).toUpperCase() + role.slice(1)}`;
        document.getElementById('modal-fullname').value = '';
        document.getElementById('modal-contact').value = '';
        document.getElementById('modal-address').value = '';
        document.getElementById('new-party-modal').classList.remove('hidden');
    }

    function closeNewPartyModal() {
        document.getElementById('new-party-modal').classList.add('hidden');
    }

    function toggleNonResidentFields(residency) {
        const block = document.getElementById('modal-nonresident-block');
        if (residency === 'non_resident') {
            block.classList.remove('hidden');
        } else {
            block.classList.add('hidden');
        }
    }

    function saveNewPartyFromModal() {
        const role = document.getElementById('modal-role').value;
        const fullName = document.getElementById('modal-fullname').value.trim();
        const residency = document.getElementById('modal-residency').value;
        const purokId = document.getElementById('modal-purok').value;
        const contact = document.getElementById('modal-contact').value.trim();
        const basis = document.getElementById('modal-basis').value;
        const address = document.getElementById('modal-address').value.trim();
        const homeBarangay = document.getElementById('modal-home-barangay').value.trim();
        const homeCity = document.getElementById('modal-home-city').value.trim();

        if (!fullName) {
            alert('Full legal name is required.');
            return;
        }

        const container = document.getElementById(`selected-${role}s`);
        document.getElementById(`empty-${role}s-hint`).classList.add('hidden');

        const card = document.createElement('div');
        card.id = `party-row-${partyIndex}`;
        card.className = 'flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200 rounded-md text-xs';
        card.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-emerald-600"></i>
                <span class="font-bold text-slate-800">${fullName} (New)</span>
                <span class="text-slate-500">(${residency})</span>
                <input type="hidden" name="parties[${partyIndex}][role]" value="${role}">
                <input type="hidden" name="parties[${partyIndex}][full_name]" value="${fullName}">
                <input type="hidden" name="parties[${partyIndex}][residency_status]" value="${residency}">
                <input type="hidden" name="parties[${partyIndex}][purok_id]" value="${purokId}">
                <input type="hidden" name="parties[${partyIndex}][contact_number]" value="${contact}">
                <input type="hidden" name="parties[${partyIndex}][residency_basis]" value="${basis}">
                <input type="hidden" name="parties[${partyIndex}][address_line]" value="${address}">
                <input type="hidden" name="parties[${partyIndex}][home_barangay]" value="${homeBarangay}">
                <input type="hidden" name="parties[${partyIndex}][home_city]" value="${homeCity}">
            </div>
            <button type="button" onclick="removePartyRow(${partyIndex}, '${role}')" class="text-red-500 hover:text-red-700 font-bold px-2 py-0.5">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;
        container.appendChild(card);
        partyIndex++;
        closeNewPartyModal();
    }

    function removePartyRow(index, role) {
        const row = document.getElementById(`party-row-${index}`);
        if (row) {
            row.remove();
        }
    }
</script>
@endsection
