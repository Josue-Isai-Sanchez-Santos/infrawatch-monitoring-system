<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Control manual y automático de InfraWatch
            </x-slot>

            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                <p>
                    Esta sección permite ejecutar y detener los procesos principales del sistema de monitoreo desde el panel administrativo.
                </p>

                <div>
                    <strong>Iniciar lectura TCP automática:</strong>
                    inicia Laravel Scheduler en segundo plano para ejecutar los chequeos programados.
                </div>

                <div>
                    <strong>Detener lectura TCP automática:</strong>
                    detiene el proceso del scheduler iniciado desde este panel.
                </div>

                <div>
                    <strong>Ejecutar lectura TCP una vez:</strong>
                    ejecuta una sola vez el comando <code>php artisan monitor:services</code>.
                </div>

                <div>
                    <strong>Iniciar agente automático:</strong>
                    inicia el agente Python en segundo plano para enviar métricas periódicamente.
                </div>

                <div>
                    <strong>Detener agente automático:</strong>
                    detiene el proceso automático del agente iniciado desde este panel.
                </div>

                <div>
                    <strong>Ejecutar agente una vez:</strong>
                    ejecuta el agente Python una sola vez.
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Advertencia
            </x-slot>

            <p class="text-sm text-gray-600 dark:text-gray-300">
                Estos botones están pensados para entorno local o de desarrollo. En producción se recomienda usar cron,
                systemd, supervisor o Docker services para manejar procesos automáticos.
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
