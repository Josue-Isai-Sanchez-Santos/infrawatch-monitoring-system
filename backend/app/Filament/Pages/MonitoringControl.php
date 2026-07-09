<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Process\Process;

class MonitoringControl extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationLabel = 'Control de Monitoreo';

    protected static ?string $title = 'Control de Monitoreo';

    protected static ?string $navigationGroup = 'InfraWatch';

    protected static string $view = 'filament.pages.monitoring-control';

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('start_tcp_auto')
                ->label('Iniciar lectura TCP automática')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Iniciar monitoreo TCP automático')
                ->modalDescription('Esto iniciará Laravel Scheduler en segundo plano usando php artisan schedule:work.')
                ->action(fn () => $this->startTcpScheduler()),

            Action::make('stop_tcp_auto')
                ->label('Detener lectura TCP automática')
                ->icon('heroicon-o-stop-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Detener monitoreo TCP automático')
                ->modalDescription('Esto detendrá el proceso automático del scheduler si está activo.')
                ->action(fn () => $this->stopTcpScheduler()),

            Action::make('run_tcp_once')
                ->label('Ejecutar lectura TCP una vez')
                ->icon('heroicon-o-bolt')
                ->color('info')
                ->action(fn () => $this->runTcpOnce()),

            Action::make('start_agent_auto')
                ->label('Iniciar agente automático')
                ->icon('heroicon-o-cpu-chip')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Iniciar agente Python automático')
                ->modalDescription('Esto iniciará el agente Python en segundo plano usando el intervalo configurado.')
                ->action(fn () => $this->startAgentAuto()),

            Action::make('stop_agent_auto')
                ->label('Detener agente automático')
                ->icon('heroicon-o-stop')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Detener agente Python automático')
                ->modalDescription('Esto detendrá el proceso automático del agente si está activo.')
                ->action(fn () => $this->stopAgentAuto()),

            Action::make('run_agent_once')
                ->label('Ejecutar agente una vez')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->action(fn () => $this->runAgentOnce()),
        ];
    }

    private function runTcpOnce(): void
    {
        try {
            Artisan::call('monitor:services');

            Notification::make()
                ->title('Lectura TCP ejecutada')
                ->body('El comando monitor:services se ejecutó correctamente una vez.')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Error al ejecutar lectura TCP')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function startTcpScheduler(): void
    {
        $pidFile = storage_path('app/infrawatch-scheduler.pid');
        $logFile = storage_path('logs/infrawatch-scheduler.log');

        if ($this->isProcessRunning($pidFile)) {
            Notification::make()
                ->title('Scheduler ya está activo')
                ->body('El monitoreo TCP automático ya parece estar ejecutándose.')
                ->warning()
                ->send();

            return;
        }

        $command = sprintf(
            'cd %s && nohup php artisan schedule:work >> %s 2>&1 & echo $!',
            escapeshellarg(base_path()),
            escapeshellarg($logFile)
        );

        $process = Process::fromShellCommandline($command);
        $process->run();

        $pid = trim($process->getOutput());

        if ($process->isSuccessful() && $pid !== '') {
            file_put_contents($pidFile, $pid);

            Notification::make()
                ->title('Lectura TCP automática iniciada')
                ->body("Scheduler iniciado en segundo plano. PID: {$pid}")
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('No se pudo iniciar el scheduler')
            ->body($process->getErrorOutput() ?: 'Error desconocido.')
            ->danger()
            ->send();
    }

    private function startAgentAuto(): void
    {
        $agentPath = base_path('../agent');
        $pythonPath = $agentPath.'/venv/bin/python';
        $pidFile = storage_path('app/infrawatch-agent.pid');
        $logFile = $agentPath.'/agent.log';

        if (! is_dir($agentPath)) {
            Notification::make()
                ->title('No se encontró la carpeta del agente')
                ->body("Ruta esperada: {$agentPath}")
                ->danger()
                ->send();

            return;
        }

        if (! file_exists($pythonPath)) {
            Notification::make()
                ->title('No se encontró el Python del entorno virtual')
                ->body("Ruta esperada: {$pythonPath}")
                ->danger()
                ->send();

            return;
        }

        if ($this->isProcessRunning($pidFile)) {
            Notification::make()
                ->title('Agente ya está activo')
                ->body('El agente Python automático ya parece estar ejecutándose.')
                ->warning()
                ->send();

            return;
        }

        $command = sprintf(
            'cd %s && nohup %s agent.py --interval 60 >> %s 2>&1 & echo $!',
            escapeshellarg($agentPath),
            escapeshellarg($pythonPath),
            escapeshellarg($logFile)
        );

        $process = Process::fromShellCommandline($command);
        $process->run();

        $pid = trim($process->getOutput());

        if ($process->isSuccessful() && $pid !== '') {
            file_put_contents($pidFile, $pid);

            Notification::make()
                ->title('Agente automático iniciado')
                ->body("Agente Python iniciado en segundo plano. PID: {$pid}")
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('No se pudo iniciar el agente')
            ->body($process->getErrorOutput() ?: 'Error desconocido.')
            ->danger()
            ->send();
    }

    private function runAgentOnce(): void
    {
        $agentPath = base_path('../agent');
        $pythonPath = $agentPath.'/venv/bin/python';

        if (! is_dir($agentPath)) {
            Notification::make()
                ->title('No se encontró la carpeta del agente')
                ->body("Ruta esperada: {$agentPath}")
                ->danger()
                ->send();

            return;
        }

        if (! file_exists($pythonPath)) {
            Notification::make()
                ->title('No se encontró el Python del entorno virtual')
                ->body("Ruta esperada: {$pythonPath}")
                ->danger()
                ->send();

            return;
        }

        $command = sprintf(
            'cd %s && %s agent.py --once',
            escapeshellarg($agentPath),
            escapeshellarg($pythonPath)
        );

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(30);
        $process->run();

        if ($process->isSuccessful()) {
            Notification::make()
                ->title('Agente ejecutado una vez')
                ->body('El agente Python envió métricas correctamente.')
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Error al ejecutar el agente')
            ->body($process->getErrorOutput() ?: $process->getOutput() ?: 'Error desconocido.')
            ->danger()
            ->send();
    }

    private function isProcessRunning(string $pidFile): bool
    {
        if (! file_exists($pidFile)) {
            return false;
        }

        $pid = trim(file_get_contents($pidFile));

        if ($pid === '') {
            return false;
        }

        $process = Process::fromShellCommandline('ps -p '.escapeshellarg($pid).' > /dev/null 2>&1');
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        @unlink($pidFile);

        return false;
    }

    private function stopTcpScheduler(): void
    {
        $pidFile = storage_path('app/infrawatch-scheduler.pid');

        $this->stopProcessFromPidFile(
            pidFile: $pidFile,
            successTitle: 'Lectura TCP automática detenida',
            notRunningTitle: 'Scheduler no activo',
            notRunningBody: 'No se encontró un proceso activo de monitoreo TCP automático.'
        );
    }

    private function stopAgentAuto(): void
    {
        $pidFile = storage_path('app/infrawatch-agent.pid');

        $this->stopProcessFromPidFile(
            pidFile: $pidFile,
            successTitle: 'Agente automático detenido',
            notRunningTitle: 'Agente no activo',
            notRunningBody: 'No se encontró un proceso activo del agente Python.'
        );
    }

    private function stopProcessFromPidFile(
        string $pidFile,
        string $successTitle,
        string $notRunningTitle,
        string $notRunningBody
    ): void {
        if (! file_exists($pidFile)) {
            Notification::make()
                ->title($notRunningTitle)
                ->body($notRunningBody)
                ->warning()
                ->send();

            return;
        }

        $pid = trim(file_get_contents($pidFile));

        if ($pid === '') {
            @unlink($pidFile);

            Notification::make()
                ->title($notRunningTitle)
                ->body($notRunningBody)
                ->warning()
                ->send();

            return;
        }

        if (! $this->isProcessRunning($pidFile)) {
            @unlink($pidFile);

            Notification::make()
                ->title($notRunningTitle)
                ->body($notRunningBody)
                ->warning()
                ->send();

            return;
        }

        $process = Process::fromShellCommandline('kill '.escapeshellarg($pid));
        $process->run();

        sleep(1);

        if ($this->isPidRunning($pid)) {
            $forceProcess = Process::fromShellCommandline('kill -9 '.escapeshellarg($pid));
            $forceProcess->run();
        }

        @unlink($pidFile);

        Notification::make()
            ->title($successTitle)
            ->body("Proceso detenido correctamente. PID: {$pid}")
            ->success()
            ->send();
    }

    private function isPidRunning(string $pid): bool
    {
        if ($pid === '') {
            return false;
        }

        $process = Process::fromShellCommandline('ps -p '.escapeshellarg($pid).' > /dev/null 2>&1');
        $process->run();

        return $process->isSuccessful();
    }
}
