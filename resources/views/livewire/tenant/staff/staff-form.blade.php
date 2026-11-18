<div class="max-w-3xl space-y-6">
    @if(session('message'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">{{ session('message') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- User account --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            <h3 class="text-sm font-semibold text-slate-800">Account</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Username</label>
                    <input wire:model="username" type="text" {{ $staffProfile ? 'disabled' : '' }}
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('username') border-red-500 @enderror {{ $staffProfile ? 'bg-slate-50' : '' }}">
                    @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Password
                        @if($staffProfile) <span class="text-xs text-slate-400">(leave blank to keep)</span> @endif
                    </label>
                    <input wire:model="password" type="password" autocomplete="new-password"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('password') border-red-500 @enderror">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Roles</label>
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach($roles as $role)
                        <label class="flex items-center gap-2">
                            <input wire:model="roleIds" type="checkbox" value="{{ $role->id }}"
                                class="rounded border-slate-300 text-blue-600">
                            <span class="text-sm text-slate-700">{{ $role->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('roleIds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Personal details --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            <h3 class="text-sm font-semibold text-slate-800">Personal Details</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Title</label>
                    <select wire:model="title"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                        <option value="">—</option>
                        <option>Mr</option>
                        <option>Mrs</option>
                        <option>Ms</option>
                        <option>Dr</option>
                        <option>Prof</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">First Name</label>
                    <input wire:model="firstName" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('firstName') border-red-500 @enderror">
                    @error('firstName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Last Name</label>
                    <input wire:model="lastName" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('lastName') border-red-500 @enderror">
                    @error('lastName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Branch</label>
                    <select wire:model="branchId"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                        <option value="">No branch</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Phone</label>
                    <input wire:model="phone" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input wire:model="email" type="email"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('email') border-red-500 @enderror">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Doctor profile --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            <div class="flex items-center gap-3">
                <input wire:model.live="isDoctor" id="isDoctor" type="checkbox" class="rounded border-slate-300 text-blue-600">
                <label for="isDoctor" class="text-sm font-semibold text-slate-800">This staff member is a doctor</label>
            </div>

            @if($isDoctor)
            <div class="grid grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Registration Number</label>
                    <input wire:model="registrationNumber" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('registrationNumber') border-red-500 @enderror">
                    @error('registrationNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Specialty</label>
                    <input wire:model="specialty" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Prescription Pad Name</label>
                    <input wire:model="prescriptionName" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Prescription Footnote</label>
                    <input wire:model="prescriptionFootnote" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Signature (image)</label>
                    <input wire:model="signatureFile" type="file" accept="image/*"
                        class="mt-1 block w-full text-sm text-slate-600">
                    @error('signatureFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Stamp (image)</label>
                    <input wire:model="stampFile" type="file" accept="image/*"
                        class="mt-1 block w-full text-sm text-slate-600">
                    @error('stampFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Prescription Logo (image)</label>
                    <input wire:model="prescriptionLogoFile" type="file" accept="image/*"
                        class="mt-1 block w-full text-sm text-slate-600">
                    @error('prescriptionLogoFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <h4 class="text-sm font-medium text-slate-700 mb-3">Consulting Hours</h4>
                    <div class="space-y-2">
                        @foreach($dayNames as $day => $dayName)
                        <div class="flex items-center gap-4">
                            <span class="w-28 text-sm text-slate-600">{{ $dayName }}</span>
                            <label class="flex items-center gap-2">
                                <input wire:model="doctorHours.{{ $day }}.is_active" type="checkbox"
                                    class="rounded border-slate-300 text-blue-600">
                                <span class="text-xs text-slate-500">Available</span>
                            </label>
                            @if($doctorHours[$day]['is_active'] ?? false)
                            <input wire:model="doctorHours.{{ $day }}.start_time" type="time"
                                class="rounded-md border border-slate-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none">
                            <span class="text-sm text-slate-400">to</span>
                            <input wire:model="doctorHours.{{ $day }}.end_time" type="time"
                                class="rounded-md border border-slate-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none">
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                {{ $staffProfile ? 'Update Staff Member' : 'Create Staff Member' }}
            </button>
            <a href="{{ route('tenant.staff.index') }}"
                class="rounded-md px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
        </div>
    </form>
</div>
