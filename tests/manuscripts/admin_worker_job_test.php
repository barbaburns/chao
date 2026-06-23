<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\Admin;

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$snapshot = Admin::buildWorkerJobSnapshot([
    'job_id' => 'job-123',
    'manuscript_id' => 9226,
    'page_count' => 2,
    'pages' => [
        [
            'page_number' => 1,
            'filename' => 'page-1.png',
            'image_path' => '/data/jobs/job-123/pages/page-1.png',
            'image_url' => 'http://127.0.0.1:8002/jobs/job-123/pages/page-1.png',
            'raw_text' => 'Casa grande. Outra casa na margem.',
            'layout_data' => [
                'text' => 'Casa grande. Outra casa na margem.',
                'lines' => [
                    [
                        'text' => 'Casa grande.',
                        'bbox' => ['x' => 20, 'y' => 30, 'width' => 180, 'height' => 28],
                        'words' => [
                            ['text' => 'Casa', 'bbox' => ['x' => 20, 'y' => 30, 'width' => 54, 'height' => 28]],
                            ['text' => 'grande', 'bbox' => ['x' => 84, 'y' => 30, 'width' => 92, 'height' => 28]],
                        ],
                    ],
                ],
            ],
            'htr' => [
                'status' => 'queued',
                'job_id' => 'htr-1',
                'page_id' => 'page-1',
                'document_id' => 'doc-9226',
            ],
        ],
        [
            'page_number' => 2,
            'filename' => 'page-2.png',
            'image_path' => '/data/jobs/job-123/pages/page-2.png',
        ],
    ],
]);
$review = Admin::normalizeWorkerPageReviewInput([
    1 => [
        'status' => 'revisado',
        'transcription' => "  Texto revisto  ",
        'notes' => "  Observacao  ",
    ],
    2 => [
        'status' => 'invalido',
        'transcription' => '',
        'notes' => '',
    ],
]);
$rows = Admin::buildWorkerPageRows($snapshot, $review);

assertSameValue('job-123', $snapshot['job_id'], 'Snapshot should keep job id');
assertSameValue(9226, $snapshot['manuscript_id'], 'Snapshot should keep manuscript id');
assertSameValue(2, $snapshot['page_count'], 'Snapshot should keep page count');
assertSameValue(2, count($snapshot['pages']), 'Snapshot should keep pages');
assertSameValue('queued', $snapshot['pages'][0]['htr_status'], 'Snapshot should flatten HTR status');
assertSameValue('htr-1', $snapshot['pages'][0]['htr_job_id'], 'Snapshot should flatten HTR job id');
assertSameValue('http://127.0.0.1:8002/jobs/job-123/pages/page-1.png', $snapshot['pages'][0]['image_url'], 'Snapshot should keep public image url when worker provides it');
assertSameValue('Casa grande. Outra casa na margem.', $snapshot['pages'][0]['raw_text'], 'Snapshot should keep raw HTR text when worker provides it');
assertSameValue('Casa', $snapshot['pages'][0]['layout_data']['lines'][0]['words'][0]['text'], 'Snapshot should keep layout data when worker provides it');
assertSameValue('', $snapshot['pages'][1]['htr_status'], 'Snapshot should default empty HTR status');
assertTrueValue(isset($snapshot['stored_at']), 'Snapshot should record when it was stored');
assertSameValue('revisado', $review[1]['status'], 'Review input should keep valid editorial status');
assertSameValue('Texto revisto', $review[1]['transcription'], 'Review input should trim transcription');
assertSameValue('Observacao', $review[1]['notes'], 'Review input should trim notes');
assertSameValue('pendente', $review[2]['status'], 'Review input should fallback invalid status to pending');
assertSameValue(2, count($rows), 'Page rows should keep every page');
assertSameValue(1, $rows[0]['page_number'], 'Page rows should keep page number');
assertSameValue('page-1.png', $rows[0]['filename'], 'Page rows should keep filename');
assertSameValue('queued', $rows[0]['htr_status'], 'Page rows should keep HTR status');
assertSameValue('http://127.0.0.1:8002/jobs/job-123/pages/page-1.png', $rows[0]['image_url'], 'Page rows should keep page image url');
assertSameValue('Casa grande. Outra casa na margem.', $rows[0]['raw_text'], 'Page rows should keep raw text');
assertSameValue('revisado', $rows[0]['editorial_status'], 'Page rows should merge saved editorial status');
assertSameValue('Texto revisto', $rows[0]['review_transcription'], 'Page rows should merge reviewed transcription');
assertSameValue('Observacao', $rows[0]['review_notes'], 'Page rows should merge review notes');
assertSameValue('-', $rows[1]['htr_status'], 'Page rows should fallback empty HTR status to dash');
assertSameValue('pendente', $rows[1]['editorial_status'], 'Page rows should fallback editorial status');
assertSameValue('doc-9226', $rows[0]['htr_document_id'], 'Page rows should keep document id');

echo 'admin_worker_job_test: ok' . PHP_EOL;
