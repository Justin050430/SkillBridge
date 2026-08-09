@csrf

<div class="grid grid-cols-2 gap-6">

    <!-- Personal Information -->

    <div class="col-span-2">

        <h3 class="text-lg font-semibold text-slate-800">
            Personal Information
        </h3>

        <p class="text-sm text-slate-500 mt-1">
            Basic information about the user.
        </p>

        <hr class="mt-4">

    </div>
    
    <!-- First Name -->
    <div>

        <x-input-label for="first_name" :value="__('First Name')" />

        <x-text-input
            id="first_name"
            name="first_name"
            type="text"
            class="block mt-1 w-full"
            :value="old('first_name', $user->first_name ?? '')"
            maxlength="100"
            pattern="[\p{L}\p{M}' -]+"
            required />

        <x-input-error
            :messages="$errors->get('first_name')"
            class="mt-2" />

    </div>

    <!-- Last Name -->
    <div>

        <x-input-label for="last_name" :value="__('Last Name')" />

        <x-text-input
            id="last_name"
            name="last_name"
            type="text"
            class="block mt-1 w-full"
            :value="old('last_name', $user->last_name ?? '')"
            maxlength="100"
            pattern="[\p{L}\p{M}' -]+"
            required />

        <x-input-error
            :messages="$errors->get('last_name')"
            class="mt-2" />

    </div>

    <!-- Email -->
    <div class="col-span-2">

        <x-input-label for="email" :value="__('Email')" />

        <x-text-input
            id="email"
            name="email"
            type="email"
            class="block mt-1 w-full"
            :value="old('email', $user->email ?? '')"
            required />

        <x-input-error
            :messages="$errors->get('email')"
            class="mt-2" />

    </div>

    <!-- Account Information -->

    <div class="col-span-2 mt-4">

        <h3 class="text-lg font-semibold text-slate-800">
            Account Information
        </h3>

        <p class="text-sm text-slate-500 mt-1">
            Login credentials and system role.
        </p>

        <hr class="mt-4">

    </div>
    
    <!-- Role -->
    <div>

        <x-input-label
            for="role_id"
            :value="__('Role')" />

        <x-input-error
            :messages="$errors->get('role_id')"
            class="mt-2" />

        <select
            name="role_id"
            class="block mt-1 w-full rounded-lg border-gray-300">

            @foreach($roles as $role)

                <option
                    value="{{ $role->id }}"
                    @selected(old('role_id', $user->role_id ?? '') == $role->id)>

                    {{ $role->role_name }}

                </option>

            @endforeach

        </select>

    </div>

    <!-- Status (edit only - new accounts always start Active) -->
    @if(isset($user))

    <div>

        <x-input-label
            for="status"
            :value="__('Status')" />

        <x-input-error
            :messages="$errors->get('status')"
            class="mt-2" />

        <select
            name="status"
            class="block mt-1 w-full rounded-lg border-gray-300">

            @foreach(['Active', 'Inactive'] as $option)

                <option
                    value="{{ $option }}"
                    @selected(old('status', $user->status ?? 'Active') === $option)>

                    {{ $option }}

                </option>

            @endforeach

        </select>

    </div>

    @else

    <div></div>

    @endif

    <!-- Password -->
    <div>

        <x-input-label
            for="password"
            :value="isset($user)
            ? 'Password (Leave blank to keep current password)'
            : 'Password'" />

        <x-text-input
            id="password"
            name="password"
            type="password"
            class="block mt-1 w-full" />

        <x-input-error
            :messages="$errors->get('password')"
            class="mt-2" />

    </div>

    <!-- Confirm Password -->
    <div>

        <x-input-label
            for="password_confirmation"
            :value="__('Confirm Password')" />

        <x-text-input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            class="block mt-1 w-full" />

        <x-input-error
            :messages="$errors->get('password_confirmation')"
            class="mt-2" />

    </div>

</div>

<div class="flex justify-end gap-3 mt-8">

    <a
        href="{{ route('admin.users') }}"
        class="inline-flex items-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">

        Cancel

    </a>

    <button
        type="submit"
        class="inline-flex items-center px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition shadow-sm">

        {{ $buttonText }}

    </button>

</div>