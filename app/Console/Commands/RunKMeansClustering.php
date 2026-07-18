<?php

namespace App\Console\Commands;

use App\Services\KMeansClusteringService;
use Illuminate\Console\Command;

class RunKMeansClustering extends Command
{
    protected $signature   = 'karsa:cluster {--k=4 : Jumlah cluster} {--max-iter=100 : Maksimum iterasi}';
    protected $description = 'Jalankan K-Means Clustering untuk mengelompokkan kabupaten berdasarkan risiko perceraian';

    public function handle(): int
    {
        $k       = (int) $this->option('k');
        $maxIter = (int) $this->option('max-iter');

        $this->info("Menjalankan K-Means Clustering (k={$k}, max_iter={$maxIter})...");

        $service = new KMeansClusteringService($k, $maxIter);

        $result = $service->run();

        if (isset($result['error'])) {
            $this->error($result['error']);
            return self::FAILURE;
        }

        $this->info('Clustering selesai!');
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Jumlah Kabupaten', $result['districts']],
                ['Jumlah Cluster (k)', $result['clusters']],
                ['Iterasi yang Diperlukan', $result['iterations']],
            ]
        );

        $this->info('Label cluster yang dihasilkan:');
        foreach ($result['labels'] as $clusterId => $label) {
            $this->line("  Cluster {$clusterId} → {$label}");
        }

        $this->info('Hasil disimpan ke tabel cluster_results.');

        return self::SUCCESS;
    }
}