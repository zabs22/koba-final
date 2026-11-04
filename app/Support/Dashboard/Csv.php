<?php

namespace App\Support\Dashboard;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class Csv
{
    /**
     * @param  array<string, string>  $columns  row key => header
     * @param  iterable<array>  $rows
     */
    public static function download(string $name, array $columns, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows "Éclair" correctly
            fputcsv($out, ['KOBA demo data: generated figures, not real transactions']);
            fputcsv($out, array_values($columns));
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($key) => is_bool($row[$key] ?? null) ? ($row[$key] ? 'yes' : 'no') : ($row[$key] ?? ''), array_keys($columns)));
            }
            fclose($out);
        }, 'koba-'.$name.'-'.now(config('dashboard.timezone'))->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
