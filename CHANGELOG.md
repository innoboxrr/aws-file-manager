# Changelog

## [Unreleased]

## [2.0.0] - 2026-09-13

Lo que necesita una aplicación nueva de Laravel 13, con un bucket creado hoy, para
usar el gestor de archivos desde la SPA de administración.

### Para actualizar desde 1.x

- **ACL.** Si tu bucket admite ACL de objeto y quieres seguir subiendo archivos
  públicos o cambiando su visibilidad, pon `AWS_FILE_MANAGER_USE_ACL=true`. Sin
  esa variable las subidas quedan privadas, pedir `public` responde 422 y cambiar
  la visibilidad responde 422.
- **Carpeta del usuario.** Borrar y cambiar la visibilidad ya no alcanzan claves
  fuera de `file-manager/{id}/`: responden 403.

### Añadido

- Clave de configuración `use_acl` (`AWS_FILE_MANAGER_USE_ACL`), apagada por defecto.
- `key` en cada entrada del índice: la clave que aceptan borrar y cambiar la
  visibilidad.
- Borrar y cambiar la visibilidad aceptan la clave completa o una ruta relativa a
  la carpeta del usuario.
- `S3Service::deleteObject()`. `S3Service` se registra como singleton y acepta un
  `S3Client` propio.
- Respuesta 503 con el motivo cuando faltan `AWS_BUCKET` o `AWS_DEFAULT_REGION`.
- Tests de comportamiento de los cinco endpoints contra un bucket en memoria detrás
  del cliente real del SDK, sin red.

### Cambiado

- La subida y el cambio de visibilidad aceptan `private`, `public` y `public-read`.
- La subida guarda el `Content-Type` del archivo.
- Sin llaves en la configuración se usa la cadena de credenciales del SDK (entorno,
  perfil, rol de la instancia) en lugar de credenciales vacías.
- `S3Service::validateUserPath()` lanza `AccessDeniedHttpException` (403) en lugar de
  `\Exception`, y compara con la barra final y sin segmentos `..`.
- Cambiar la visibilidad de un archivo que no existe responde 404.
- `composer.json` requiere `laravel/framework ^13.0` y `laravel/sanctum ^4.3` en lugar
  de `illuminate/support`. El paquete ya usaba Foundation y el guard `auth:sanctum`.
- `RouteServiceProvider`, `EventServiceProvider` y `AuthServiceProvider` heredan de
  `Illuminate\Support\ServiceProvider`. Las rutas usan callables. Las URIs, los
  nombres de ruta y las claves de configuración no cambian.

### Corregido

- Borrar un archivo respondía 500 siempre. Accedía al cliente privado de
  `S3Service`, y además calculaba la clave con una barra final.
- `change-visibility` aceptaba cualquier clave del bucket, incluidas las de otros
  usuarios.
- Una ruta fuera de la carpeta del usuario llegaba como 500. Ahora es 403.
- En un bucket nuevo no se podía subir nada: cada subida mandaba un ACL que S3
  rechaza.
- `php artisan migrate` fallaba en una aplicación nueva con `CACHE_STORE=database`:
  el arranque leía la caché antes de que existiera la tabla. Además, las claves
  `auth_policies` y `events_and_observers` eran compartidas con otros paquetes.
- La aplicación cargaba sus rutas una vez más por este paquete, y cada registro
  mandaba repetido el correo de verificación.
- El README documentaba una fachada que no existe.

## [1.0.0] - 2024-07-12
### Added
- Initial stable release.
