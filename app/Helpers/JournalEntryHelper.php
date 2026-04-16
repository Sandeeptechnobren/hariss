<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\JournalEntry;
use App\Models\Account;
use App\Models\JournalEntryDetail;

class JournalEntryHelper
{

   public function createCreditNoteJournal($creditNote)
    {
        $entry = JournalEntry::create([
            'entry_no'       => generateEntryNo(),
            'reference_type' => 'credit_note',
            'reference_id'   => $creditNote->id,
            'date'           => now(),
            'total_amount'   => $creditNote->total_amount,
        ]);

        // 👉 Debit: Supplier A/C
        JournalEntryDetail::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $creditNote->supplier_id,
            'type'             => 'debit',
            'amount'           => $creditNote->total_amount,
        ]);

        $account = Account::where('name', 'Purchase Return A/C')->first();

        // 👉 Credit: Purchase Return A/C
        JournalEntryDetail::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $account->id,
            'type'             => 'credit',
            'amount'           => $creditNote->total_amount,
        ]);
    }

}
