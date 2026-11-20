<div class="flex h-full min-h-0 overflow-hidden bg-slate-100" x-data="{ editSection: null }">

    {{-- ══════════════════════════════════════════════
         LEFT SIDEBAR — Demographics + Contact + Insurance
    ══════════════════════════════════════════════ --}}
    <div class="w-80 flex-shrink-0 flex flex-col bg-white border-r border-slate-200 overflow-y-auto">

        {{-- Patient identity header --}}
        <div class="px-5 py-4 bg-gradient-to-br from-blue-600 to-blue-700 text-white flex-shrink-0">
            <div class="flex items-start gap-3">
                <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0 text-xl font-bold">
                    {{ mb_strtoupper(mb_substr($patient->first_name, 0, 1) . mb_substr($patient->last_name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-base font-bold leading-tight">{{ $patient->display_name }}</h1>
                    <p class="text-blue-200 text-xs mt-0.5">#{{ $patient->patient_number }}</p>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @if($patient->allergies->count() > 0)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold bg-red-500 text-white px-2 py-0.5 rounded-full">
                                ⚠ {{ $patient->allergies->count() }} {{ Str::plural('Allergy', $patient->allergies->count()) }}
                            </span>
                        @else
                            <span class="text-xs bg-white/20 text-blue-100 px-2 py-0.5 rounded-full">NKDA</span>
                        @endif
                        @if($patient->blood_type)
                            <span class="text-xs bg-white/20 text-white px-2 py-0.5 rounded-full font-semibold">{{ $patient->blood_type }}</span>
                        @endif
                        @if($patient->is_deceased)
                            <span class="text-xs bg-slate-700 text-slate-200 px-2 py-0.5 rounded-full">Deceased</span>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Quick actions --}}
            <div class="flex gap-2 mt-3 pt-3 border-t border-blue-500">
                <button wire:click="$set('showQueueForm', true)"
                        class="flex-1 text-xs font-semibold bg-emerald-500 hover:bg-emerald-400 text-white py-1.5 rounded-lg transition text-center">
                    + Queue
                </button>
                <a href="{{ route('tenant.patients.edit', $patient->id) }}"
                   class="flex-1 text-xs font-semibold bg-white/20 hover:bg-white/30 text-white py-1.5 rounded-lg transition text-center">
                    Edit Full
                </a>
                <button wire:click="generateIntakeQr"
                        class="flex-1 text-xs font-semibold bg-indigo-500 hover:bg-indigo-400 text-white py-1.5 rounded-lg transition text-center">
                    QR Form
                </button>
            </div>
        </div>

        {{-- Send to Queue form --}}
        @if($showQueueForm)
            <div class="px-4 py-4 bg-emerald-50 border-b border-emerald-200 flex-shrink-0">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-emerald-800">Send to Queue</p>
                    <button wire:click="$set('showQueueForm', false)" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
                </div>
                <div class="space-y-2">
                    <select wire:model="queueVisitType" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white">
                        <option value="walk_in">Walk-in</option>
                        <option value="appointment">Appointment</option>
                        <option value="emergency">Emergency</option>
                        <option value="follow_up">Follow-up</option>
                    </select>
                    <select wire:model="queueDoctorId" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white">
                        <option value="0">Any available doctor</option>
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}">{{ $doc->staffProfile?->full_name }}</option>
                        @endforeach
                    </select>
                    <input wire:model="queueComplaint" type="text" placeholder="Chief complaint (optional)"
                           class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                        <input type="checkbox" wire:model="queuePriority" class="rounded text-red-600" />
                        Mark as priority
                    </label>
                    <div class="flex gap-2">
                        <button wire:click="$set('showQueueForm', false)"
                                class="flex-1 text-sm text-slate-500 border border-slate-200 rounded-lg py-1.5">Cancel</button>
                        <button wire:click="sendToQueue"
                                class="flex-1 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg py-1.5 transition">
                            Check In
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- QR Panel --}}
        @if($qrSvg)
            <div class="px-4 py-4 bg-indigo-50 border-b border-indigo-200 flex-shrink-0">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-semibold text-indigo-800">Patient Self-Service Form</p>
                    <button wire:click="$set('qrSvg', '')" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
                </div>
                <div class="bg-white rounded-lg p-3 text-center">
                    {!! $qrSvg !!}
                    <p class="text-xs text-slate-500 mt-2">Scan QR code or visit the link</p>
                    <div class="mt-2 bg-indigo-100 rounded-lg px-4 py-2">
                        <p class="text-xs text-indigo-500 uppercase tracking-wide font-semibold mb-0.5">One-Time PIN</p>
                        <p class="text-3xl font-bold font-mono tracking-widest text-indigo-700">{{ $qrPin }}</p>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">Valid 24 hours · Single use</p>
                </div>
            </div>
        @endif

        {{-- ── Demographics section ── --}}
        <div class="px-4 py-3 border-b border-slate-100" x-data="{ editing: false }">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Demographics</p>
                <button @click="editing = !editing" class="text-xs text-blue-600 hover:text-blue-800" x-text="editing ? 'Cancel' : 'Edit'"></button>
            </div>

            <div x-show="!editing" class="space-y-1.5 text-sm">
                <div class="flex gap-2"><span class="text-slate-400 w-24 flex-shrink-0">Date of Birth</span><span class="text-slate-800">{{ $patient->date_of_birth?->format('d M Y') ?? '—' }} {{ $patient->age ? '('.$patient->age.' yrs)' : '' }}</span></div>
                <div class="flex gap-2"><span class="text-slate-400 w-24 flex-shrink-0">Gender</span><span class="text-slate-800">{{ ucfirst($patient->gender ?? '—') }}</span></div>
                <div class="flex gap-2"><span class="text-slate-400 w-24 flex-shrink-0">National ID</span><span class="text-slate-800">{{ $patient->national_id ?? '—' }}</span></div>
                <div class="flex gap-2"><span class="text-slate-400 w-24 flex-shrink-0">Passport</span><span class="text-slate-800">{{ $patient->passport_number ?? '—' }}</span></div>
                <div class="flex gap-2"><span class="text-slate-400 w-24 flex-shrink-0">Marital</span><span class="text-slate-800">{{ ucfirst(str_replace('_', ' ', $patient->marital_status ?? '—')) }}</span></div>
                <div class="flex gap-2"><span class="text-slate-400 w-24 flex-shrink-0">Blood Type</span><span class="text-slate-800 font-semibold">{{ $patient->blood_type ?? '—' }}</span></div>
            </div>

            <div x-show="editing" class="space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-slate-400">Title</label>
                        <select wire:model="editTitle" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5 bg-white">
                            <option value="">—</option>
                            <option>Mr</option><option>Mrs</option><option>Ms</option><option>Miss</option><option>Dr</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-400">Gender</label>
                        <select wire:model="editGender" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5 bg-white">
                            <option value="">—</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-xs text-slate-400">First Name</label>
                    <input wire:model="editFirstName" type="text" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                </div>
                <div>
                    <label class="text-xs text-slate-400">Last Name</label>
                    <input wire:model="editLastName" type="text" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                </div>
                <div>
                    <label class="text-xs text-slate-400">Date of Birth</label>
                    <input wire:model="editDob" type="date" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-slate-400">National ID</label>
                        <input wire:model="editNationalId" type="text" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                    </div>
                    <div>
                        <label class="text-xs text-slate-400">Blood Type</label>
                        <select wire:model="editBloodType" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5 bg-white">
                            <option value="">—</option>
                            @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt)
                                <option value="{{ $bt }}">{{ $bt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-xs text-slate-400">Marital Status</label>
                    <select wire:model="editMaritalStatus" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5 bg-white">
                        <option value="">—</option>
                        <option value="single">Single</option><option value="married">Married</option>
                        <option value="divorced">Divorced</option><option value="widowed">Widowed</option>
                        <option value="common_law">Common-law</option>
                    </select>
                </div>
                <button wire:click="saveDemographics" @click="editing = false"
                        class="w-full text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-1.5 transition">
                    Save Demographics
                </button>
            </div>
        </div>

        {{-- ── Contact section ── --}}
        <div class="px-4 py-3 border-b border-slate-100" x-data="{ editing: false }">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Contact</p>
                <button @click="editing = !editing" class="text-xs text-blue-600 hover:text-blue-800" x-text="editing ? 'Cancel' : 'Edit'"></button>
            </div>

            <div x-show="!editing" class="space-y-1.5 text-sm">
                <div class="flex gap-2"><span class="text-slate-400 w-20 flex-shrink-0">Phone</span><span class="text-slate-800">{{ $patient->phone_primary ?? '—' }}</span></div>
                @if($patient->phone_secondary)
                    <div class="flex gap-2"><span class="text-slate-400 w-20 flex-shrink-0">Alt Phone</span><span class="text-slate-800">{{ $patient->phone_secondary }}</span></div>
                @endif
                <div class="flex gap-2"><span class="text-slate-400 w-20 flex-shrink-0">Email</span><span class="text-slate-800 truncate">{{ $patient->email ?? '—' }}</span></div>
                <div class="flex gap-2"><span class="text-slate-400 w-20 flex-shrink-0">Address</span><span class="text-slate-800">{{ $patient->address ? $patient->address.($patient->city ? ', '.$patient->city : '') : '—' }}</span></div>
                @if($patient->occupation)
                    <div class="flex gap-2"><span class="text-slate-400 w-20 flex-shrink-0">Occupation</span><span class="text-slate-800">{{ $patient->occupation }}</span></div>
                @endif
            </div>

            <div x-show="editing" class="space-y-2">
                <div>
                    <label class="text-xs text-slate-400">Phone (Primary)</label>
                    <input wire:model="editPhone" type="tel" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                </div>
                <div>
                    <label class="text-xs text-slate-400">Phone (Alt)</label>
                    <input wire:model="editPhoneAlt" type="tel" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                </div>
                <div>
                    <label class="text-xs text-slate-400">Email</label>
                    <input wire:model="editEmail" type="email" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                </div>
                <div>
                    <label class="text-xs text-slate-400">Address</label>
                    <textarea wire:model="editAddress" rows="2" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-slate-400">City</label>
                        <input wire:model="editCity" type="text" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                    </div>
                    <div>
                        <label class="text-xs text-slate-400">Country</label>
                        <input wire:model="editCountry" type="text" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                    </div>
                </div>
                <div>
                    <label class="text-xs text-slate-400">Occupation</label>
                    <input wire:model="editOccupation" type="text" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                </div>
                <button wire:click="saveContact" @click="editing = false"
                        class="w-full text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-1.5 transition">
                    Save Contact
                </button>
            </div>
        </div>

        {{-- ── Insurance section ── --}}
        <div class="px-4 py-3 border-b border-slate-100" x-data="{ editing: false }">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Insurance</p>
                <button @click="editing = !editing" class="text-xs text-blue-600 hover:text-blue-800" x-text="editing ? 'Cancel' : 'Edit'"></button>
            </div>

            <div x-show="!editing" class="space-y-1.5 text-sm">
                <div class="flex gap-2"><span class="text-slate-400 w-20 flex-shrink-0">Provider</span><span class="text-slate-800">{{ $patient->insuranceProvider?->name ?? '—' }}</span></div>
                <div class="flex gap-2"><span class="text-slate-400 w-20 flex-shrink-0">Policy #</span><span class="text-slate-800">{{ $patient->insurance_policy_number ?? '—' }}</span></div>
                @if($patient->insurance_group_number)
                    <div class="flex gap-2"><span class="text-slate-400 w-20 flex-shrink-0">Group #</span><span class="text-slate-800">{{ $patient->insurance_group_number }}</span></div>
                @endif
            </div>

            <div x-show="editing" class="space-y-2">
                <div>
                    <label class="text-xs text-slate-400">Insurance Provider</label>
                    <select wire:model="editInsurerId" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5 bg-white">
                        <option value="">None</option>
                        @foreach($insurers as $ins)
                            <option value="{{ $ins->id }}">{{ $ins->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-slate-400">Policy Number</label>
                    <input wire:model="editPolicyNum" type="text" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                </div>
                <div>
                    <label class="text-xs text-slate-400">Group Number</label>
                    <input wire:model="editGroupNum" type="text" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5" />
                </div>
                <button wire:click="saveInsurance" @click="editing = false"
                        class="w-full text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-1.5 transition">
                    Save Insurance
                </button>
            </div>
        </div>

        {{-- ── Clinical (doctor + notes) ── --}}
        <div class="px-4 py-3" x-data="{ editing: false }">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Clinical</p>
                <button @click="editing = !editing" class="text-xs text-blue-600 hover:text-blue-800" x-text="editing ? 'Cancel' : 'Edit'"></button>
            </div>

            <div x-show="!editing" class="space-y-1.5 text-sm">
                <div class="flex gap-2"><span class="text-slate-400 w-20 flex-shrink-0">Doctor</span><span class="text-slate-800">{{ $patient->primaryDoctor?->staffProfile?->full_name ?? '—' }}</span></div>
                <div class="flex gap-2"><span class="text-slate-400 w-20 flex-shrink-0">Branch</span><span class="text-slate-800">{{ $patient->branch?->name ?? '—' }}</span></div>
                @if($patient->notes)
                    <div class="mt-2 text-xs text-slate-500 bg-amber-50 border border-amber-100 rounded px-2 py-1.5">{{ $patient->notes }}</div>
                @endif
            </div>

            <div x-show="editing" class="space-y-2">
                <div>
                    <label class="text-xs text-slate-400">Primary Doctor</label>
                    <select wire:model="editDoctorId" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5 bg-white">
                        <option value="">Not assigned</option>
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}">{{ $doc->staffProfile?->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-slate-400">Internal Notes</label>
                    <textarea wire:model="editNotes" rows="3" class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mt-0.5 resize-none"></textarea>
                </div>
                <button wire:click="saveClinical" @click="editing = false"
                        class="w-full text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-1.5 transition">
                    Save Clinical
                </button>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════
         MAIN CONTENT — Tabbed workspace
    ══════════════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col min-w-0 min-h-0">

        {{-- Tab nav + alerts --}}
        <div class="bg-white border-b border-slate-200 px-5 flex-shrink-0">
            <div class="flex items-center gap-1 overflow-x-auto">
                @foreach([
                    ['overview',  'Overview'],
                    ['medical',   'Medical'],
                    ['imaging',   'Imaging & Docs'],
                    ['history',   'Visit History'],
                ] as [$key, $label])
                    <button wire:click="$set('activeTab', '{{ $key }}')"
                            class="px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition
                                   {{ $activeTab === $key
                                       ? 'border-blue-600 text-blue-700'
                                       : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Success / error alerts --}}
        @if($successMessage)
            <div class="mx-5 mt-3 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800 flex items-center justify-between flex-shrink-0"
                 x-data x-init="setTimeout(() => $el.remove(), 4000)">
                <span>{{ $successMessage }}</span>
                <button wire:click="$set('successMessage', '')" class="text-emerald-500 hover:text-emerald-700 text-xs ml-3">✕</button>
            </div>
        @endif
        @if($errorMessage)
            <div class="mx-5 mt-3 rounded-lg bg-red-50 border border-red-200 px-4 py-2 text-sm text-red-800 flex items-center justify-between flex-shrink-0">
                {{ $errorMessage }}
                <button wire:click="$set('errorMessage', '')" class="text-red-500 text-xs ml-3">✕</button>
            </div>
        @endif

        {{-- Tab content --}}
        <div class="flex-1 overflow-y-auto px-5 py-4">

            {{-- ── OVERVIEW TAB ── --}}
            @if($activeTab === 'overview')
                <div class="max-w-3xl space-y-5">

                    {{-- Last visit --}}
                    @php $lastVisit = $encounters->first(); @endphp
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last Visit</p>
                        </div>
                        @if($lastVisit)
                            <div class="px-4 py-4">
                                <div class="flex items-start justify-between gap-4 mb-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">{{ $lastVisit->encounter_date?->format('d M Y') }}</p>
                                        @if($lastVisit->chief_complaint)
                                            <p class="text-sm text-slate-600 mt-0.5">{{ $lastVisit->chief_complaint }}</p>
                                        @endif
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $lastVisit->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex-shrink-0">
                                        {{ ucfirst(str_replace('_', ' ', $lastVisit->status)) }}
                                    </span>
                                </div>
                                @php
                                    $lastNote = \App\Models\Tenant\ConsultationNote::on('tenant')
                                        ->where('encounter_id', $lastVisit->id)->first();
                                @endphp
                                @if($lastNote)
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        @if($lastNote->assessment)
                                            <div>
                                                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Assessment</p>
                                                <p class="text-slate-700">{{ $lastNote->assessment }}</p>
                                            </div>
                                        @endif
                                        @if($lastNote->plan)
                                            <div>
                                                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Plan</p>
                                                <p class="text-slate-700">{{ $lastNote->plan }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="px-4 py-8 text-center text-sm text-slate-400 italic">No visits on record.</p>
                        @endif
                    </div>

                    {{-- Stats grid --}}
                    <div class="grid grid-cols-4 gap-3">
                        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 text-center">
                            <p class="text-2xl font-bold text-blue-600">{{ $encounters->count() }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Total Visits</p>
                        </div>
                        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 text-center">
                            <p class="text-2xl font-bold {{ $patient->allergies->count() > 0 ? 'text-red-600' : 'text-slate-400' }}">
                                {{ $patient->allergies->count() }}
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">Allergies</p>
                        </div>
                        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 text-center">
                            <p class="text-2xl font-bold text-purple-600">{{ $patient->medications->count() }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Active Meds</p>
                        </div>
                        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 text-center">
                            <p class="text-2xl font-bold text-indigo-600">{{ $imagingOrders->count() }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Imaging</p>
                        </div>
                    </div>

                    {{-- Active alerts (allergies + conditions) --}}
                    @if($patient->allergies->count() > 0)
                        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-red-700 mb-2">⚠ Known Allergies</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($patient->allergies as $allergy)
                                    <span class="text-xs bg-red-100 border border-red-200 text-red-800 px-2.5 py-1 rounded-full font-medium">
                                        {{ $allergy->allergen }}
                                        @if($allergy->reaction) → {{ $allergy->reaction }} @endif
                                        @if($allergy->severity) ({{ ucfirst($allergy->severity) }}) @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($patient->conditions->where('status', 'active')->count() > 0 || $patient->conditions->where('status', 'chronic')->count() > 0)
                        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 mb-2">Active Conditions</p>
                            <div class="space-y-1">
                                @foreach($patient->conditions->whereIn('status', ['active', 'chronic']) as $cond)
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full {{ $cond->status === 'chronic' ? 'bg-orange-400' : 'bg-amber-500' }} flex-shrink-0"></span>
                                        <span class="text-sm text-slate-800">{{ $cond->condition_name }}</span>
                                        <span class="text-xs text-slate-400">{{ ucfirst($cond->status) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            @endif

            {{-- ── MEDICAL TAB ── --}}
            @if($activeTab === 'medical')
                <div class="max-w-3xl space-y-5">

                    {{-- Allergies --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Allergies</p>
                            <button wire:click="$set('showAllergyForm', !$showAllergyForm)"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                {{ $showAllergyForm ? 'Cancel' : '+ Add' }}
                            </button>
                        </div>
                        @if($showAllergyForm)
                            <div class="px-4 py-3 bg-blue-50 border-b border-blue-100">
                                <div class="grid grid-cols-3 gap-2">
                                    <input wire:model="allergyAllergen" type="text" placeholder="Allergen *"
                                           class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                    <input wire:model="allergyReaction" type="text" placeholder="Reaction"
                                           class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                    <select wire:model="allergySeverity" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white">
                                        <option value="">Severity</option>
                                        <option value="mild">Mild</option><option value="moderate">Moderate</option><option value="severe">Severe</option>
                                    </select>
                                </div>
                                <button wire:click="saveAllergy" class="mt-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg transition">
                                    Save Allergy
                                </button>
                            </div>
                        @endif
                        <div class="p-4">
                            @forelse($patient->allergies as $allergy)
                                <div class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                        <span class="text-sm font-medium text-red-800">{{ $allergy->allergen }}</span>
                                        @if($allergy->reaction)
                                            <span class="text-sm text-slate-500">→ {{ $allergy->reaction }}</span>
                                        @endif
                                        @if($allergy->severity)
                                            <span class="text-xs px-1.5 py-0.5 rounded {{ $allergy->severity === 'severe' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ ucfirst($allergy->severity) }}
                                            </span>
                                        @endif
                                    </div>
                                    <button wire:click="removeAllergy({{ $allergy->id }})"
                                            wire:confirm="Remove this allergy?"
                                            class="text-xs text-slate-400 hover:text-red-600 transition">Remove</button>
                                </div>
                            @empty
                                <p class="text-sm text-slate-400 italic text-center py-3">No known allergies.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Conditions --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conditions / Diagnoses</p>
                            <button wire:click="$set('showConditionForm', !$showConditionForm)"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                {{ $showConditionForm ? 'Cancel' : '+ Add' }}
                            </button>
                        </div>
                        @if($showConditionForm)
                            <div class="px-4 py-3 bg-blue-50 border-b border-blue-100">
                                <div class="grid grid-cols-2 gap-2 mb-2">
                                    <input wire:model="conditionName" type="text" placeholder="Condition name *" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white col-span-2" />
                                    <input wire:model="conditionIcd10" type="text" placeholder="ICD-10 code (optional)" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                    <input wire:model="conditionDate" type="date" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                    <select wire:model="conditionStatus" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white">
                                        <option value="active">Active</option><option value="chronic">Chronic</option>
                                        <option value="in_remission">In Remission</option><option value="resolved">Resolved</option>
                                    </select>
                                    <input wire:model="conditionNotes" type="text" placeholder="Notes" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                </div>
                                <button wire:click="saveCondition" class="text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg transition">
                                    Save Condition
                                </button>
                            </div>
                        @endif
                        <div class="p-4">
                            @forelse($patient->conditions as $cond)
                                <div class="flex items-start gap-2 py-2 border-b border-slate-50 last:border-0">
                                    <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0
                                        {{ $cond->status === 'active' ? 'bg-amber-500' : ($cond->status === 'chronic' ? 'bg-orange-500' : 'bg-slate-300') }}"></span>
                                    <div>
                                        <span class="text-sm font-medium text-slate-800">{{ $cond->condition_name }}</span>
                                        @if($cond->icd10_code)
                                            <span class="ml-2 text-xs font-mono text-blue-600">{{ $cond->icd10_code }}</span>
                                        @endif
                                        <span class="ml-2 text-xs text-slate-400">{{ ucfirst($cond->status) }}</span>
                                        @if($cond->diagnosed_date)
                                            <span class="text-xs text-slate-400">· {{ \Carbon\Carbon::parse($cond->diagnosed_date)->format('M Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-slate-400 italic text-center py-3">No conditions recorded.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Medications --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current Medications</p>
                            <button wire:click="$set('showMedicationForm', !$showMedicationForm)"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                {{ $showMedicationForm ? 'Cancel' : '+ Add' }}
                            </button>
                        </div>
                        @if($showMedicationForm)
                            <div class="px-4 py-3 bg-blue-50 border-b border-blue-100">
                                <div class="grid grid-cols-2 gap-2 mb-2">
                                    <input wire:model="medicationName" placeholder="Medication name *" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white col-span-2" />
                                    <input wire:model="medicationDosage" placeholder="Dose (e.g. 500mg)" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                    <input wire:model="medicationFrequency" placeholder="Frequency (e.g. BD)" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                    <input wire:model="medicationRoute" placeholder="Route (e.g. Oral)" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                    <input wire:model="medicationStartDate" type="date" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                </div>
                                <button wire:click="saveMedication" class="text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg transition">
                                    Save Medication
                                </button>
                            </div>
                        @endif
                        <div class="p-4">
                            @forelse($patient->medications as $med)
                                <div class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                                    <div>
                                        <span class="text-sm font-medium text-slate-800">{{ $med->medication_name }}</span>
                                        <span class="text-sm text-slate-500 ml-2">{{ $med->dosage }} {{ $med->frequency }}{{ $med->route ? ' · '.$med->route : '' }}</span>
                                    </div>
                                    <button wire:click="discontinueMedication({{ $med->id }})"
                                            wire:confirm="Mark as discontinued?"
                                            class="text-xs text-slate-400 hover:text-orange-600 transition">Stop</button>
                                </div>
                            @empty
                                <p class="text-sm text-slate-400 italic text-center py-3">No active medications.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Medical history --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Medical &amp; Family History</p>
                        </div>
                        <div class="p-4 grid grid-cols-2 gap-4">
                            @foreach([
                                ['surgicalHistory',    'Surgical History'],
                                ['familyHistory',      'Family History'],
                                ['socialHistory',      'Social History'],
                                ['obstetricHistory',   'Obstetric History'],
                                ['immunisationHistory','Immunisation History'],
                                ['otherNotes',         'Other Notes'],
                            ] as [$field, $label])
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">{{ $label }}</label>
                                    <textarea wire:model="{{ $field }}" rows="3"
                                              class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none bg-white"></textarea>
                                </div>
                            @endforeach
                        </div>
                        <div class="px-4 pb-4">
                            <button wire:click="saveMedicalHistory"
                                    class="text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg transition">
                                Save Medical History
                            </button>
                        </div>
                    </div>

                </div>
            @endif

            {{-- ── IMAGING & DOCS TAB ── --}}
            @if($activeTab === 'imaging')
                <div class="max-w-3xl space-y-5">

                    {{-- Upload imaging --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upload X-Ray / Imaging File</p>
                            <button wire:click="$set('showImagingUpload', !$showImagingUpload)"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                {{ $showImagingUpload ? 'Cancel' : '+ Upload' }}
                            </button>
                        </div>
                        @if($showImagingUpload)
                            <div class="px-4 py-4 bg-blue-50 border-b border-blue-100">
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="text-xs text-slate-500 font-semibold uppercase">Modality *</label>
                                        <select wire:model="imagingModality" class="mt-1 w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white">
                                            @foreach(\App\Models\Tenant\ImagingOrder::MODALITIES as $code => $label)
                                                <option value="{{ $code }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 font-semibold uppercase">Body Part *</label>
                                        <input wire:model="imagingBodyPart" type="text" placeholder="e.g. Chest, Left Knee"
                                               class="mt-1 w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 font-semibold uppercase">Study Date</label>
                                        <input wire:model="imagingStudyDate" type="date"
                                               class="mt-1 w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 font-semibold uppercase">Radiologist</label>
                                        <input wire:model="imagingRadiologist" type="text" placeholder="Optional"
                                               class="mt-1 w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 font-semibold uppercase">Findings</label>
                                        <textarea wire:model="imagingFindings" rows="2"
                                                  class="mt-1 w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white resize-none"></textarea>
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 font-semibold uppercase">Impression</label>
                                        <textarea wire:model="imagingImpression" rows="2"
                                                  class="mt-1 w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white resize-none"></textarea>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-xs text-slate-500 font-semibold uppercase">File (PDF or Image) *</label>
                                    <input wire:model="imagingFile" type="file" accept=".pdf,.jpg,.jpeg,.png,.tif,.tiff"
                                           class="mt-1 w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer" />
                                    <div wire:loading wire:target="imagingFile" class="text-xs text-blue-600 mt-1">Uploading...</div>
                                </div>
                                <button wire:click="uploadImaging"
                                        wire:loading.attr="disabled"
                                        class="text-sm font-semibold bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-5 py-2 rounded-lg transition">
                                    <span wire:loading.remove wire:target="uploadImaging">Upload Imaging</span>
                                    <span wire:loading wire:target="uploadImaging">Saving...</span>
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Imaging list --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Imaging Reports ({{ $imagingOrders->count() }})</p>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse($imagingOrders as $order)
                                @php $report = $order->report; @endphp
                                <div class="px-4 py-3 flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-indigo-700">{{ $order->modality }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-semibold text-slate-800">{{ $order->modality_label }} — {{ $order->body_part }}</p>
                                            @if(!$order->encounter_id)
                                                <span class="text-xs px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">Patient-provided</span>
                                            @endif
                                            <span class="text-xs text-slate-400">{{ $order->ordered_at?->format('d M Y') }}</span>
                                        </div>
                                        @if($report?->impression)
                                            <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $report->impression }}</p>
                                        @endif
                                        @if($report?->report_pdf_path)
                                            <a href="{{ route('tenant.imaging.report.view', $report->id) }}" target="_blank"
                                               class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 mt-1.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                View Report
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="px-4 py-10 text-center text-sm text-slate-400 italic">No imaging on record.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- General documents --}}
                    @if($documents->count() > 0)
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Documents ({{ $documents->count() }})</p>
                                <a href="{{ route('tenant.documents.index', $patient->id) }}"
                                   class="text-xs font-semibold text-blue-600 hover:text-blue-800">Manage →</a>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @foreach($documents->take(10) as $doc)
                                    <div class="px-4 py-3 flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-slate-800 truncate">{{ $doc->title }}</p>
                                            <p class="text-xs text-slate-400">{{ $doc->created_at?->format('d M Y') }} · {{ $doc->uploadedBy?->name ?? '—' }}</p>
                                        </div>
                                        <a href="{{ route('tenant.documents.download', $doc->id) }}" target="_blank"
                                           class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex-shrink-0">Download</a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            @endif

            {{-- ── VISIT HISTORY TAB ── --}}
            @if($activeTab === 'history')
                <div class="max-w-3xl space-y-3">

                    @forelse($encounters as $enc)
                    @php
                        $note     = $enc->consultationNote;
                        $vitals   = $enc->vitalSigns->last(); // most recent reading per visit
                        $diagnoses = $enc->diagnoses;
                        $rxList   = $enc->prescriptions;
                        $refs     = $enc->referrals;
                        $isRecent = $loop->index < 2;
                        $docName  = $enc->doctor?->staffProfile?->full_name ?? 'Unknown';
                        $branch   = $enc->branch?->name ?? '';
                    @endphp

                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden"
                         x-data="{ open: {{ $isRecent ? 'true' : 'false' }} }">

                        {{-- Header row - always visible --}}
                        <button @click="open = !open"
                                class="w-full px-5 py-4 flex items-start justify-between gap-4 text-left hover:bg-slate-50 transition">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-bold text-slate-800">{{ $enc->encounter_date?->format('d M Y') }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                        {{ $enc->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst(str_replace('_', ' ', $enc->status)) }}
                                    </span>
                                    @if($enc->visit_type)
                                    <span class="text-xs text-slate-400 font-medium">{{ ucfirst(str_replace('_', ' ', $enc->visit_type)) }}</span>
                                    @endif
                                    @if($branch)
                                    <span class="text-xs text-slate-400">· {{ $branch }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">Dr. {{ $docName }}
                                    @if($enc->chief_complaint) · <span class="italic">{{ $enc->chief_complaint }}</span>@endif
                                </p>
                                {{-- Inline vitals summary always visible --}}
                                @if($vitals)
                                <div class="flex flex-wrap gap-3 mt-2">
                                    @if($vitals->systolic_bp && $vitals->diastolic_bp)
                                    @php $highBP = $vitals->systolic_bp >= 140 || $vitals->diastolic_bp >= 90; @endphp
                                    <span class="text-xs font-semibold {{ $highBP ? 'text-red-600' : 'text-slate-600' }}">
                                        BP {{ $vitals->systolic_bp }}/{{ $vitals->diastolic_bp }} mmHg{{ $highBP ? ' ▲' : '' }}
                                    </span>
                                    @endif
                                    @if($vitals->pulse_rate)
                                    <span class="text-xs text-slate-500">HR {{ $vitals->pulse_rate }} bpm</span>
                                    @endif
                                    @if($vitals->temperature)
                                    <span class="text-xs text-slate-500">Temp {{ $vitals->temperature }}°C</span>
                                    @endif
                                    @if($vitals->weight_kg)
                                    <span class="text-xs text-slate-500">{{ $vitals->weight_kg }} kg</span>
                                    @endif
                                    @if($vitals->oxygen_saturation)
                                    <span class="text-xs text-slate-500">SpO₂ {{ $vitals->oxygen_saturation }}%</span>
                                    @endif
                                    @if($vitals->blood_glucose)
                                    <span class="text-xs text-slate-500">Glucose {{ $vitals->blood_glucose }} mmol/L</span>
                                    @endif
                                </div>
                                @endif
                            </div>
                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0 mt-1 transition-transform duration-150"
                                 :class="open ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Expanded detail --}}
                        <div x-show="open" x-cloak class="border-t border-slate-100 divide-y divide-slate-100">

                            {{-- Full vitals --}}
                            @if($vitals)
                            <div class="px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Vitals
                                    <span class="font-normal normal-case text-slate-400 ml-1">recorded {{ $vitals->recorded_at?->format('d M Y H:i') }}</span>
                                </p>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                                    @if($vitals->systolic_bp && $vitals->diastolic_bp)
                                    @php $highBP = $vitals->systolic_bp >= 140 || $vitals->diastolic_bp >= 90; @endphp
                                    <div>
                                        <p class="text-xs text-slate-400">Blood Pressure</p>
                                        <p class="font-semibold {{ $highBP ? 'text-red-600' : 'text-slate-800' }}">
                                            {{ $vitals->systolic_bp }}/{{ $vitals->diastolic_bp }} mmHg
                                        </p>
                                    </div>
                                    @endif
                                    @if($vitals->pulse_rate)
                                    <div>
                                        <p class="text-xs text-slate-400">Heart Rate</p>
                                        <p class="font-semibold text-slate-800">{{ $vitals->pulse_rate }} bpm {{ $vitals->pulse_rhythm ? '('.$vitals->pulse_rhythm.')' : '' }}</p>
                                    </div>
                                    @endif
                                    @if($vitals->respiratory_rate)
                                    <div>
                                        <p class="text-xs text-slate-400">Respiratory Rate</p>
                                        <p class="font-semibold text-slate-800">{{ $vitals->respiratory_rate }} /min</p>
                                    </div>
                                    @endif
                                    @if($vitals->temperature)
                                    <div>
                                        <p class="text-xs text-slate-400">Temperature</p>
                                        <p class="font-semibold text-slate-800">{{ $vitals->temperature }}°C {{ $vitals->temperature_site ? '('.$vitals->temperature_site.')' : '' }}</p>
                                    </div>
                                    @endif
                                    @if($vitals->oxygen_saturation)
                                    <div>
                                        <p class="text-xs text-slate-400">SpO₂</p>
                                        <p class="font-semibold {{ $vitals->oxygen_saturation < 95 ? 'text-red-600' : 'text-slate-800' }}">{{ $vitals->oxygen_saturation }}%</p>
                                    </div>
                                    @endif
                                    @if($vitals->weight_kg)
                                    <div>
                                        <p class="text-xs text-slate-400">Weight</p>
                                        <p class="font-semibold text-slate-800">{{ $vitals->weight_kg }} kg</p>
                                    </div>
                                    @endif
                                    @if($vitals->height_cm)
                                    <div>
                                        <p class="text-xs text-slate-400">Height</p>
                                        <p class="font-semibold text-slate-800">{{ $vitals->height_cm }} cm</p>
                                    </div>
                                    @endif
                                    @if($vitals->bmi)
                                    <div>
                                        <p class="text-xs text-slate-400">BMI</p>
                                        <p class="font-semibold text-slate-800">{{ number_format($vitals->bmi, 1) }}</p>
                                    </div>
                                    @endif
                                    @if($vitals->blood_glucose)
                                    <div>
                                        <p class="text-xs text-slate-400">Blood Glucose {{ $vitals->blood_glucose_timing ? '('.$vitals->blood_glucose_timing.')' : '' }}</p>
                                        <p class="font-semibold text-slate-800">{{ $vitals->blood_glucose }} mmol/L</p>
                                    </div>
                                    @endif
                                    @if($vitals->pain_score !== null)
                                    <div>
                                        <p class="text-xs text-slate-400">Pain Score</p>
                                        <p class="font-semibold text-slate-800">{{ $vitals->pain_score }}/10</p>
                                    </div>
                                    @endif
                                </div>
                                @if($vitals->notes)
                                <p class="text-xs text-slate-500 mt-2 italic">{{ $vitals->notes }}</p>
                                @endif
                            </div>
                            @endif

                            {{-- Doctor notes (SOAP) --}}
                            @if($note)
                            <div class="px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">
                                    Doctor Notes
                                    @if($note->is_finalized)
                                    <span class="ml-2 text-emerald-600 font-medium normal-case">✓ Finalized</span>
                                    @endif
                                </p>
                                <div class="space-y-3">
                                    @foreach([
                                        ['Subjective (History)',   $note->subjective],
                                        ['Objective / Findings',   $note->objective],
                                        ['Examination Findings',   $note->examination_findings],
                                        ['Assessment',             $note->assessment],
                                        ['Plan',                   $note->plan],
                                        ['Investigations Ordered', $note->investigations_ordered],
                                        ['Follow-up Instructions', $note->follow_up_instructions],
                                    ] as [$label, $value])
                                        @if($value)
                                        <div>
                                            <p class="text-xs font-semibold text-slate-400 mb-0.5">{{ $label }}</p>
                                            <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $value }}</p>
                                        </div>
                                        @endif
                                    @endforeach
                                    @if($note->follow_up_days)
                                    <p class="text-xs text-slate-500">Follow-up in <strong>{{ $note->follow_up_days }}</strong> day(s)</p>
                                    @endif
                                </div>
                            </div>
                            @endif

                            {{-- Diagnoses --}}
                            @if($diagnoses->count())
                            <div class="px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Diagnoses</p>
                                <div class="space-y-1.5">
                                    @foreach($diagnoses as $dx)
                                    <div class="flex items-start gap-2">
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0
                                            {{ $dx->diagnosis_type === 'primary' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ ucfirst($dx->diagnosis_type) }}
                                        </span>
                                        <span class="text-sm text-slate-700">{{ $dx->diagnosis_text }}
                                            @if($dx->icd10_code) <span class="text-xs text-slate-400">({{ $dx->icd10_code }})</span>@endif
                                        </span>
                                        @if($dx->certainty && $dx->certainty !== 'confirmed')
                                        <span class="text-xs text-slate-400 italic flex-shrink-0">{{ $dx->certainty }}</span>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            {{-- Prescriptions --}}
                            @if($rxList->count())
                            <div class="px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Prescriptions</p>
                                @foreach($rxList as $rx)
                                <div class="{{ !$loop->first ? 'mt-3 pt-3 border-t border-slate-100' : '' }}">
                                    <p class="text-xs text-slate-500 mb-1">{{ $rx->prescription_number }}
                                        <span class="ml-1 {{ $rx->status === 'issued' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst($rx->status) }}</span>
                                    </p>
                                    <div class="space-y-1">
                                        @foreach($rx->items as $item)
                                        <p class="text-sm text-slate-700">
                                            <strong>{{ $item->medication_name }}</strong>
                                            @if($item->strength) {{ $item->strength }}@endif
                                            @if($item->form) · {{ $item->form }}@endif
                                            @if($item->dose && $item->frequency) — {{ $item->dose }} {{ $item->frequency }}@endif
                                            @if($item->duration) for {{ $item->duration }} {{ $item->duration_unit }}@endif
                                        </p>
                                        @if($item->instructions)
                                        <p class="text-xs text-slate-400 pl-2">{{ $item->instructions }}</p>
                                        @endif
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            {{-- Referrals --}}
                            @if($refs->count())
                            <div class="px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Referrals</p>
                                <div class="space-y-2">
                                    @foreach($refs as $ref)
                                    <div class="text-sm text-slate-700">
                                        <span class="font-semibold">{{ $ref->referred_to_name }}</span>
                                        @if($ref->referred_to_specialty) · {{ $ref->referred_to_specialty }}@endif
                                        @if($ref->urgency && $ref->urgency !== 'routine')
                                        <span class="ml-1 text-xs font-medium text-amber-600 uppercase">{{ $ref->urgency }}</span>
                                        @endif
                                        @if($ref->reason)
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $ref->reason }}</p>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            {{-- Nothing captured --}}
                            @if(!$vitals && !$note && $diagnoses->isEmpty() && $rxList->isEmpty() && $refs->isEmpty())
                            <div class="px-5 py-4 text-sm text-slate-400 italic">No clinical details recorded for this visit.</div>
                            @endif

                        </div>{{-- end expanded --}}
                    </div>
                    @empty
                    <div class="bg-white rounded-xl border border-slate-200 px-5 py-12 text-center">
                        <p class="text-sm text-slate-400 italic">No visits on record.</p>
                    </div>
                    @endforelse

                </div>
            @endif

        </div>{{-- end tab content --}}
    </div>{{-- end main --}}

</div>
