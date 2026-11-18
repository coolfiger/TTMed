<div>
    @if(session('message'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">{{ session('message') }}</div>
    @endif

    {{-- Patient header --}}
    <div class="bg-white rounded-lg border border-slate-200 p-5 mb-6 flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-slate-800">{{ $patient->full_name }}</h2>
                @if($patient->allergies->isNotEmpty())
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700">
                        ⚠ {{ $patient->allergies->count() }} {{ Str::plural('Allergy', $patient->allergies->count()) }}
                    </span>
                @endif
                @if($patient->is_deceased)
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-slate-200 text-slate-700">Deceased</span>
                @endif
            </div>
            <div class="mt-1 flex gap-4 text-sm text-slate-500">
                <span class="font-mono">{{ $patient->patient_number }}</span>
                @if($patient->date_of_birth)
                    <span>DOB: {{ $patient->date_of_birth->format('d M Y') }} ({{ $patient->age }}y)</span>
                @endif
                @if($patient->gender)
                    <span>{{ $patient->gender === 'M' ? 'Male' : ($patient->gender === 'F' ? 'Female' : 'Other') }}</span>
                @endif
                @if($patient->blood_type)
                    <span class="font-semibold text-red-600">{{ $patient->blood_type }}</span>
                @endif
            </div>
            <div class="mt-1 flex gap-4 text-sm text-slate-500">
                @if($patient->phone_primary)
                    <span>{{ $patient->phone_primary }}</span>
                @endif
                @if($patient->email)
                    <span>{{ $patient->email }}</span>
                @endif
                @if($patient->branch)
                    <span>Branch: {{ $patient->branch->name }}</span>
                @endif
            </div>
        </div>
        <a href="{{ route('tenant.patients.edit', $patient->id) }}"
            class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">
            Edit
        </a>
    </div>

    {{-- Tab navigation --}}
    <div class="flex border-b border-slate-200 mb-6 gap-1 flex-wrap">
        @foreach([
            'personal'  => 'Personal',
            'medical'   => 'Medical History',
            'consents'  => 'Consents',
            'file'      => 'Physical File',
        ] as $tab => $label)
        <button wire:click="$set('activeTab', '{{ $tab }}')"
            class="px-4 py-2 text-sm font-medium rounded-t-md -mb-px
                {{ $activeTab === $tab
                    ? 'bg-white border border-b-white border-slate-200 text-blue-700'
                    : 'text-slate-500 hover:text-slate-800' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- Personal tab --}}
    @if($activeTab === 'personal')
    <div class="grid grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-slate-200 p-5 space-y-3">
            <h3 class="text-sm font-semibold text-slate-800">Details</h3>
            @foreach([
                'National ID'      => $patient->national_id,
                'Passport'         => $patient->passport_number,
                'Marital Status'   => $patient->marital_status,
                'Occupation'       => $patient->occupation,
                'Employer'         => $patient->employer,
                'Country'          => $patient->country,
                'Primary Doctor'   => $patient->primaryDoctor?->staffProfile?->full_name,
                'Insurance'        => $patient->insuranceProvider?->name,
                'Policy #'         => $patient->insurance_policy_number,
            ] as $label => $value)
            @if($value)
            <div class="flex justify-between text-sm">
                <span class="text-slate-500">{{ $label }}</span>
                <span class="text-slate-800">{{ $value }}</span>
            </div>
            @endif
            @endforeach
        </div>

        <div class="space-y-5">
            {{-- Allergies --}}
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-semibold text-slate-800">Allergies</h3>
                    <button wire:click="$set('showAllergyForm', true)"
                        class="text-xs text-blue-600 hover:text-blue-800">+ Add</button>
                </div>
                @if($showAllergyForm)
                <form wire:submit="saveAllergy" class="space-y-2 mb-4 p-3 bg-slate-50 rounded-md">
                    <input wire:model="allergyAllergen" type="text" placeholder="Allergen *"
                        class="block w-full rounded border border-slate-300 px-2 py-1.5 text-sm @error('allergyAllergen') border-red-500 @enderror">
                    @error('allergyAllergen') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <input wire:model="allergyReaction" type="text" placeholder="Reaction"
                        class="block w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
                    <select wire:model="allergySeverity" class="block w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
                        <option value="">Severity</option>
                        <option value="mild">Mild</option>
                        <option value="moderate">Moderate</option>
                        <option value="severe">Severe</option>
                        <option value="life_threatening">Life-threatening</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded bg-blue-600 px-3 py-1 text-xs text-white">Save</button>
                        <button type="button" wire:click="$set('showAllergyForm', false)" class="text-xs text-slate-500">Cancel</button>
                    </div>
                </form>
                @endif
                @forelse($patient->allergies as $allergy)
                <div class="flex justify-between items-start py-1.5 border-b border-slate-100 last:border-0">
                    <div>
                        <span class="text-sm font-medium text-slate-800">{{ $allergy->allergen }}</span>
                        @if($allergy->severity)
                            <span class="ml-2 text-xs rounded-full px-1.5 py-0.5
                                {{ $allergy->severity === 'life_threatening' || $allergy->severity === 'severe' ? 'bg-red-100 text-red-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $allergy->severity_label }}
                            </span>
                        @endif
                        @if($allergy->reaction)
                            <div class="text-xs text-slate-500">{{ $allergy->reaction }}</div>
                        @endif
                    </div>
                    <button wire:click="removeAllergy({{ $allergy->id }})" class="text-xs text-slate-400 hover:text-red-500">Remove</button>
                </div>
                @empty
                <p class="text-sm text-slate-400">No known allergies recorded.</p>
                @endforelse
            </div>

            {{-- Emergency contacts --}}
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Emergency Contacts</h3>
                @forelse($patient->contacts->where('is_emergency_contact', true) as $contact)
                <div class="py-1.5 border-b border-slate-100 last:border-0">
                    <span class="text-sm font-medium text-slate-800">{{ $contact->name }}</span>
                    @if($contact->relationship) <span class="ml-1 text-xs text-slate-500">({{ $contact->relationship }})</span> @endif
                    @if($contact->phone) <div class="text-xs text-slate-500">{{ $contact->phone }}</div> @endif
                </div>
                @empty
                <p class="text-sm text-slate-400">No emergency contacts on record.</p>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    {{-- Medical History tab --}}
    @if($activeTab === 'medical')
    <div class="space-y-5">
        {{-- Conditions --}}
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-semibold text-slate-800">Conditions</h3>
                <button wire:click="$set('showConditionForm', true)" class="text-xs text-blue-600 hover:text-blue-800">+ Add</button>
            </div>
            @if($showConditionForm)
            <form wire:submit="saveCondition" class="space-y-2 mb-4 p-3 bg-slate-50 rounded-md grid grid-cols-2 gap-2">
                <div class="col-span-2">
                    <input wire:model="conditionName" type="text" placeholder="Condition name *"
                        class="block w-full rounded border border-slate-300 px-2 py-1.5 text-sm @error('conditionName') border-red-500 @enderror">
                    @error('conditionName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <input wire:model="conditionIcd10" type="text" placeholder="ICD-10 code"
                    class="block w-full rounded border border-slate-300 px-2 py-1.5 text-sm font-mono">
                <input wire:model="conditionDate" type="date"
                    class="block w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
                <select wire:model="conditionStatus" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
                    <option value="active">Active</option>
                    <option value="chronic">Chronic</option>
                    <option value="in_remission">In Remission</option>
                    <option value="resolved">Resolved</option>
                </select>
                <div class="col-span-2 flex gap-2">
                    <button type="submit" class="rounded bg-blue-600 px-3 py-1 text-xs text-white">Save</button>
                    <button type="button" wire:click="$set('showConditionForm', false)" class="text-xs text-slate-500">Cancel</button>
                </div>
            </form>
            @endif
            <table class="min-w-full text-sm">
                @forelse($patient->conditions as $cond)
                <tr class="border-b border-slate-100 last:border-0">
                    <td class="py-1.5 font-medium text-slate-800">{{ $cond->condition_name }}</td>
                    <td class="py-1.5 font-mono text-xs text-slate-500 pl-4">{{ $cond->icd10_code }}</td>
                    <td class="py-1.5 pl-4">
                        <span class="rounded-full px-2 py-0.5 text-xs
                            {{ $cond->status === 'active' ? 'bg-red-50 text-red-700' : ($cond->status === 'chronic' ? 'bg-amber-50 text-amber-700' : 'bg-green-50 text-green-700') }}">
                            {{ ucfirst(str_replace('_', ' ', $cond->status)) }}
                        </span>
                    </td>
                    <td class="py-1.5 pl-4 text-slate-500">{{ $cond->diagnosed_date?->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-2 text-slate-400">No conditions recorded.</td></tr>
                @endforelse
            </table>
        </div>

        {{-- Current medications --}}
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-semibold text-slate-800">Current Medications</h3>
                <button wire:click="$set('showMedicationForm', true)" class="text-xs text-blue-600 hover:text-blue-800">+ Add</button>
            </div>
            @if($showMedicationForm)
            <form wire:submit="saveMedication" class="space-y-2 mb-4 p-3 bg-slate-50 rounded-md grid grid-cols-2 gap-2">
                <div class="col-span-2">
                    <input wire:model="medicationName" type="text" placeholder="Medication name *"
                        class="block w-full rounded border border-slate-300 px-2 py-1.5 text-sm @error('medicationName') border-red-500 @enderror">
                    @error('medicationName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <input wire:model="medicationDosage" type="text" placeholder="Dosage (e.g. 10mg)"
                    class="rounded border border-slate-300 px-2 py-1.5 text-sm">
                <input wire:model="medicationFrequency" type="text" placeholder="Frequency (e.g. twice daily)"
                    class="rounded border border-slate-300 px-2 py-1.5 text-sm">
                <input wire:model="medicationRoute" type="text" placeholder="Route (oral, IV…)"
                    class="rounded border border-slate-300 px-2 py-1.5 text-sm">
                <input wire:model="medicationStartDate" type="date"
                    class="rounded border border-slate-300 px-2 py-1.5 text-sm">
                <div class="col-span-2">
                    <input wire:model="medicationPrescribedBy" type="text" placeholder="Prescribed by"
                        class="block w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
                </div>
                <div class="col-span-2 flex gap-2">
                    <button type="submit" class="rounded bg-blue-600 px-3 py-1 text-xs text-white">Save</button>
                    <button type="button" wire:click="$set('showMedicationForm', false)" class="text-xs text-slate-500">Cancel</button>
                </div>
            </form>
            @endif
            <table class="min-w-full text-sm">
                @forelse($patient->medications as $med)
                <tr class="border-b border-slate-100 last:border-0">
                    <td class="py-1.5 font-medium text-slate-800">{{ $med->medication_name }}</td>
                    <td class="py-1.5 pl-3 text-slate-600">{{ $med->dosage }}</td>
                    <td class="py-1.5 pl-3 text-slate-500">{{ $med->frequency }}</td>
                    <td class="py-1.5 pl-3 text-slate-500 text-xs">{{ $med->route }}</td>
                    <td class="py-1.5 pl-3 text-right">
                        <button wire:click="discontinueMedication({{ $med->id }})"
                            class="text-xs text-slate-400 hover:text-red-500">Discontinue</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-2 text-slate-400">No current medications.</td></tr>
                @endforelse
            </table>
        </div>

        {{-- Narrative history --}}
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Narrative History</h3>
            <form wire:submit="saveMedicalHistory" class="space-y-4">
                @foreach([
                    'surgicalHistory'     => 'Surgical History',
                    'familyHistory'       => 'Family History',
                    'socialHistory'       => 'Social History',
                    'obstetricHistory'    => 'Obstetric History',
                    'immunisationHistory' => 'Immunisation History',
                    'otherNotes'          => 'Other Notes',
                ] as $field => $label)
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ $label }}</label>
                    <textarea wire:model="{{ $field }}" rows="3"
                        class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"></textarea>
                </div>
                @endforeach
                <button type="submit"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                    Save History
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Consents tab --}}
    @if($activeTab === 'consents')
    <div class="bg-white rounded-lg border border-slate-200 p-5">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-semibold text-slate-800">Consent Records</h3>
            <button wire:click="$set('showConsentForm', true)" class="text-xs text-blue-600 hover:text-blue-800">+ Add</button>
        </div>
        @if($showConsentForm)
        <form wire:submit="saveConsent" class="space-y-3 mb-5 p-4 bg-slate-50 rounded-md">
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <input wire:model="consentType" type="text" placeholder="Consent type *"
                        class="block w-full rounded border border-slate-300 px-2 py-1.5 text-sm @error('consentType') border-red-500 @enderror">
                    @error('consentType') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <input wire:model="consentGivenBy" type="text" placeholder="Given by"
                    class="rounded border border-slate-300 px-2 py-1.5 text-sm">
                <input wire:model="consentRelationship" type="text" placeholder="Relationship to patient"
                    class="rounded border border-slate-300 px-2 py-1.5 text-sm">
                <input wire:model="consentDate" type="date"
                    class="rounded border border-slate-300 px-2 py-1.5 text-sm">
                <div class="col-span-2 flex gap-2">
                    <button type="submit" class="rounded bg-blue-600 px-3 py-1 text-xs text-white">Save</button>
                    <button type="button" wire:click="$set('showConsentForm', false)" class="text-xs text-slate-500">Cancel</button>
                </div>
            </div>
        </form>
        @endif
        <table class="min-w-full text-sm">
            @forelse($patient->consents as $consent)
            <tr class="border-b border-slate-100 last:border-0">
                <td class="py-2 font-medium text-slate-800">{{ $consent->consent_type }}</td>
                <td class="py-2 pl-4 text-slate-600">{{ $consent->given_by }}</td>
                <td class="py-2 pl-4 text-slate-500">{{ $consent->consent_date?->format('d M Y') }}</td>
                <td class="py-2 pl-4">
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $consent->is_given ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                        {{ $consent->is_given ? 'Given' : 'Withdrawn' }}
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="py-3 text-slate-400">No consent records.</td></tr>
            @endforelse
        </table>
    </div>
    @endif

    {{-- Physical file tab --}}
    @if($activeTab === 'file')
    <div class="bg-white rounded-lg border border-slate-200 p-5">
        @if($patient->physicalFile)
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-sm text-slate-500">File Number</p>
                <p class="text-lg font-mono font-semibold text-slate-800">{{ $patient->physicalFile->file_number ?? '—' }}</p>
                <p class="text-sm text-slate-600 mt-1">Current location: <strong>{{ $patient->physicalFile->location_summary }}</strong></p>
            </div>
            <button wire:click="$set('showMoveFileForm', true)"
                class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">
                Move File
            </button>
        </div>
        @else
        <p class="text-sm text-slate-400 mb-4">No physical file record yet.</p>
        @endif

        @if($showMoveFileForm)
        <form wire:submit="moveFile" class="space-y-3 mb-5 p-4 bg-slate-50 rounded-md max-w-md">
            <div>
                <label class="block text-sm font-medium text-slate-700">New Location <span class="text-red-500">*</span></label>
                <input wire:model="fileToLocation" type="text" placeholder="e.g. Cabinet A, Shelf 3"
                    class="mt-1 block w-full rounded border border-slate-300 px-2 py-1.5 text-sm @error('fileToLocation') border-red-500 @enderror">
                @error('fileToLocation') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Reason</label>
                <input wire:model="fileMoveReason" type="text"
                    class="mt-1 block w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded bg-blue-600 px-3 py-1.5 text-xs text-white">Save Movement</button>
                <button type="button" wire:click="$set('showMoveFileForm', false)" class="text-xs text-slate-500">Cancel</button>
            </div>
        </form>
        @endif

        @if($patient->physicalFile?->movements->isNotEmpty())
        <h4 class="text-sm font-semibold text-slate-700 mb-2">Movement History</h4>
        <table class="min-w-full text-sm">
            @foreach($patient->physicalFile->movements as $mov)
            <tr class="border-b border-slate-100 last:border-0">
                <td class="py-1.5 text-slate-500 text-xs">{{ $mov->moved_at->format('d M Y H:i') }}</td>
                <td class="py-1.5 pl-4 text-slate-500">{{ $mov->from_location ?? '—' }} → {{ $mov->to_location }}</td>
                <td class="py-1.5 pl-4 text-slate-500">{{ $mov->reason }}</td>
                <td class="py-1.5 pl-4 text-slate-400 text-xs">{{ $mov->movedBy?->name }}</td>
            </tr>
            @endforeach
        </table>
        @endif
    </div>
    @endif
</div>
