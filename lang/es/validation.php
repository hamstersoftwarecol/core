<?php

// Solo las reglas que usan las pantallas del core; el resto cae al inglés (fallback_locale).
return [
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'current_password' => 'La contraseña no es correcta.',
    'email' => ':Attribute debe ser un email válido.',
    'lowercase' => ':Attribute debe estar en minúsculas.',
    'max' => ['string' => ':Attribute no puede superar :max caracteres.'],
    'min' => ['string' => ':Attribute debe tener al menos :min caracteres.'],
    'password' => [
        'letters' => ':Attribute debe contener al menos una letra.',
        'mixed' => ':Attribute debe contener mayúsculas y minúsculas.',
        'numbers' => ':Attribute debe contener al menos un número.',
        'symbols' => ':Attribute debe contener al menos un símbolo.',
        'uncompromised' => 'Esta :attribute apareció en una filtración de datos. Elige otra.',
    ],
    'regex' => 'El formato de :attribute no es válido.',
    'required' => ':Attribute es obligatorio.',
    'string' => ':Attribute debe ser texto.',
    'unique' => 'Ese :attribute ya está en uso.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'email',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'current_password' => 'contraseña actual',
        'form.email' => 'email',
        'form.password' => 'contraseña',
        'newRole' => 'nombre del rol',
    ],
];
