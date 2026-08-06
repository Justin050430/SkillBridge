<button {{ $attributes->merge([
'class' => 'inline-flex items-center px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition shadow-sm'
]) }}>
    {{ $slot }}
</button>