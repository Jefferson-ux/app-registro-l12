<?php

/*
|--------------------------------------------------------------------------
| Prueba de la página pública de inicio
|--------------------------------------------------------------------------
|
| Comprueba que la ruta principal responda correctamente, muestre la vista
| de bienvenida y presente el nombre del producto junto con el enlace para
| ingresar a la aplicación.
|
*/

it('muestra la página pública de inicio con un enlace a la aplicación', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertViewIs('welcome')
        ->assertSeeText('Puntual')
        ->assertSeeText('Entrar a la app')
        ->assertSee('href="' . url('/app') . '"', false);
});
