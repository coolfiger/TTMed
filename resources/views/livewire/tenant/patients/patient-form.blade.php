<div class="max-w-4xl space-y-6">
    {{-- Duplicate warning --}}
    @if($showDuplicateWarning)
    <div class="rounded-lg border border-amber-300 bg-amber-50 p-5">
        <h3 class="text-sm font-semibold text-amber-800 mb-2">Possible duplicate patients found</h3>
        <p class="text-sm text-amber-700 mb-3">The following existing patients may match the information you entered:</p>
        <div class="space-y-2 mb-4">
            @foreach($duplicates as $dup)
            <div class="flex items-center justify-between bg-white rounded-md border border-amber-200 px-4 py-2">
                <div>
                    <span class="text-sm font-medium text-slate-800">{{ $dup['display_name'] }}</span>
                    <span class="ml-2 text-xs font-mono text-slate-500">{{ $dup['patient_number'] }}</span>
                    @if($dup['date_of_birth'])
                        <span class="ml-2 text-xs text-slate-500">DOB: {{ $dup['date_of_birth'] }}</span>
                    @endif
                    @if($dup['phone_primary'])
                        <span class="ml-2 text-xs text-slate-500">{{ $dup['phone_primary'] }}</span>
                    @endif
                </div>
                <a href="{{ route('tenant.patients.show', $dup['id']) }}" target="_blank"
                    class="text-xs text-blue-600 hover:underline">View</a>
            </div>
            @endforeach
        </div>
        <div class="flex gap-3">
            <button wire:click="confirmAndSave"
                class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-500">
                Register as new patient anyway
            </button>
            <button wire:click="$set('showDuplicateWarning', false)"
                class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Go back and review
            </button>
        </div>
    </div>
    @endif

    <form wire:submit="checkDuplicates" class="space-y-6">
        {{-- Personal information --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            <h3 class="text-sm font-semibold text-slate-800">Personal Information</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Title</label>
                    <select wire:model="title"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                        <option value="">—</option>
                        @foreach(['Mr','Mrs','Ms','Miss','Dr','Prof','Rev'] as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">First Name <span class="text-red-500">*</span></label>
                    <input wire:model="firstName" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('firstName') border-red-500 @enderror">
                    @error('firstName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Last Name <span class="text-red-500">*</span></label>
                    <input wire:model="lastName" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('lastName') border-red-500 @enderror">
                    @error('lastName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Middle Name</label>
                    <input wire:model="middleName" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Preferred Name</label>
                    <input wire:model="preferredName" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Gender</label>
                    <select wire:model="gender"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                        <option value="">—</option>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                        <option value="O">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Date of Birth</label>
                    <input wire:model="dateOfBirth" type="date"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('dateOfBirth') border-red-500 @enderror">
                    @error('dateOfBirth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Blood Type</label>
                    <select wire:model="bloodType"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                        <option value="">—</option>
                        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt)
                        <option value="{{ $bt }}">{{ $bt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Marital Status</label>
                    <select wire:model="maritalStatus"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                        <option value="">—</option>
                        @foreach(['Single','Married','Divorced','Widowed','Common Law','Separated'] as $ms)
                        <option value="{{ $ms }}">{{ $ms }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">National ID</label>
                    <input wire:model="nationalId" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm font-mono focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Passport Number</label>
                    <input wire:model="passportNumber" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm font-mono focus:border-blue-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- Contact --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            <h3 class="text-sm font-semibold text-slate-800">Contact Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Primary Phone</label>
                    <input wire:model="phonePrimary" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Secondary Phone</label>
                    <input wire:model="phoneSecondary" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input wire:model="email" type="email"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('email') border-red-500 @enderror">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Address</label>
                    <textarea wire:model="address" rows="2"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">City</label>
                    <input wire:model="city" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Country</label>
                    <input wire:model="country" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Occupation</label>
                    <input wire:model="occupation" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Employer</label>
                    <input wire:model="employer" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- Insurance --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            <h3 class="text-sm font-semibold text-slate-800">Insurance</h3>
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-slate-700">Insurance Provider</label>
                    <select wire:model="insuranceProviderId"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                        <option value="">None</option>
                        @foreach($insurers as $ins)
                        <option value="{{ $ins->id }}">{{ $ins->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Policy Number</label>
                    <input wire:model="insurancePolicyNumber" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm font-mono focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Group Number</label>
                    <input wire:model="insuranceGroupNumber" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm font-mono focus:border-blue-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- Clinical assignment --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            <h3 class="text-sm font-semibold text-slate-800">Clinical Assignment</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Primary Doctor</label>
                    <select wire:model="primaryDoctorId"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                        <option value="">Not assigned</option>
                        @foreach($doctors as $doc)
                        <option value="{{ $doc->id }}">{{ $doc->staffProfile->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Branch</label>
                    <select wire:model="branchId"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                        <option value="">Not assigned</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Notes</label>
                    <textarea wire:model="notes" rows="2"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"></textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                {{ $patient ? 'Update Patient' : 'Register Patient' }}
            </button>
            <a href="{{ route('tenant.patients.index') }}"
                class="rounded-md px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
        </div>
    </form>
</div>
