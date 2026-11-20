<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Patient Registration</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <style>
        input, select, textarea {
            -webkit-appearance: none;
            font-size: 16px !important; /* prevents iOS zoom */
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">

<div class="max-w-lg mx-auto px-4 py-8">

    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-blue-600 mb-4">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">New Patient Registration</h1>
        <p class="text-sm text-slate-500 mt-1">Please fill in your details below</p>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
            <ul class="text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tenant.patient.register.submit', $token) }}" class="space-y-6">
        @csrf

        {{-- Personal Details --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-slate-50 border-b border-slate-200 px-5 py-3">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Personal Details</h2>
            </div>
            <div class="px-5 py-5 space-y-4">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}"
                               autocomplete="given-name" required
                               class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}"
                               autocomplete="family-name" required
                               class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                               autocomplete="bday"
                               class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Gender</label>
                        <select name="gender"
                                class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition bg-white">
                            <option value="">Select…</option>
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">National ID / Passport No.</label>
                    <input type="text" name="national_id" value="{{ old('national_id') }}"
                           autocomplete="off"
                           class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>

            </div>
        </div>

        {{-- Contact --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-slate-50 border-b border-slate-200 px-5 py-3">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Contact Information</h2>
            </div>
            <div class="px-5 py-5 space-y-4">

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Mobile / Phone</label>
                    <input type="tel" name="phone_primary" value="{{ old('phone_primary') }}"
                           inputmode="tel" autocomplete="tel"
                           class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           inputmode="email" autocomplete="email"
                           class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Address</label>
                    <textarea name="address" rows="2"
                              autocomplete="street-address"
                              class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition resize-none">{{ old('address') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">City</label>
                        <input type="text" name="city" value="{{ old('city') }}"
                               autocomplete="address-level2"
                               class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Occupation</label>
                        <input type="text" name="occupation" value="{{ old('occupation') }}"
                               autocomplete="organization-title"
                               class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                </div>

            </div>
        </div>

        {{-- Emergency Contact --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-slate-50 border-b border-slate-200 px-5 py-3">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Emergency Contact</h2>
            </div>
            <div class="px-5 py-5 space-y-4">

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Full Name</label>
                    <input type="text" name="emergency_name" value="{{ old('emergency_name') }}"
                           autocomplete="off"
                           class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Phone</label>
                        <input type="tel" name="emergency_phone" value="{{ old('emergency_phone') }}"
                               inputmode="tel"
                               class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Relationship</label>
                        <input type="text" name="emergency_relation" value="{{ old('emergency_relation') }}"
                               placeholder="e.g. Spouse"
                               class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                </div>

            </div>
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-2xl text-base transition shadow-sm active:scale-95">
            Submit Registration
        </button>

        <p class="text-center text-xs text-slate-400 pb-4">
            Your information is stored securely and used only for medical care.
        </p>

    </form>
</div>
</body>
</html>
