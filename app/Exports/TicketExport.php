<?php

namespace App\Exports;

use App\Models\Ticket_Management\RaiseTicket;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TicketExport implements FromArray, WithHeadings
{
    protected $search;
    protected $filters;

    public function __construct($search = null, $filters = [])
    {
        $this->search = $search;
        $this->filters = $filters;
    }

    public function array(): array
    {
        $query = RaiseTicket::with('assignUser');

        /*
        |--------------------------------------------------------------------------
        | 🔍 Search (keyword + label support)
        |--------------------------------------------------------------------------
        */

        if (!empty($this->search)) {

            $keyword = strtolower(trim($this->search));

            $statusMap = [
                'open' => 1,
                'in progress' => 2,
                'not fixed' => 3,
                'ready for retesting' => 4,
                'need discussion' => 5,
                'partially done' => 6,
                'reopen' => 7,
                'cancelled' => 8,
                'closed' => 9,
            ];

            $priorityMap = [
                'p1' => 1,
                'p2' => 2,
                'p3' => 3,
                'p4' => 4,
            ];

            $severityMap = [
                'low' => 1,
                'medium' => 2,
                'high' => 3,
                'critical' => 4,
            ];

            $issueTypeMap = [
                'bug' => 1,
                'observation' => 2,
                'question' => 3,
                'suggestion' => 4,
                'improvement' => 5,
                'new feature' => 6,
            ];

            $query->where(function ($q) use (
                $keyword,
                $statusMap,
                $priorityMap,
                $severityMap,
                $issueTypeMap
            ) {

                $q->where('title', 'ILIKE', "%{$keyword}%")
                    ->orWhere('ticket_code', 'ILIKE', "%{$keyword}%");

                if (isset($statusMap[$keyword])) {
                    $q->orWhere('status', $statusMap[$keyword]);
                }

                if (isset($priorityMap[$keyword])) {
                    $q->orWhere('priority', $priorityMap[$keyword]);
                }

                if (isset($severityMap[$keyword])) {
                    $q->orWhere('severity', $severityMap[$keyword]);
                }

                if (isset($issueTypeMap[$keyword])) {
                    $q->orWhere('issue_type', $issueTypeMap[$keyword]);
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 🎯 Filters
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['status'])) {

            $status = is_array($this->filters['status'])
                ? $this->filters['status']
                : explode(',', $this->filters['status']);

            $status = array_filter(array_map('intval', $status));

            if (!empty($status)) {
                $query->whereIn('status', $status);
            }
        }

        if (!empty($this->filters['priority'])) {

            $priority = is_array($this->filters['priority'])
                ? $this->filters['priority']
                : explode(',', $this->filters['priority']);

            $priority = array_filter(array_map('intval', $priority));

            if (!empty($priority)) {
                $query->whereIn('priority', $priority);
            }
        }

        if (!empty($this->filters['severity'])) {

            $severity = is_array($this->filters['severity'])
                ? $this->filters['severity']
                : explode(',', $this->filters['severity']);

            $severity = array_filter(array_map('intval', $severity));

            if (!empty($severity)) {
                $query->whereIn('severity', $severity);
            }
        }

        if (!empty($this->filters['issue_type'])) {

            $issueType = is_array($this->filters['issue_type'])
                ? $this->filters['issue_type']
                : explode(',', $this->filters['issue_type']);

            $issueType = array_filter(array_map('intval', $issueType));

            if (!empty($issueType)) {
                $query->whereIn('issue_type', $issueType);
            }
        }

        $tickets = $query->orderBy('id', 'desc')->get();

        /*
        |--------------------------------------------------------------------------
        | 📦 Format Data
        |--------------------------------------------------------------------------
        */

        $data = [];

        foreach ($tickets as $t) {

            $data[] = [
                'Ticket Code' => $t->ticket_code,
                'Title' => $t->title,
                'Description' => $t->description,
                'Assign User' => $t->assignUser->name ?? null,
                'Status' => $this->getStatusLabel($t->status),
                'Priority' => $this->getPriorityLabel($t->priority),
                'Severity' => $this->getSeverityLabel($t->severity),
                'Issue Type' => $this->getIssueTypeLabel($t->issue_type),
                'Created At' => $t->created_at,
            ];
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | 🏷 Label Helpers
    |--------------------------------------------------------------------------
    */

    private function getStatusLabel($status)
    {
        return [
            1 => "Open",
            2 => "In Progress",
            3 => "Not Fixed",
            4 => "Ready For Retesting",
            5 => "Need Discussion",
            6 => "Partially Done",
            7 => "Reopen",
            8 => "Cancelled",
            9 => "Closed",
        ][$status] ?? null;
    }

    private function getPriorityLabel($priority)
    {
        return [
            1 => "P1",
            2 => "P2",
            3 => "P3",
            4 => "P4",
        ][$priority] ?? null;
    }

    private function getSeverityLabel($severity)
    {
        return [
            1 => "Low",
            2 => "Medium",
            3 => "High",
            4 => "Critical",
        ][$severity] ?? null;
    }

    private function getIssueTypeLabel($issueType)
    {
        return [
            1 => "Bug",
            2 => "Observation",
            3 => "Question",
            4 => "Suggestion",
            5 => "Improvement",
            6 => "New Feature",
        ][$issueType] ?? null;
    }

    public function headings(): array
    {
        return [
            'Ticket Code',
            'Title',
            'Description',
            'Assign User',
            'Status',
            'Priority',
            'Severity',
            'Issue Type',
            'Created At',
        ];
    }
}
