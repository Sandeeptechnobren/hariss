<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class SearchHelper
{
    /**
     * Apply global search on given query.
     *
     * @param Builder $query
     * @param string|null $searchTerm
     * @param array $columns
     * @return Builder
     */
    public static function applySearch(Builder $query, ?string $searchTerm, array $columns): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        $table = $query->getModel()->getTable();

        $query->where(function ($q) use ($searchTerm, $columns, $table) {
            foreach ($columns as $column) {

              if ($column === 'created_user.name') {
                $q->orWhereExists(function ($sub) use ($searchTerm, $table) {
                    $sub->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.id', "$table.created_user")
                        ->where('users.name', 'LIKE', "%{$searchTerm}%");
                });
                continue;
            }

            if ($column === 'updated_user.name') {
                $q->orWhereExists(function ($sub) use ($searchTerm, $table) {
                    $sub->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.id', "$table.updated_user")
                        ->where('users.name', 'LIKE', "%{$searchTerm}%");
                });
                continue;
            }
                // 🔹 Handle normal relations like item.name
                if (strpos($column, '.') !== false) {
                    [$relation, $relColumn] = explode('.', $column);
                    if (method_exists($q->getModel(), $relation)) {
                        $q->orWhereHas($relation, function ($rel) use ($relColumn, $searchTerm) {
                            $rel->where($relColumn, 'LIKE', '%' . $searchTerm . '%');
                        });
                    }
                    continue;
                }

                // 🔹 Handle normal table columns
                $q->orWhere($table . '.' . $column, 'LIKE', '%' . $searchTerm . '%');
            }
        });

        return $query;
    }

    /**
     * Dynamically join users table for created_user or updated_user
     */
    protected static function joinUserTable($query, $table, $column, $alias)
    {
        $joins = collect($query->getQuery()->joins)->pluck('table')->toArray();
        $aliasTable = $alias . '_table';
    }
}
