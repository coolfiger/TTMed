<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Information Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="min-h-screen bg-slate-50 font-sans">

<div class="max-w-lg mx-auto px-4 py-8">

    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Patient Information Form</h1>
        <p class="text-slate-500 mt-1 text-sm">Please fill in your details. This form will expire once submitted.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
            @foreach($errors->all() as $error)
                <p class="text-sm text-red-700">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ url()->current() }}" class="space-y-6">
        @csrf

        {{-- PIN verification --}}
        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl px-5 py-4">
            <label class="block text-sm font-semibold text-indigo-800 mb-1">Enter your 4-digit PIN</label>
            <p class="text-xs text-indigo-500 mb-3">The PIN was shown to you at reception.</p>
            <input type="text" name="pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4"
                   class="w-full text-center text-3xl font-mono font-bold tracking-widest border-2 border-indigo-300 rounded-xl px-4 py-3 focus:border-indigo-600 focus:outline-none bg-white"
                   placeholder="••••" autocomplete="one-time-code" value="{{ old('pin') }}" required />
        </div>

        {{-- Personal details --}}
        <div class="bg-white border border-slate-200 rounded-2xl px-5 py-5 space-y-4">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Personal Details</h2>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">First Name *</label>
                    <input type="text" name="first_name" required
                           value="{{ old('first_name', $record->patient->first_name) }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Last Name *</label>
                    <input type="text" name="last_name" required
                           value="{{ old('last_name', $record->patient->last_name) }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Date of Birth</label>
                    <input type="date" name="date_of_birth"
                           value="{{ old('date_of_birth', $record->patient->date_of_birth?->format('Y-m-d')) }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Gender</label>
                    <select name="gender" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none bg-white">
                        <option value="">Select…</option>
                        <option value="male" {{ old('gender', $record->patient->gender) === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $record->patient->gender) === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender', $record->patient->gender) === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Contact --}}
        <div class="bg-white border border-slate-200 rounded-2xl px-5 py-5 space-y-4">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Contact</h2>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Phone Number</label>
                <input type="tel" name="phone_primary"
                       value="{{ old('phone_primary', $record->patient->phone_primary) }}"
                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Email Address</label>
                <input type="email" name="email"
                       value="{{ old('email', $record->patient->email) }}"
                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Street Address</label>
                <textarea name="address" rows="2"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none resize-none">{{ old('address', $record->patient->address) }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">City</label>
                    <input type="text" name="city"
                           value="{{ old('city', $record->patient->city) }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Occupation</label>
                    <input type="text" name="occupation"
                           value="{{ old('occupation', $record->patient->occupation) }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
                </div>
            </div>
        </div>

        {{-- Emergency contact --}}
        <div class="bg-white border border-slate-200 rounded-2xl px-5 py-5 space-y-4">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Emergency Contact</h2>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Full Name</label>
                    <input type="text" name="emergency_name"
                           value="{{ old('emergency_name') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Phone</label>
                    <input type="tel" name="emergency_phone"
                           value="{{ old('emergency_phone') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Relationship</label>
                <input type="text" name="emergency_relation" placeholder="e.g. Spouse, Parent, Sibling"
                       value="{{ old('emergency_relation') }}"
                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
            </div>
        </div>

        <div class="pt-2">
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-2xl text-base transition shadow-sm">
                Submit Information
            </button>
            <p class="text-xs text-slate-400 text-center mt-3">Your information is securely stored and only accessible to your medical team.</p>
        </div>

    </form>

</div>
</body>
</html>
