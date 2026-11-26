# Política de seguridad de InfraWatch

## Alcance y soporte

| Línea | Estado de soporte | Condiciones |
|---|---|---|
| `master` / última versión formal | Mantenida activamente | Correcciones evaluadas caso por caso; no implica SLA |
| `2.x`, si se formalizan releases | Línea actual prevista | Actualizaciones de seguridad mediante nuevas versiones o en `master` |
| `1.x` / prototipos históricos | No mantenida | Actualizar a una versión mantenida |
| Forks y commits históricos de terceros | No garantizado | Responsabilidad de quien los mantiene |

Hasta que se publiquen tags oficiales, **no debe interpretarse `2.x` como una serie de releases disponible**. La decisión de soporte y publicación queda documentada en [docs/releases.md](docs/releases.md).

## Reportar una vulnerabilidad de forma privada

1. Comprueba la sección **Security** del repositorio: <https://github.com/Josue-Isai-Sanchez-Santos/infrawatch-monitoring-system/security>.
2. Si está habilitada la opción **Report a vulnerability** / *Private vulnerability reporting*, utilízala: <https://github.com/Josue-Isai-Sanchez-Santos/infrawatch-monitoring-system/security/advisories/new>.
3. Si esa opción no aparece, abre **únicamente una Issue genérica solicitando un canal privado**, sin detalles técnicos, secretos ni pruebas de explotación. El mantenedor acordará un medio apropiado; no se ha publicado un correo de seguridad verificado.
4. Incluye, en el canal privado, commit o versión, descripción, alcance, pasos mínimos para reproducir sin afectar terceros y una mitigación propuesta si la tienes.

**No publiques** tokens de agentes, contraseñas de PostgreSQL, `APP_KEY`, `REVERB_APP_SECRET`, tokens de Telegram, volcados de BD, trazas con datos privados ni pruebas de explotación en Issues, PR, capturas o logs.

## Proceso de atención

La recepción y evaluación son manuales; **no se promete tiempo de respuesta ni corrección específicos**. Si se confirma una vulnerabilidad, la prioridad dependerá de explotabilidad, impacto, exposición del entorno y existencia de mitigaciones. Se preparará un parche y se divulgará la corrección cuando sea razonable hacerlo.

## Medidas para operadores

- Ejecutar la aplicación detrás de HTTPS/WSS y un proxy inverso configurado para producción.
- Desactivar `APP_DEBUG`; no publicar PostgreSQL ni paneles internos en Internet.
- Usar credenciales únicas, tokens de agente por host y el principio de mínimo privilegio.
- Mantener copias de seguridad verificadas; no ejecutar `migrate:fresh` ni `docker compose down -v` sobre datos valiosos.
- Comprobar `composer audit --locked`, `npm audit`, Dependabot y Gitleaks antes de publicar cambios.
- Rotar las credenciales expuestas inmediatamente: eliminarlas de un commit no revoca el acceso.

## Límites conocidos

El `docker-compose.yml` actual está orientado a **desarrollo**, utiliza el servidor integrado de PHP y publica puertos. Las Policies de Filament no impiden todas las operaciones SQL directas ni cascadas de claves foráneas. No se considera un despliegue endurecido para producción.

Documentación complementaria: [modelo de permisos](docs/security-model.md), [configuración](docs/configuration.md), [operaciones](docs/operations.md).
