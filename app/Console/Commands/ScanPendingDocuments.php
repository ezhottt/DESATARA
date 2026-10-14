<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\Evidence\DocumentMalwareScanner;
use Illuminate\Console\Command;
use Throwable;

final class ScanPendingDocuments extends Command
{
    protected $signature = 'documents:scan-pending {--limit=25 : Maximum quarantined documents to scan in one batch}';

    protected $description = 'Scan quarantined document uploads and release only clean files';

    public function handle(DocumentMalwareScanner $scanner): int
    {
        $limit = (int) $this->option('limit');
        if ($limit < 1 || $limit > 500) {
            $this->error('Limit must be between 1 and 500.');

            return self::FAILURE;
        }

        $documents = Document::query()
            ->where('malware_scan_status', 'pending')
            ->where('storage_state', 'quarantined')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $failures = 0;

        foreach ($documents as $document) {
            try {
                $result = $scanner->scan($document);
                $this->line("{$document->uuid}: {$result->malware_scan_status}");
            } catch (Throwable) {
                $failures++;
                $this->error("{$document->uuid}: scan failed; document remains quarantined.");
            }
        }

        $this->info("Scanned {$documents->count()} quarantined document(s); failures: {$failures}.");

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
