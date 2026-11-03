<?php

namespace App\Support\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Search, filter, sort and paginate an array of rows for the dashboard data
 * table's remote mode (?q=&sort=&dir=&page=&per_page= plus filter keys).
 */
final class TableQuery
{
    /**
     * @param  list<string>  $searchable  row keys matched by ?q=
     * @param  list<string>  $filters  row keys matched exactly when present in the query
     */
    public static function respond(Request $request, array $rows, array $searchable, array $filters = []): array
    {
        $q = Str::lower(trim((string) $request->query('q', '')));

        $rows = array_values(array_filter($rows, function (array $row) use ($request, $q, $searchable, $filters) {
            foreach ($filters as $key) {
                $value = $request->query($key);
                if ($value !== null && $value !== '' && (string) ($row[$key] ?? '') !== (string) $value) {
                    return false;
                }
            }

            if ($q === '') {
                return true;
            }

            foreach ($searchable as $key) {
                if (str_contains(Str::lower((string) ($row[$key] ?? '')), $q)) {
                    return true;
                }
            }

            return false;
        }));

        $sort = (string) $request->query('sort', '');
        if ($sort !== '' && $rows && array_key_exists($sort, $rows[0])) {
            $dir = $request->query('dir') === 'asc' ? 1 : -1;
            usort($rows, fn ($a, $b) => ($a[$sort] <=> $b[$sort]) * $dir);
        }

        $perPage = max(5, min(100, (int) $request->query('per_page', 15)));
        $total = count($rows);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($pages, (int) $request->query('page', 1)));

        return [
            'rows' => array_slice($rows, ($page - 1) * $perPage, $perPage),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'sum' => round(array_sum(array_column($rows, 'total')), 2),
        ];
    }
}
