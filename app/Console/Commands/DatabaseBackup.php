<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:database-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melakukan backup database PostgreSQL secara otomatis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = 'backup-'.now()->format('Y-m-d').'.sql';

        // Pastikan path pg_dump sudah ada di environment variable komputer Anda
        // Jika di Windows/XAMPP biasanya: C:\PostgreSQL\bin\pg_dump.exe
        putenv('PGPASSWORD='.config('database.connections.pgsql.password'));
        $command = sprintf(
            'pg_dump -h %s -U %s %s > %s',
            config('database.connections.pgsql.host'),
            config('database.connections.pgsql.username'),
            config('database.connections.pgsql.database'),
            storage_path('app/backups/'.$filename)
        );

        $returnVar = null;
        $output = null;

        // Pastikan folder backup ada
        if (! is_dir(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0777, true);
        }

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $this->info('Backup database berhasil: '.$filename);
        } else {
            $this->error('Backup database gagal.');
        }
    }
}
