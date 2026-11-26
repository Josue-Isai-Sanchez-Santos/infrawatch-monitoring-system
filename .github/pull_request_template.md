## Propósito

Describe qué problema resuelve este PR y por qué la solución es adecuada.

## Tipo de cambio

- [ ] Corrección de error
- [ ] Seguridad
- [ ] Funcionalidad compatible
- [ ] Cambio incompatible (documentar migración)
- [ ] Documentación
- [ ] Tests / CI / infraestructura

## Pruebas realizadas

- [ ] `./vendor/bin/pint --test`
- [ ] PHPUnit (SQLite local o PostgreSQL CI)
- [ ] unittest del agente (si aplica)
- [ ] `composer audit --locked` / `npm audit` (si aplica)
- [ ] `npm run build` / `docker compose config --quiet` (si aplica)
- [ ] Pruebas manuales de interfaz o flujos modificados

Incluye resultados, sistema y el SHA probado. No declares pruebas no ejecutadas.

## Compatibilidad y seguridad

- [ ] Revisé que no se incluyan `.env`, tokens, respaldos ni datos privados.
- [ ] Evalué permisos, cambios de BD y efectos sobre el historial.
- [ ] Actualicé README, CHANGELOG y guías afectadas, cuando corresponda.
- [ ] No ejecuté migraciones destructivas sobre datos reales.

## Evidencia y riesgos

Enumera cambios de comportamiento, riesgos conocidos, rollback y capturas **sanitizadas** si son pertinentes. Para vulnerabilidades sensibles usa [SECURITY.md](../SECURITY.md) y un canal privado, nunca este PR.
